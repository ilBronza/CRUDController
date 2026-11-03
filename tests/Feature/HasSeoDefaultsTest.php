<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Helpers\Seo\SeoFormHelper;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\Model\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class HasSeoDefaultsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $migration = require __DIR__.'/../../database/migrations/2026_10_22_000000_create_crud_seo_table.php';
        $migration->up();

        Schema::create('seo_default_articles', static function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
        });
    }

    public function test_default_seo_url_uses_get_public_url_when_available(): void
    {
        $article = SeoDefaultArticle::create(['name' => 'Wash']);

        $this->assertSame('https://public.test/it/wash', $article->getDefaultSeoUrl('it'));
        $this->assertSame('https://public.test/it/wash', $article->getMetaTags('it')['canonical']);
    }

    public function test_open_graph_defaults_to_article_type_site_name_and_share_image(): void
    {
        config(['uikittemplate.head.siteName' => 'Example Site', 'app.name' => 'Fallback']);
        $article = SeoDefaultArticle::create(['name' => 'Wash']);

        $meta = $article->getMetaTags('it');
        $this->assertSame('article', $meta['og:type']);
        $this->assertSame('Example Site', $meta['og:site_name']);
        $this->assertSame('https://cdn.test/wash.jpg', $meta['og:image']);
        $this->assertSame(1200, $meta['og:image:width']);
        $this->assertSame(630, $meta['og:image:height']);
        $this->assertSame('Wash', $meta['og:image:alt']);
        $this->assertSame('summary_large_image', $meta['twitter:card']);
        $this->assertSame('https://cdn.test/wash.jpg', $meta['twitter:image']);
        $this->assertArrayNotHasKey('twitter:image:alt', $meta);
    }

    public function test_save_from_request_persists_seo_and_is_noop_without_payload(): void
    {
        $article = SeoDefaultArticle::create(['name' => 'Wash']);

        $this->assertNull(SeoFormHelper::saveFromRequest($article, Request::create('/', 'POST')));

        $seo = SeoFormHelper::saveFromRequest($article, Request::create('/', 'POST', [
            'seo' => [
                'locale' => 'it',
                'title' => 'Titolo SEO',
                'description' => 'Descrizione SEO',
            ],
        ]));

        $this->assertSame('Titolo SEO', $seo->title);
        $this->assertDatabaseHas('crud_seo', [
            'seoable_type' => $article->getMorphClass(),
            'seoable_id' => (string) $article->getKey(),
            'locale' => 'it',
            'title' => 'Titolo SEO',
            'description' => 'Descrizione SEO',
        ]);
        $this->assertSame('Titolo SEO', $article->fresh()->getMetaTags('it')['title']);
    }
}

class SeoDefaultArticle extends Model
{
    use HasSeo;

    protected $table = 'seo_default_articles';
    protected $guarded = [];
    public $timestamps = false;

    public function getPublicUrl(?string $locale = null): ?string
    {
        return 'https://public.test/'.($locale ?? 'it').'/wash';
    }

    public function getDefaultSeoImage(?string $locale = null): ?array
    {
        return [
            'url' => 'https://cdn.test/wash.jpg',
            'width' => 1200,
            'height' => 630,
            'alt' => $this->name,
        ];
    }
}
