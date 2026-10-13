<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\Tests\Fixtures\Controllers\GadgetIndexController;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use Illuminate\Auth\GenericUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class CRUDIndexDataCacheTest extends TestCase
{
    protected function defineRoutes($router)
    {
        $router->get('cached-gadgets/{section?}', [CachedGadgetIndexController::class, 'index'])->name('cached-gadgets.index');
        $router->get('uncached-gadgets', [UncachedGadgetIndexController::class, 'index'])->name('uncached-gadgets.index');
        $router->get('provided-gadgets', [ProvidedGadgetIndexController::class, 'index'])->name('provided-gadgets.index');
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cache.default' => 'array', 'cache.stores.array.serialize' => true]);
        $this->withoutMiddleware();
        $this->actingAsSuperadmin();
        CachedGadgetIndexController::$loads = CachedGadgetIndexController::$beforeCalls = 0;
        CachedIndexGadget::$renders = 0;
    }

    private function key(): string
    {
        return CachedIndexGadget::getIndexCacheKey().'_jsonData';
    }

    private function ajax(string $url = '/cached-gadgets')
    {
        return $this->getJson($url, ['X-Requested-With' => 'XMLHttpRequest']);
    }

    private function gadget(string $name = 'First'): CachedIndexGadget
    {
        return CachedIndexGadget::create(['name' => $name, 'description' => 'Description']);
    }

    public function test_cache_hit_skips_models_and_cell_rendering_and_ignores_ajax_nonce(): void
    {
        $this->gadget();
        $first = $this->ajax('/cached-gadgets?_=1&cachedtablekey=first')->assertOk()->json();
        $renders = CachedIndexGadget::$renders;
        $this->assertGreaterThan(0, $renders);
        DB::enableQueryLog();
        DB::flushQueryLog();

        $second = $this->ajax('/cached-gadgets?_=2&cachedtablekey=second')->assertOk()->json();

        $this->assertSame($first, $second);
        $this->assertSame(1, CachedGadgetIndexController::$loads);
        $this->assertSame($renders, CachedIndexGadget::$renders);
        $this->assertSame(2, CachedGadgetIndexController::$beforeCalls);
        $this->assertSame([], DB::getQueryLog());
        $this->assertTrue(cache()->has($this->key()));
    }

    public function test_shared_data_is_reused_across_users(): void
    {
        $this->gadget();
        $first = $this->ajax()->assertOk()->json();
        $this->actingAs(new GenericUser(['id' => 987]));
        $this->assertSame($first, $this->ajax()->assertOk()->json());
        $this->assertSame(1, CachedGadgetIndexController::$loads);
    }

    public function test_empty_index_is_cached_and_creating_a_model_invalidates_it(): void
    {
        $this->ajax()->assertOk()->assertJsonPath('data', []);
        $this->ajax()->assertOk()->assertJsonPath('data', []);
        $this->assertSame(1, CachedGadgetIndexController::$loads);
        $this->gadget();
        $this->assertFalse(cache()->has($this->key()));
        $this->ajax()->assertOk()->assertJsonPath('data.0.0', 'First');
    }

    public function test_touch_refreshes_the_response_and_save_delete_and_restore_invalidate_it(): void
    {
        $gadget = $this->gadget();
        $this->ajax()->assertOk();
        DB::table('gadgets')->where('id', $gadget->id)->update(['name' => 'Changed']);
        $this->ajax()->assertOk()->assertJsonPath('data.0.0', 'First');
        $gadget->touch();
        $this->assertFalse(cache()->has($this->key()));
        $this->ajax()->assertOk()->assertJsonPath('data.0.0', 'Changed');

        $gadget->name = 'Saved';
        $gadget->save();
        $this->assertFalse(cache()->has($this->key()));
        $this->ajax()->assertOk()->assertJsonPath('data.0.0', 'Saved');
        $gadget->delete();
        $this->assertFalse(cache()->has($this->key()));
        $this->ajax()->assertOk()->assertJsonPath('data', []);
        $gadget->restore();
        $this->assertFalse(cache()->has($this->key()));
        $this->ajax()->assertOk()->assertJsonPath('data.0.0', 'Saved');
    }

    public function test_use_index_cache_false_and_models_without_the_trait_remain_live(): void
    {
        $this->gadget();
        $this->ajax('/uncached-gadgets')->assertOk();
        $this->ajax('/uncached-gadgets')->assertOk();
        $this->assertSame(2, CachedGadgetIndexController::$loads);
        $this->assertFalse(cache()->has($this->key()));

        config(['testing.index_model' => Gadget::class]);
        $this->ajax()->assertOk();
        $this->ajax()->assertOk();
        $this->assertSame(4, CachedGadgetIndexController::$loads);
        $this->assertFalse(cache()->has($this->key()));
    }

    #[DataProvider('partialRequests')]
    public function test_partial_updates_do_not_read_or_overwrite_the_full_index(string $query): void
    {
        $one = $this->gadget();
        $this->gadget('Second');
        $this->ajax()->assertOk()->assertJsonCount(2, 'data');
        $cached = cache()->get($this->key());
        DB::table('gadgets')->where('id', $one->id)->update(['name' => 'Live']);

        $this->ajax('/cached-gadgets?'.$query)->assertOk()->assertJsonPath('data.0.0', 'Live');
        $this->assertSame($cached, cache()->get($this->key()));
        $this->assertSame(2, CachedGadgetIndexController::$loads);
    }

    public static function partialRequests(): array
    {
        return [['rowId=1'], ['rowIds[]=1'], ['ibeditor=1']];
    }

    public function test_other_fields_filters_and_routes_replace_the_single_entry_without_collisions(): void
    {
        $this->gadget();
        $this->gadget('Second');
        $this->ajax()->assertOk()->assertJsonCount(2, 'data');
        $this->ajax('/cached-gadgets?name=Second')->assertOk()->assertJsonPath('data.0.0', 'Second')->assertJsonCount(1, 'data');
        $this->ajax('/cached-gadgets/another-index')->assertOk()->assertJsonCount(2, 'data');
        config(['testing.index_fields' => ['description' => 'flat', 'name' => 'flat']]);
        $this->ajax('/cached-gadgets/another-index')->assertOk()->assertJsonPath('data.0.0', 'Description');
        $this->assertSame(4, CachedGadgetIndexController::$loads);
        $this->assertTrue(cache()->has($this->key()));
    }

    public function test_explicit_element_providers_keep_the_table_available_and_do_not_cache(): void
    {
        $this->ajax('/provided-gadgets')->assertOk()->assertJsonPath('data', []);
        $this->assertFalse(cache()->has($this->key()));
    }

    public function test_all_model_index_aliases_and_json_entries_are_invalidated_together(): void
    {
        foreach (CachedIndexGadget::getIndexCacheKeys() as $key) {
            cache()->forever($key, 'models');
            cache()->forever($key.'_jsonData', 'cells');
        }
        cache()->forever('unrelated', 'keep');
        $this->gadget();
        foreach (CachedIndexGadget::getIndexCacheKeys() as $key) {
            $this->assertFalse(cache()->has($key));
            $this->assertFalse(cache()->has($key.'_jsonData'));
        }
        $this->assertSame('keep', cache()->get('unrelated'));
    }
}

