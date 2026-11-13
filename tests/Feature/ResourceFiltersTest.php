<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\ResourceFilters\IdentifierFilterScope;
use IlBronza\CRUD\ResourceFilters\ResourceFilterHelper;
use IlBronza\CRUD\ResourceFilters\ResourceFilters;
use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetIndexController;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\Datatables\Datatables;
use IlBronza\Menu\Helpers\MenuPayloadBuilder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class ResourceFiltersTest extends TestCase
{
    private const URL = '/crud/resource-filters';
    private const SESSION_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';
    private const SESSION_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    protected function defineRoutes($router)
    {
        $router->get('/stateless-items', fn () => ProjectFilterGadget::orderBy('id')->pluck('id'));
        $router->middleware('web')->group(function () use ($router) {
            $router->get('/login', fn () => 'login')->name('login');
            $router->get('/items', fn () => ProjectFilterGadget::orderBy('id')->pluck('id'));
            $router->get('/bound/{gadget}', fn (ProjectFilterGadget $gadget) => (string) $gadget->getKey());
            $router->get('/filters-index', [
                ResourceIndexController::class,
                'index',
            ])->name('filters.index');
            $router->post('/filters-index', [
                ResourceIndexController::class,
                'index',
            ])->name('filters.index.post');
            $router->post('/maintenance', fn () => ProjectFilterGadget::count());
            $router->get('/sync-job', function () {
                Bus::dispatchSync(new CountFilterGadgets());
                return [
                    'job' => CountFilterGadgets::$count,
                    'page' => ProjectFilterGadget::count(),
                ];
            });
            $router->get('/preloaded-rows', function () {
                $table = new class extends Datatables {
                    public function calculateData()
                    {
                        return ProjectFilterGadget::orderBy('id')->pluck('name')->all();
                    }
                };
                $table->setName('things');
                return $table->prepareCachedData();
            });
            $router->get('/filter-menu', function (ResourceFilters $filters) {
                // Testbench simulates several HTTP requests inside one container.
                app()->forgetInstance('menu');
                $menu = app('menu');
                $html = $menu->render();
                $mobile = $menu->renderOffcanvas();
                $progressive = (new MenuPayloadBuilder($menu))->root();
                return [
                    'html' => $html,
                    'mobile' => $mobile,
                    'browser' => $progressive['browserCache']['key'],
                    'items' => $menu->getNavbars()->first()->getAllButtons()->pluck('name'),
                    'header' => $filters->header()?->render(),
                ];
            });
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAsSuperadmin()->active = true;
        $this->withCredentials()->withCookie(config('session.cookie'), self::SESSION_A);
        config([
            'cache.default' => 'array',
            'session.driver' => 'array',
            'menu.serviceProviders' => [
                'CRUDMenuUtilities',
            ],
            'menu.usesCache' => true,
            'resource-filters.resources.things' => [
                'model' => FilterGadget::class,
                'helper' => GadgetFilters::class,
                'scope' => GadgetScope::class,
                'label' => 'Strumenti',
                'enabled' => true,
            ],
        ]);
        Schema::table('gadgets', function (Blueprint $table) {
            $table->boolean('visible')->default(true);
            $table->boolean('enabled')->default(true);
            $table->integer('level')->default(0);
        });
        foreach ([
            [
                1,
                'Uno',
                0,
                false,
                true,
            ],
            [
                2,
                'Due',
                1,
                true,
                true,
            ],
            [
                3,
                'Segreto',
                0,
                false,
                false,
            ],
        ] as [
            $id,
            $name,
            $level,
            $enabled,
            $visible,
        ]) {
            DB::table('gadgets')->insert(compact('id', 'name', 'level', 'enabled', 'visible'));
        }
    }

    public function test_edit_shows_current_values_and_keeps_other_visible_options_selectable(): void
    {
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
                '2',
                '1',
            ],
            'unknown' => 'ignored',
        ])->assertNoContent()->assertSessionHas('crud.resource_filters.things', [
            'ids' => [
                '1',
                '2',
            ],
        ]);
        $this->putJson(self::URL.'/things', [
            'ids' => [
                1,
            ],
        ])->assertNoContent();
        $this->get('/items')->assertExactJson([
            1,
        ]);
        $form = $this->get(self::URL.'/things?iframed=1')->assertOk()
            ->assertSee('Uno')->assertSee('Due')->assertDontSee('Segreto')
            ->assertDontSee('crud-resource-filter-things')->assertDontSee('fieldsets.Strumenti')->assertSee('Azzera risorsa');
        $dom = new \DOMDocument();
        @$dom->loadHTML($form->getContent());
        $selected = (new \DOMXPath($dom))->query('//select[@name="ids[]"]/option[@selected]');
        $this->assertCount(1, $selected);
        $this->assertSame('1', $selected->item(0)->getAttribute('value'));

        $this->putJson(self::URL.'/things', [
            'ids' => [
                3,
            ],
            'level' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('ids');
        $this->get('/items')->assertExactJson([
            1,
        ]);
        $error = $this->put(self::URL.'/things?iframed=1', [
            'level' => [
                'invalid',
            ],
        ])->assertStatus(303)->assertSessionHasErrors('level');
        $this->get($error->headers->get('Location'))->assertOk()->assertSee('Due');
        $this->get('/items')->assertExactJson([
            1,
        ]);
        $this->get('/bound/2')->assertNotFound();
        $this->getJson('/stateless-items')->assertExactJson([
            1,
            2,
        ]);
        $this->post('/maintenance')->assertContent('2');
        $this->get('/sync-job')->assertExactJson([
            'job' => 2,
            'page' => 1,
        ]);
        $this->assertSame(2, ProjectFilterGadget::count());
        $this->assertSame(3, DB::table('gadgets')->count());
    }

    public function test_resource_replacement_clear_and_resets_preserve_other_session_data(): void
    {
        config([
            'resource-filters.resources.other' => array_replace(config('resource-filters.resources.things'), [
                'helper' => OnlyLevelFilters::class,
            ]),
        ]);
        $this->withSession([
            'unrelated' => 'keep',
        ]);
        $this->putJson(self::URL.'/other', [
            'level' => 0,
            'ids' => [
                2,
            ],
        ])->assertNoContent()->assertSessionHas('crud.resource_filters.other', [
            'level' => 0,
        ]);
        $this->putJson(self::URL.'/things', [
            'enabled' => false,
            'level' => '0',
        ])->assertNoContent()->assertSessionHas('crud.resource_filters.things', [
            'enabled' => false,
            'level' => 0,
        ]);
        $this->get('/items')->assertExactJson([
            1,
        ]);
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
            ],
        ])->assertNoContent()->assertSessionMissing('crud.resource_filters.things.level');
        $this->get('/items')->assertExactJson([]);
        $this->deleteJson(self::URL.'/other')->assertNoContent();
        $this->get('/items')->assertExactJson([
            2,
        ]);
        $this->putJson(self::URL.'/things', [
            'ids' => [],
            'level' => '',
        ])->assertNoContent()->assertSessionMissing(ResourceFilters::SESSION_KEY);
        $this->get('/items')->assertExactJson([
            1,
            2,
        ]);
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
            ],
        ])->assertNoContent();
        $this->deleteJson(self::URL)->assertNoContent()->assertSessionHas('unrelated', 'keep')
            ->assertSessionMissing(ResourceFilters::SESSION_KEY);
    }

    public function test_project_transform_removes_stale_keys_before_binding_and_building_the_form(): void
    {
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
            ],
        ])->assertNoContent();
        config([
            'resource-filters.resources.things.helper' => RemoveIdsFilters::class,
        ]);
        $this->get('/bound/1')->assertOk()->assertSessionMissing(ResourceFilters::SESSION_KEY);
        $this->get(self::URL.'/things?iframed=1')->assertOk()
            ->assertSee('name="level"', false)->assertDontSee('name="ids[]"', false);
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
            ],
            'level' => 0,
        ])->assertNoContent()->assertSessionHas('crud.resource_filters.things', [
            'level' => 0,
        ]);
        config([
            'resource-filters.resources.things.enabled' => false,
        ]);
        $this->get('/items')->assertExactJson([
            1,
            2,
        ])->assertSessionMissing(ResourceFilters::SESSION_KEY);
        $this->get(self::URL.'/things')->assertNotFound();
        $this->assertNull($this->get('/filter-menu')->assertOk()->json('header'));
        config([
            'resource-filters.resources' => [],
        ]);
        $this->assertStringNotContainsString('crud-resource-filter-', $this->get('/filter-menu')->json('html'));
    }

    public function test_index_and_desktop_mobile_progressive_menu_caches_follow_session_state(): void
    {
        $headers = [
            'X-Requested-With' => 'XMLHttpRequest',
        ];
        $this->get('/preloaded-rows')->assertExactJson([
            'Uno',
            'Due',
        ]);
        $initial = $this->get('/filter-menu')->assertOk()->json();
        $this->assertNull($initial['header']);
        $this->getJson('/filters-index', $headers)->assertOk()->assertJsonCount(2, 'data');
        $this->putJson(self::URL.'/things', [
            'ids' => [
                2,
            ],
        ])->assertNoContent();
        $this->getJson('/filters-index', $headers)->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.0', 'Due');
        $this->get('/preloaded-rows')->assertExactJson([
            'Due',
        ]);
        $filtered = $this->get('/filter-menu')->assertOk()->json();
        $this->assertSame(1, array_count_values($filtered['items'])['crud-resource-filter-things']);
        $this->assertStringContainsString('Strumenti', $filtered['mobile']);
        $this->assertStringContainsString('Due', $filtered['header']);
        $dom = new \DOMDocument();
        @$dom->loadHTML($filtered['header']);
        $xpath = new \DOMXPath($dom);
        $globalForm = $xpath->query('//form[not(ancestor::details)]')->item(0);
        $this->assertNotNull($globalForm);
        $this->assertSame(route('crud.resourceFilters.resetAll'), $globalForm->getAttribute('action'));
        $this->assertSame(__('crud::resource-filters.reset_all'), $xpath->evaluate('string(.//button/@aria-label)', $globalForm));
        $this->assertSame('DELETE', $xpath->evaluate('string(.//input[@name="_method"]/@value)', $globalForm));
        $this->assertNotSame($initial['html'], $filtered['html']);
        $this->assertNotSame($initial['browser'], $filtered['browser']);
        $this->postJson('/filters-index', [], $headers)->assertOk()->assertJsonCount(1, 'data');
        // Testbench reuses the Store instance. Drop its in-memory attributes while
        // keeping the handler (the two sessions still have independent saved data).
        app('session.store')->flush();
        $this->withCookie(config('session.cookie'), self::SESSION_B);
        $this->getJson('/filters-index', $headers)->assertOk()->assertJsonCount(2, 'data');
        app('session.store')->flush();
        $this->withCookie(config('session.cookie'), self::SESSION_A);
        $this->get('/items')->assertExactJson([
            2,
        ]);
        $this->deleteJson(self::URL)->assertNoContent();
        $this->getJson('/filters-index', $headers)->assertOk()->assertJsonCount(2, 'data');
        $this->get('/preloaded-rows')->assertExactJson([
            'Uno',
            'Due',
        ]);
        $reset = $this->get('/filter-menu')->assertOk()->json();
        $this->assertSame($initial['browser'], $reset['browser']);
        $this->assertNull($reset['header']);
    }

    public function test_header_only_placement_and_resource_ability_are_observed(): void
    {
        config([
            'resource-filters.navbar' => false,
            'resource-filters.resources.things.ability' => 'consult-gadgets',
        ]);
        Gate::define('consult-gadgets', fn () => true);
        $ui = $this->get('/filter-menu')->assertOk()->json();
        $this->assertStringContainsString('Strumenti', $ui['header']);
        $this->assertStringNotContainsString('crud-resource-filter-', $ui['html']);
        $this->putJson(self::URL.'/things', [
            'ids' => [
                1,
            ],
        ])->assertNoContent();
        Gate::define('consult-gadgets', fn () => false);
        $this->get(self::URL.'/things')->assertNotFound();
        $this->get('/items')->assertExactJson([
            1,
            2,
        ])->assertSessionMissing(ResourceFilters::SESSION_KEY);
    }

    public function test_form_mutations_require_a_browser_csrf_token(): void
    {
        // Laravel bypasses VerifyCsrfToken in the testing environment.
        $this->app->instance('env', 'local');
        $this->put(self::URL.'/things', [
            'ids' => [
                1,
            ],
        ])->assertStatus(419);
        $this->delete(self::URL)->assertStatus(419);
        $this->get('/items')->assertExactJson([
            1,
            2,
        ]);
    }

    public function test_registry_and_authentication_protect_endpoints(): void
    {
        $this->get(self::URL.'/missing')->assertNotFound();
        $this->putJson(self::URL.'/missing')->assertNotFound();
        $this->deleteJson(self::URL.'/missing')->assertNotFound();
        $this->get(self::URL)->assertStatus(405);
        auth()->logout();
        $this->putJson(self::URL.'/things')->assertUnauthorized();
        $this->deleteJson(self::URL)->assertUnauthorized();
    }
}

