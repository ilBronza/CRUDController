<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Catalog\Catalog;
use IlBronza\CRUD\Catalog\CatalogItem;
use IlBronza\CRUD\Catalog\Filters\BooleanFilter;
use IlBronza\CRUD\Catalog\Filters\FilterFactory;
use IlBronza\CRUD\Interfaces\CatalogOptionsProviderInterface;
use IlBronza\CRUD\Tests\Fixtures\Models\CatalogCustomer;
use IlBronza\CRUD\Tests\Fixtures\Models\CatalogDocument;
use IlBronza\CRUD\Tests\Fixtures\Providers\CustomerCatalogParameters;
use IlBronza\CRUD\Tests\Fixtures\Providers\DocumentCatalogParameters;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;

class CatalogTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->get('/catalog-documents', function (Request $request) {
            return view('catalog-fixtures::catalog', ['catalog' => Catalog::make(new DocumentCatalogParameters(), $request)]);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        view()->addNamespace('catalog-fixtures', __DIR__ . '/../Fixtures/views');
        Schema::create('catalog_customers', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('company_name');
            $table->string('region');
        });
        Schema::create('catalog_documents', function (Blueprint $table) {
            $table->id();
            $table->integer('tenant_id');
            $table->string('subject')->nullable();
            $table->text('summary')->nullable();
            $table->string('status');
            $table->decimal('amount');
            $table->boolean('approved');
            $table->unsignedBigInteger('customer_id')->nullable();
        });
    }

    private function document(array $attributes = []): CatalogDocument
    {
        return CatalogDocument::create(array_merge([
            'tenant_id' => 1, 'subject' => 'Rapporto', 'status' => 'draft', 'amount' => 20, 'approved' => true,
        ], $attributes));
    }

    private function catalog(array $parameters = [], ?DocumentCatalogParameters $definition = null): Catalog
    {
        return Catalog::make($definition ?? new DocumentCatalogParameters(), Request::create('/catalog-documents', 'GET', $parameters));
    }

    public function test_combines_search_multiple_range_and_boolean_in_the_database(): void
    {
        $match = $this->document(['subject' => 'Alpha', 'amount' => 25]);
        $this->document(['subject' => 'Alpha', 'status' => 'sent', 'amount' => 70]);
        $this->document(['subject' => 'Alpha', 'amount' => 25, 'approved' => false]);
        $this->document(['subject' => 'Beta', 'amount' => 25]);
        $this->document(['subject' => 'Alpha', 'tenant_id' => 2, 'amount' => 25]);

        $catalog = $this->catalog(['filters' => [
            'search' => ' Alpha ', 'status' => ['draft', 'sent', 'draft'],
            'amount' => ['min' => '20', 'max' => '30'], 'approved' => '1',
        ]]);
        $this->assertSame(1, $catalog->results->total());
        $this->assertSame($match->id, $catalog->results->first()->key);
        $this->assertSame(['draft', 'sent'], $catalog->values['status']);
    }

    public function test_multiple_values_use_or_and_false_and_zero_remain_active(): void
    {
        $this->document(['approved' => false, 'amount' => 0]);
        $this->document(['approved' => false, 'amount' => 0, 'status' => 'sent']);
        $this->document(['approved' => true, 'amount' => 0]);
        $this->document(['approved' => false, 'amount' => 1]);
        $catalog = $this->catalog(['filters' => ['status' => ['draft', 'sent'], 'approved' => '0', 'amount' => ['max' => '0']]]);
        $this->assertSame(2, $catalog->results->total());
        $this->assertFalse($catalog->values['approved']);
        $this->assertStringContainsString('Approvato: No', implode(' ', array_column($catalog->activeFilters, 'label')));
    }

    public function test_search_treats_like_wildcards_literally_and_does_not_escape_the_base_scope(): void
    {
        $match = $this->document(['subject' => 'Offerta 10%_!', 'summary' => '']);
        $this->document(['subject' => 'Offerta 1000']);
        $this->document(['tenant_id' => 2, 'summary' => '10%_!']);
        $catalog = $this->catalog(['filters' => ['search' => '10%_!']]);
        $this->assertSame(1, $catalog->results->total());
        $this->assertSame($match->id, $catalog->results->first()->key);
    }

    public function test_scope_filters_can_query_relations(): void
    {
        $north = CatalogCustomer::create(['tenant_id' => 1, 'company_name' => 'Nord Srl', 'region' => 'north']);
        $south = CatalogCustomer::create(['tenant_id' => 1, 'company_name' => 'Sud Srl', 'region' => 'south']);
        $match = $this->document(['customer_id' => $north->id]);
        $this->document(['customer_id' => $south->id]);
        $this->document(['customer_id' => $north->id, 'tenant_id' => 2]);
        $catalog = $this->catalog(['filters' => ['regions' => ['north']]]);
        $this->assertSame(1, $catalog->results->total());
        $this->assertSame($match->id, $catalog->results->first()->key);
    }

    public function test_custom_filter_and_or_scope_stay_within_original_scope(): void
    {
        $this->document(['amount' => 50, 'status' => 'sent']);
        $this->document(['amount' => 10, 'status' => 'draft']);
        $this->document(['tenant_id' => 2, 'amount' => 50, 'status' => 'sent']);
        $custom = new class('large') extends BooleanFilter {
            protected function applyValue(Builder $query, mixed $value): void
            {
                $query->orWhere('amount', $value ? '>=' : '<', 50);
            }
        };
        $definition = new class($custom) extends DocumentCatalogParameters {
            public function __construct(private BooleanFilter $custom) {}
            public function getFiltersParameters(): array
            {
                return [
                    'large' => $this->custom,
                    'status' => ['type' => 'multiple', 'scope' => 'anyStatus', 'options' => ['draft' => 'Bozza', 'sent' => 'Inviato']],
                ];
            }
        };
        $catalog = $this->catalog(['filters' => ['large' => '1', 'status' => ['draft', 'sent']]], $definition);
        $this->assertSame(1, $catalog->results->total());
    }

    public function test_dynamic_options_derive_from_original_scope_not_selected_filters(): void
    {
        $this->document(['status' => 'draft']);
        $this->document(['status' => 'sent']);
        $this->document(['tenant_id' => 2, 'status' => 'secret']);
        $provider = new class implements CatalogOptionsProviderInterface {
            public function options(Builder $baseQuery): array
            {
                return $baseQuery->reorder()->distinct()->pluck('status', 'status')->all();
            }
        };
        $definition = new class($provider) extends DocumentCatalogParameters {
            public function __construct(private CatalogOptionsProviderInterface $provider) {}
            public function getFiltersParameters(): array
            {
                return ['status' => ['type' => 'multiple', 'column' => 'status', 'options' => $this->provider]];
            }
        };
        $catalog = $this->catalog(['filters' => ['status' => ['draft']]], $definition);
        $this->assertSame(['draft' => 'draft', 'sent' => 'sent'], $catalog->controls['status']['options']);
        $this->assertSame(1, $catalog->results->total());
        parse_str(parse_url($catalog->resetUrl(), PHP_URL_QUERY), $reset);
        $this->assertSame(2, $this->catalog($reset, $definition)->results->total());
    }

    public function test_pagination_is_deterministic_and_counts_the_entire_filtered_set(): void
    {
        $ids = [];
        for ($i = 0; $i < 5; $i++)
            $ids[] = $this->document()->id;
        $this->document(['tenant_id' => 2]);
        DB::enableQueryLog();
        $catalog = $this->catalog(['page' => '2', 'sort' => 'amount_desc']);
        $this->assertSame(5, $catalog->results->total());
        $this->assertSame(array_slice($ids, 2, 2), $catalog->results->getCollection()->pluck('key')->all());
        $this->assertStringContainsString('limit 2 offset 2', implode(' ', array_column(DB::getQueryLog(), 'query')));
        $this->assertStringContainsString('sort=amount_desc', $catalog->results->nextPageUrl());
    }

    public function test_removing_one_selected_value_preserves_other_values_and_resets_page(): void
    {
        $catalog = $this->catalog(['page' => '3', 'sort' => 'amount_desc', 'filters' => ['status' => ['draft', 'sent'], 'approved' => '0']]);
        parse_str(parse_url($catalog->withoutFilter('status', 'draft'), PHP_URL_QUERY), $parameters);
        $this->assertSame(['sent'], $parameters['filters']['status']);
        $this->assertSame('0', $parameters['filters']['approved']);
        $this->assertSame('amount_desc', $parameters['sort']);
        $this->assertArrayNotHasKey('page', $parameters);
        $this->assertStringNotContainsString('filters', $catalog->resetUrl());
    }

    public function test_component_renders_missing_optional_data_and_preserves_get_state(): void
    {
        $this->document(['subject' => null]);
        $this->get('/catalog-documents?filters[approved]=1&sort=amount_desc&page=1')
            ->assertOk()->assertSee('Senza titolo')->assertSee('1 risultato')
            ->assertSee('method="get"', false)->assertSee('data-catalog-toggle', false)
            ->assertSee('value="amount_desc" selected', false)
            ->assertDontSee('name="page"', false);
    }

    public function test_customer_catalog_uses_a_different_model_and_custom_card(): void
    {
        CatalogCustomer::create(['tenant_id' => 1, 'company_name' => 'Acme', 'region' => 'north']);
        CatalogCustomer::create(['tenant_id' => 2, 'company_name' => 'Hidden', 'region' => 'north']);
        $catalog = Catalog::make(new CustomerCatalogParameters(), Request::create('/customers', 'GET', ['filters' => ['company' => 'Acme']]));
        $html = view('catalog-fixtures::catalog', compact('catalog'))->render();
        $this->assertSame(1, $catalog->results->total());
        $this->assertStringContainsString('custom-customer-card', $html);
        $this->assertStringContainsString('Acme', $html);
        $this->assertStringNotContainsString('Hidden', $html);
    }

    public function test_default_card_escapes_text_and_rejects_executable_urls(): void
    {
        $item = new CatalogItem(1, '<script>alert(1)</script>', '<b>Summary</b>', 'javascript:alert(1)', 'javascript:alert(1)', ['<b>badge</b>']);
        $html = view('crud::catalog.card', compact('item'))->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_explicit_context_survives_reset_removal_pagination_and_form_submission(): void
    {
        for ($i = 0; $i < 3; $i++)
            $this->document();
        $catalog = Catalog::make(new DocumentCatalogParameters(), Request::create('/catalog-documents', 'GET', [
            'project' => 'untrusted', 'filters' => ['approved' => '1'],
        ]), ['project' => 'authorized']);
        foreach ([$catalog->resetUrl(), $catalog->withoutFilter('approved'), $catalog->results->nextPageUrl()] as $url) {
            $this->assertStringContainsString('project=authorized', $url);
            $this->assertStringNotContainsString('untrusted', $url);
        }
        $this->assertStringContainsString('name="project" value="authorized"', view('catalog-fixtures::catalog', compact('catalog'))->render());
    }

    public function test_eager_loading_applies_only_to_current_page(): void
    {
        $customer = CatalogCustomer::create(['tenant_id' => 1, 'company_name' => 'Acme', 'region' => 'north']);
        for ($i = 0; $i < 3; $i++)
            $this->document(['customer_id' => $customer->id]);
        $definition = new class extends DocumentCatalogParameters {
            public function with(): array { return ['customer']; }
        };
        DB::enableQueryLog();
        $catalog = $this->catalog([], $definition);
        $queries = array_column(DB::getQueryLog(), 'query');
        $this->assertCount(3, $queries); // count, current page, eager-loaded customers
        $this->assertStringContainsString('catalog_customers', $queries[2]);
        $this->assertCount(2, $catalog->results);
    }

    public function test_empty_state_and_full_html_ajax_response(): void
    {
        $this->get('/catalog-documents', ['X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->assertOk()->assertSee('Nessun elemento corrisponde')->assertSee('data-crud-catalog', false);
    }

    #[DataProvider('invalidParameters')]
    public function test_invalid_parameters_return_422_without_redirecting(array $parameters): void
    {
        $this->get('/catalog-documents?' . http_build_query($parameters))->assertStatus(422);
    }

    public static function invalidParameters(): array
    {
        return [
            'filters scalar' => [['filters' => 'anything']],
            'unknown filter' => [['filters' => ['delete' => '1']]],
            'array search' => [['filters' => ['search' => ['x']]]],
            'long search' => [['filters' => ['search' => str_repeat('x', 201)]]],
            'scalar multiple' => [['filters' => ['status' => 'draft']]],
            'unknown option' => [['filters' => ['status' => ['secret']]]],
            'nested option' => [['filters' => ['status' => [['draft']]]]],
            'associative option' => [['filters' => ['status' => ['a' => 'draft']]]],
            'invalid boolean' => [['filters' => ['approved' => 'yes']]],
            'range reversed' => [['filters' => ['amount' => ['min' => '30', 'max' => '20']]]],
            'range negative' => [['filters' => ['amount' => ['min' => '-1']]]],
            'range infinite' => [['filters' => ['amount' => ['min' => '1e999']]]],
            'range unknown key' => [['filters' => ['amount' => ['column' => 'tenant_id']]]],
            'unknown sort' => [['sort' => 'tenant_id']],
            'array sort' => [['sort' => ['subject']]],
            'page zero' => [['page' => '0']],
            'page noninteger' => [['page' => '1.5']],
        ];
    }

    public function test_ajax_validation_exposes_errors_without_running_arbitrary_methods(): void
    {
        $this->getJson('/catalog-documents?filters[approved]=bad')->assertStatus(422)->assertJsonValidationErrors('filters.approved');
        $this->expectException(InvalidArgumentException::class);
        $definition = new class extends DocumentCatalogParameters {
            public function getFiltersParameters(): array
            {
                return ['danger' => ['type' => 'boolean', 'scope' => 'delete']];
            }
        };
        $this->catalog(['filters' => ['danger' => '1']], $definition);
    }

    public function test_factory_rejects_conflicting_targets(): void
    {
        $this->expectException(InvalidArgumentException::class);
        FilterFactory::makeMany(['status' => ['type' => 'multiple', 'column' => 'status', 'scope' => 'anyStatus']]);
    }
}