class CachedIndexGadget extends Gadget
{
    use CRUDCacheTrait;

    public static int $renders = 0;

    public function getDescriptionAttribute($value)
    {
        static::$renders++;
        return $value;
    }

    public static function getIndexCacheKeys(): array
    {
        return [static::getIndexCacheKey(), 'another-index-alias'];
    }
}

class CachedGadgetIndexController extends GadgetIndexController
{
    public $modelClass = CachedIndexGadget::class;
    public static int $loads = 0;
    public static int $beforeCalls = 0;

    public function getModelClass(): string
    {
        return config('testing.index_model', $this->modelClass);
    }

    public function beforeIndex(): void
    {
        static::$beforeCalls++;
    }

    public function getIndexFieldsArray()
    {
        return ['fields' => config('testing.index_fields', ['name' => 'flat', 'description' => 'flat'])];
    }

    public function getIndexElements()
    {
        static::$loads++;
        return $this->getModelClass()::query()
            ->when(request('name'), fn ($query, $name) => $query->where('name', $name))->get();
    }
}

class UncachedGadgetIndexController extends CachedGadgetIndexController
{
    public bool $useIndexCache = false;
}

class ProvidedGadgetIndexController extends CachedGadgetIndexController
{
    public function index(Request $request)
    {
        $response = $this->_index($request, elementsGetter: fn () => collect());
        // Streaming callers still need the initialized table after _index().
        $this->getTable()->setElements(collect());
        return $response;
    }
}