class FilterGadget extends Gadget
{
    use CRUDCacheTrait;

    public function userCanShow(\IlBronza\AccountManager\Models\User $user = null)
    {
        return $this instanceof ProjectFilterGadget && parent::userCanShow($user);
    }

    public static function gpc(): string
    {
        return ProjectFilterGadget::class;
    }

    protected static function booted(): void
    {
        static::addGlobalScope('visibility', fn (Builder $query) => $query->where('visible', true));
    }
}

class ProjectFilterGadget extends FilterGadget
{
}

class GadgetFilters extends ResourceFilterHelper
{
    public function getFilters(): array
    {
        return [
            'ids' => $this->identifiers('Selezione'),
        ];
    }

    public function transformFilters(array $filters): array
    {
        $filters['level'] = [
            'type' => 'number',
            'label' => 'Livello',
            'cast' => 'integer',
        ];
        $filters['enabled'] = [
            'type' => 'select',
            'label' => 'Abilitato',
            'cast' => 'boolean',
            'options' => fn () => [
                0 => 'No',
                1 => 'Sì',
            ],
        ];
        return $filters;
    }
}

class RemoveIdsFilters extends GadgetFilters
{
    public function transformFilters(array $filters): array
    {
        unset($filters['ids']);
        return parent::transformFilters($filters);
    }
}

class OnlyLevelFilters extends ResourceFilterHelper
{
    public function getFilters(): array
    {
        return [
            'level' => [
                'type' => 'number',
                'label' => 'Livello',
                'cast' => 'integer',
            ],
        ];
    }
}

class GadgetScope extends IdentifierFilterScope
{
    public function constrain(Builder $query, array $values): void
    {
        parent::constrain($query, $values);
        foreach ([
            'level',
            'enabled',
        ] as $column)
            if (array_key_exists($column, $values))
                $query->where($query->qualifyColumn($column), $values[$column]);
    }
}

class ResourceIndexController extends GadgetIndexController
{
    public $modelClass = ProjectFilterGadget::class;

    public function getIndexElements()
    {
        return $this->getModelClass()::orderBy('id')->get();
    }
}

class CountFilterGadgets implements ShouldQueue
{
    use \Illuminate\Bus\Queueable;

    public static int $count = 0;

    public function handle(): void
    {
        static::$count = ProjectFilterGadget::count();
    }
}
