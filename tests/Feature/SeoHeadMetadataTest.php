<?php

namespace IlBronza\CRUD\Tests\Feature;

use DOMDocument;
use DOMXPath;
use IlBronza\CRUD\Seo\SeoUrlResolver;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\Model\HasSeo;
use IlBronza\UikitTemplate\Frontend\FrontendContext;
use IlBronza\UikitTemplate\Frontend\FrontendServiceProvider;
use IlBronza\UikitTemplate\Helpers\HeadMetadata;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SeoHeadMetadataTest extends TestCase
{
    private SeoHeadArticle $article;

    protected function getPackageProviders($app)
    {
        return array_merge(parent::getPackageProviders($app), [FrontendServiceProvider::class]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['uikittemplate.availableLanguages' => ['it', 'en']]);

        $migration = require __DIR__.'/../../database/migrations/2026_10_22_000000_create_crud_seo_table.php';
        $migration->up();

        Schema::create('seo_head_articles', static function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
        $this->article = SeoHeadArticle::create(['title' => 'Model title']);
        $this->article->seo()->createMany([
            ['locale' => 'it', 'title' => 'Titolo italiano', 'description' => 'Descrizione italiana'],
            [
                'locale' => 'en', 'title' => 'SEO English <title>', 'description' => 'English description',
                'canonical_url' => 'https://public.test/en/article', 'robots' => 'noindex, follow',
                'og_title' => 'English sharing title', 'og_description' => 'English sharing description',
                'og_image' => 'https://public.test/image.jpg', 'og_image_alt' => 'Image description',
            ],
        ]);
    }

    protected function defineRoutes($router)
    {
        $router->get('/seo-head/{slug?}', function () {
            if ($this->app->bound('seo-head.configure')) {
                ($this->app->make('seo-head.configure'))(app('uikittemplate')->head());
            }

            return view('uikit::frontend.head', [
                'frontendContext' => $this->app->bound('seo-head.context')
                    ? $this->app->make('seo-head.context')
                    : new FrontendContext('en', 'Generic page title', page: $this->article),
            ]);
        });
    }

    private function document(string $html): DOMXPath
    {
        $document = new DOMDocument;
        @$document->loadHTML($html);

        return new DOMXPath($document);
    }

    private function content(DOMXPath $document, string $selector): string
    {
        $nodes = $document->query($selector);
        $this->assertCount(1, $nodes);

        return $nodes->item(0)->getAttribute('content');
    }

    private function canonical(DOMXPath $document): string
    {
        $nodes = $document->query('//link[@rel="canonical"]');
        $this->assertCount(1, $nodes);

        return $nodes->item(0)->getAttribute('href');
    }

    public function test_frontend_reads_the_real_trait_in_the_content_locale_and_imports_metadata_once(): void
    {
        $this->article->load('seo');
        DB::enableQueryLog();
        DB::flushQueryLog();

        $response = $this->get('/seo-head/article?utm_source=mail');
        $response->assertOk();
        $document = $this->document($response->getContent());

        $this->assertCount(1, $document->query('//title'));
        $this->assertSame('SEO English <title>', $document->query('//title')->item(0)->textContent);
        $this->assertSame('English description', $this->content($document, '//meta[@name="description"]'));
        $this->assertSame('https://public.test/en/article', $this->canonical($document));
        $this->assertSame('noindex, follow', $this->content($document, '//meta[@name="robots"]'));
        $this->assertSame('English sharing title', $this->content($document, '//meta[@property="og:title"]'));
        $this->assertSame('https://public.test/en/article', $this->content($document, '//meta[@property="og:url"]'));
        $this->assertSame('https://public.test/image.jpg', $this->content($document, '//meta[@property="og:image"]'));
        $this->assertSame('English sharing description', $this->content($document, '//meta[@name="twitter:description"]'));
        $this->assertSame('summary_large_image', $this->content($document, '//meta[@name="twitter:card"]'));
        $this->assertSame(1, $this->article->metadataCalls);
        $this->assertSame([], DB::getQueryLog());
        $this->assertSame('it', app()->getLocale());
        $this->assertSame('it', app('translator')->getLocale());
    }

    public function test_controller_overrides_win_even_when_the_model_is_registered_after_them(): void
    {
        $model = $this->article;
        $this->app->instance('seo-head.configure', static function (HeadMetadata $head) use ($model) {
            $head->setTitle('Controller title')->setCanonical('https://override.test/en/article')
                ->setDescription('Controller description')->setRobots('index, follow')
                ->setOpenGraphTitle('Controller share title')->setTwitterTitle('Controller tweet title')
                ->setSeoModel($model, 'en');
        });

        $response = $this->get('/seo-head/article');
        $response->assertOk();
        $document = $this->document($response->getContent());
        $this->assertSame('Controller title', $document->query('//title')->item(0)->textContent);
        $this->assertSame('https://override.test/en/article', $this->canonical($document));
        $this->assertSame('https://override.test/en/article', $this->content($document, '//meta[@property="og:url"]'));
        $this->assertSame('Controller share title', $this->content($document, '//meta[@property="og:title"]'));
        $this->assertSame('Controller description', $this->content($document, '//meta[@name="description"]'));
        $this->assertSame('index, follow', $this->content($document, '//meta[@name="robots"]'));
        $this->assertSame('English sharing description', $this->content($document, '//meta[@property="og:description"]'));
        $this->assertSame('English sharing description', $this->content($document, '//meta[@name="twitter:description"]'));
        $this->assertSame('Controller tweet title', $this->content($document, '//meta[@name="twitter:title"]'));
        $this->assertSame('https://public.test/image.jpg', $this->content($document, '//meta[@property="og:image"]'));
        $this->assertSame('English description', $this->article->getSeo('en')->description);
        $this->assertSame('https://override.test/it/article', $document->query('//link[@hreflang="it"]')->item(0)->getAttribute('href'));
    }

    public function test_explicit_context_meta_overrides_model_defaults_and_uses_published_language_urls(): void
    {
        $context = new FrontendContext('en', 'Generic title', [
            'title' => 'Context SEO title', 'description' => 'Context description',
            'canonical' => 'https://context.test/article', 'og:title' => 'Context sharing title',
        ], page: $this->article, languageUrls: [
            'it' => 'https://public.test/articolo', 'en' => 'https://public.test/en/article',
        ]);
        $this->app->instance('seo-head.context', $context);
        $response = $this->get('/seo-head/article');
        $response->assertOk();
        $document = $this->document($response->getContent());

        $this->assertSame('Context SEO title', $document->query('//title')->item(0)->textContent);
        $this->assertSame('Context description', $this->content($document, '//meta[@name="description"]'));
        $this->assertSame('Context sharing title', $this->content($document, '//meta[@property="og:title"]'));
        $this->assertSame('https://context.test/article', $this->canonical($document));
        $this->assertSame('https://context.test/article', $this->content($document, '//meta[@property="og:url"]'));
        $this->assertSame('https://public.test/articolo', $document->query('//link[@hreflang="it"]')->item(0)->getAttribute('href'));
    }

    public function test_missing_model_url_keeps_request_fallback_and_sitemap_resolver_independent(): void
    {
        $this->article->seo()->delete();
        $this->article->publicUrls = [];
        $response = $this->get('/seo-head/article?tracking=1&page=2');
        $response->assertOk();
        $document = $this->document($response->getContent());

        $this->assertSame('http://localhost/seo-head/article', $this->canonical($document));
        $this->assertSame('http://localhost/seo-head/article', $this->content($document, '//meta[@property="og:url"]'));
        $this->assertSame('http://localhost/it/seo-head/article', $document->query('//link[@hreflang="it"]')->item(0)->getAttribute('href'));
        $this->assertNull(app(SeoUrlResolver::class)->canonical($this->article, 'en'));
    }

    public function test_trait_default_locale_uses_frontend_attribute_without_changing_application_locale(): void
    {
        request()->attributes->set('frontend.locale', 'en');
        $this->assertSame('SEO English <title>', $this->article->getMetaTags()['title']);
        $this->assertSame('en', $this->article->getSeo()->locale);
        $this->assertSame('Titolo italiano', $this->article->getMetaTags('it')['title']);
        $this->assertSame('it', app()->getLocale());
    }

    public function test_legacy_seo_partial_and_shared_renderer_emit_one_title_and_one_set_of_tags(): void
    {
        $html = Blade::render(<<<'BLADE'
            <head>
                @include('crud::seo.meta', ['model' => $model, 'locale' => 'en'])
                @include('uikittemplate-head::metadata', ['headTitle' => 'Fallback title'])
            </head>
            BLADE, ['model' => $this->article]);
        $document = $this->document($html);
        $this->assertCount(1, $document->query('//title'));
        $this->assertSame('SEO English <title>', $document->query('//title')->item(0)->textContent);
        $this->assertSame('https://public.test/en/article', $this->canonical($document));
        $this->assertCount(1, $document->query('//meta[@property="og:title"]'));
        $this->assertCount(1, $document->query('//meta[@name="description"]'));
    }

    public function test_model_can_be_supplied_by_controller_without_frontend_context(): void
    {
        app('uikittemplate')->head()->setSeoModel($this->article, 'en');
        $document = $this->document(view('uikittemplate-head::metadata', ['headTitle' => 'Generic admin title'])->render());
        $this->assertSame('SEO English <title>', $document->query('//title')->item(0)->textContent);
        $this->assertSame('English description', $this->content($document, '//meta[@name="description"]'));
        $this->assertSame('https://public.test/en/article', $this->canonical($document));
    }

    public function test_independent_open_graph_url_is_preserved_when_explicitly_overridden(): void
    {
        $head = app('uikittemplate')->head()->setSeoModel($this->article, 'en')
            ->setCanonical('https://canonical.test/article')->setOpenGraph(['og:url' => 'https://share.test/article']);
        $this->assertSame('https://canonical.test/article', $head->getCanonical());
        $this->assertSame('https://share.test/article', $head->getOpenGraph()['og:url']);
    }

    public function test_each_social_parameter_can_be_overridden_independently_of_model_metadata(): void
    {
        $this->app->instance('seo-head.configure', static function (HeadMetadata $head) {
            $head->setOpenGraphDescription('Share <description>')
                ->setOpenGraphUrl('https://share.test/article')->setOpenGraphType('article')
                ->setOpenGraphImage('https://share.test/image.jpg')->setOpenGraphImageAlt('Social image')
                ->setOpenGraphLocale('en_GB')->setOpenGraphSiteName('Site')
                ->setTwitterCard('summary')->setTwitterDescription('Twitter description')
                ->setTwitterImage('https://twitter.test/image.jpg')->setTwitterImageAlt('Twitter image')
                ->setTwitterSite('@site')->setTwitterCreator('@author');
        });

        $response = $this->get('/seo-head/article');
        $response->assertOk();
        $document = $this->document($response->getContent());
        foreach ([
            'og:title' => 'English sharing title', 'og:description' => 'Share <description>',
            'og:url' => 'https://share.test/article', 'og:type' => 'article',
            'og:image' => 'https://share.test/image.jpg', 'og:image:alt' => 'Social image',
            'og:locale' => 'en_GB', 'og:site_name' => 'Site',
        ] as $property => $value) {
            $this->assertSame($value, $this->content($document, '//meta[@property="'.$property.'"]'));
        }
        foreach ([
            'description' => 'English description', 'twitter:card' => 'summary',
            'twitter:title' => 'English sharing title', 'twitter:description' => 'Twitter description',
            'twitter:image' => 'https://twitter.test/image.jpg', 'twitter:image:alt' => 'Twitter image',
            'twitter:site' => '@site', 'twitter:creator' => '@author',
        ] as $name => $value) {
            $this->assertSame($value, $this->content($document, '//meta[@name="'.$name.'"]'));
        }
        $this->assertSame('https://public.test/en/article', $this->canonical($document));
        $this->assertSame('https://public.test/image.jpg', $this->article->getSeo('en')->og_image);
    }

    public function test_null_overrides_omit_inherited_tags_without_restoring_model_or_context_values(): void
    {
        $this->app->instance('seo-head.context', new FrontendContext('en', 'Generic title', [
            'title' => 'Context title', 'description' => 'Context description',
            'og:title' => 'Context social title', 'twitter:description' => 'Context Twitter description',
        ], page: $this->article));
        $this->app->instance('seo-head.configure', static function (HeadMetadata $head) {
            $head->setTitle(null)->setDescription(null)->setRobots(null)
                ->setOpenGraphTitle(null)->setOpenGraphUrl(null)->setTwitterDescription(null);
        });

        $response = $this->get('/seo-head/article');
        $response->assertOk();
        $document = $this->document($response->getContent());
        foreach (['//title', '//meta[@name="description"]', '//meta[@name="robots"]',
            '//meta[@property="og:title"]', '//meta[@property="og:url"]', '//meta[@name="twitter:description"]',
        ] as $selector) {
            $this->assertCount(0, $document->query($selector), $selector);
        }
        $this->assertSame('https://public.test/en/article', $this->canonical($document));
        $this->assertSame('https://public.test/image.jpg', $this->content($document, '//meta[@property="og:image"]'));
        $this->assertSame('summary_large_image', $this->content($document, '//meta[@name="twitter:card"]'));
    }
}

class SeoHeadArticle extends Model
{
    use HasSeo {
        getMetaTags as private traitMetaTags;
    }

    protected $table = 'seo_head_articles';
    protected $guarded = [];
    public $timestamps = false;
    public int $metadataCalls = 0;
    public array $publicUrls = ['it' => 'https://public.test/articolo', 'en' => 'https://public.test/en/article'];

    public function getMetaTags(?string $locale = null): array
    {
        $this->metadataCalls++;

        return $this->traitMetaTags($locale);
    }

    public function getDefaultSeoUrl(?string $locale = null): ?string
    {
        return $this->publicUrls[$locale ?? app()->getLocale()] ?? null;
    }
}
