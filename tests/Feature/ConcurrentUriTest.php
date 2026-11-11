<?php

namespace IlBronza\CRUD\Tests\Feature;

use IlBronza\CRUD\CRUDServiceProvider;
use IlBronza\CRUD\Middleware\CRUDConcurrentUrlAlert;
use IlBronza\CRUD\Middleware\CRUDParseComasAndDots;
use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use IlBronza\UikitTemplate\UikitTemplateServiceProvider;
use Illuminate\Auth\GenericUser;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ConcurrentUriTest extends TestCase
{
    private const FIRST = 'aaaaaaaaaaaaaaaaaaaaaaaa';
    private const SECOND = 'bbbbbbbbbbbbbbbbbbbbbbbb';

    protected function getPackageProviders($app)
    {
        return [CRUDServiceProvider::class, UikitTemplateServiceProvider::class];
    }

    protected function defineEnvironment($app)
    {
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('x', 32)));
        $app['config']->set('app.locale', 'it');
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('crud.useConcurrentRequestsAlert', true);
    }

    protected function defineRoutes($router)
    {
        $router->middleware(['web', 'CRUDConcurrentUrlAlert'])->group(function ($router) {
            foreach (['page', 'other'] as $path) {
                $router->get('concurrent-test/' . $path, fn () => view()->file(__DIR__ . '/../Fixtures/views/concurrent-uri.blade.php'));
            }
            $router->post('concurrent-test/operation', fn () => response()->json(['saved' => true]));
            $router->post('concurrent-test/iframe', fn () => view()->file(__DIR__ . '/../Fixtures/views/concurrent-uri.blade.php'));
        });
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->asUser(1);
    }

    private function asUser(int $id): void
    {
        $this->actingAs(new GenericUser(['id' => $id, 'name' => 'User ' . $id]), 'web');
    }

    private function checker(): ConcurrentUriChecker
    {
        return app(ConcurrentUriChecker::class);
    }

    private function page(string $path = 'page'): array
    {
        $response = $this->get('/concurrent-test/' . $path)->assertOk();
        $response->assertHeader('Cache-Control', 'no-store, private');
        $this->assertMatchesRegularExpression('/window.ibConcurrentUriConfig = (.*);/', $response->getContent());
        preg_match('/window.ibConcurrentUriConfig = (.*);/', $response->getContent(), $matches);

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    private function data(array $page, string $key = self::FIRST): array
    {
        return ['url' => $page['url'], 'token' => $page['token'], 'pageKey' => $key];
    }

    private function tick(array $page, string $key = self::FIRST)
    {
        return $this->postJson('/concurrent-uri/tick', $this->data($page, $key));
    }

    private function leave(array $page, string $key = self::FIRST)
    {
        return $this->postJson('/concurrent-uri/leave-page', $this->data($page, $key));
    }

    private function write(array $page, string $key = self::FIRST)
    {
        return $this->postJson('/concurrent-test/operation', ['name' => 'Draft'], [
            'X-CRUD-Concurrent-Token' => $page['token'],
            'X-CRUD-Page-Key' => $key,
        ]);
    }

    public function test_enabled_feature_preserves_the_number_parser_alias(): void
    {
        $aliases = app('router')->getMiddleware();
        $this->assertSame(CRUDParseComasAndDots::class, $aliases['CRUDParseComasAndDots']);
        $this->assertSame(CRUDConcurrentUrlAlert::class, $aliases['CRUDConcurrentUrlAlert']);
        $this->assertSame($this->checker(), app('concurrentUriChecker'));
    }

    public function test_rendering_does_not_claim_a_page_and_provides_safe_configuration(): void
    {
        $page = $this->page();
        $this->assertNull($this->checker()->check($page['url']));
        $this->assertSame(3000, $page['interval']);
        $this->assertSame(10000, $page['lifetime']);
        $this->assertArrayNotHasKey('pageKey', $page);
        $this->assertTrue($this->checker()->checksSameUserTabs());
    }

    public function test_first_tab_acquires_renews_and_performs_operations(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk()->assertJsonPath('status', 'gained');
        $this->travel(6)->seconds();
        $this->tick($page)->assertOk()->assertJsonPath('status', 'kept');
        $this->travel(6)->seconds();
        $this->write($page)->assertOk()->assertJsonPath('saved', true);
    }

    public function test_posted_iframe_html_initializes_the_client_without_acquiring_another_page(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $response = $this->post('/concurrent-test/iframe', [
            '_crud_concurrent_token' => $page['token'],
            '_crud_page_key' => self::FIRST,
            'iframed' => true,
        ])->assertOk()->assertHeader('Cache-Control', 'no-store, private');
        $response->assertSee('window.ibConcurrentUriConfig =', false);
        $response->assertSee('window.ibConcurrentUri =', false);
        $this->assertNull($this->checker()->check('concurrent-test/iframe'));
        $this->write($page)->assertOk();
    }

    public function test_posted_iframe_still_requires_the_owning_page_credentials(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->postJson('/concurrent-test/iframe', [
            '_crud_concurrent_token' => $page['token'],
            '_crud_page_key' => self::SECOND,
            'iframed' => true,
        ])->assertStatus(409);
        $this->postJson('/concurrent-test/iframe', ['iframed' => true])
            ->assertUnprocessable()->assertJsonValidationErrors(['token', 'pageKey']);
    }

    public function test_same_user_second_tab_conflicts_by_default_and_cannot_write(): void
    {
        $first = $this->page();
        $second = $this->page();
        $this->tick($first)->assertOk();
        $this->tick($second, self::SECOND)->assertOk()->assertJsonPath('status', 'morePages');
        $this->write($second, self::SECOND)->assertStatus(409);
        $this->write($first)->assertOk();
    }

    public function test_a_second_tab_cannot_release_the_owner(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->leave($page, self::SECOND)->assertOk()->assertExactJson(['released' => true]);
        $this->write($page)->assertOk();
    }

    public function test_disabled_same_user_check_allows_both_tabs_but_release_only_removes_one(): void
    {
        config(['crud.concurrentUriCheckSameUserTabs' => false]);
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->tick($page, self::SECOND)->assertOk()->assertJsonPath('status', 'kept');
        $this->write($page)->assertOk();
        $this->write($page, self::SECOND)->assertOk();
        $this->leave($page)->assertOk();
        $this->write($page)->assertStatus(409);
        $this->write($page, self::SECOND)->assertOk();
        $this->leave($page, self::SECOND)->assertOk();
        $this->assertNull($this->checker()->check($page['url']));
    }

    public function test_shared_tabs_expire_independently(): void
    {
        config(['crud.concurrentUriCheckSameUserTabs' => false]);
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->travel(6)->seconds();
        $this->tick($page, self::SECOND)->assertOk();
        $this->travel(5)->seconds();
        $this->write($page)->assertStatus(409);
        $this->write($page, self::SECOND)->assertOk();
    }

    public function test_other_users_conflict_even_when_same_user_check_is_disabled(): void
    {
        config(['crud.concurrentUriCheckSameUserTabs' => false]);
        $first = $this->page();
        $this->tick($first)->assertOk();
        $this->asUser(2);
        $second = $this->page();
        $this->tick($second, self::SECOND)->assertOk()
            ->assertJsonPath('status', 'notAllowed')->assertJsonPath('userData.id', 1);
        $this->write($second, self::SECOND)->assertStatus(409);
        $this->leave($second, self::SECOND)->assertOk();
        $this->asUser(1);
        $this->write($first)->assertOk();
    }

    public function test_responses_never_create_or_read_the_tick_endpoint_as_a_page(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk()->assertExactJson([
            'status' => 'gained', 'userData' => ['id' => 1],
            'message' => trans('crud::crud.theOtherUserClosedThisUriBetterToRefreshPageToSeeUpdates'),
        ]);
        $this->tick($page, self::SECOND)->assertOk()->assertJsonPath('status', 'morePages');
        $this->asUser(2);
        $other = $this->page();
        $this->tick($other, self::SECOND)->assertOk()->assertJsonPath('userData.id', 1);
        $this->assertNull($this->checker()->check('concurrent-uri/tick'));
    }

    public function test_release_allows_takeover_and_a_delayed_old_release_is_harmless(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->leave($page)->assertOk();
        $this->tick($page, self::SECOND)->assertOk()->assertJsonPath('status', 'gained');
        $this->leave($page)->assertOk();
        $this->write($page, self::SECOND)->assertOk();
        $this->write($page)->assertStatus(409);
    }

    public function test_expiry_blocks_writes_until_a_new_heartbeat_acquires_ownership(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->travel(11)->seconds();
        $this->write($page)->assertStatus(409);
        $this->tick($page, self::SECOND)->assertOk()->assertJsonPath('status', 'gained');
        $this->write($page)->assertStatus(409);
        $this->write($page, self::SECOND)->assertOk();
    }

    public function test_conflicting_heartbeats_do_not_extend_the_owners_lease(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->travel(6)->seconds();
        $this->tick($page, self::SECOND)->assertJsonPath('status', 'morePages');
        $this->travel(5)->seconds();
        $this->tick($page, self::SECOND)->assertJsonPath('status', 'gained');
    }

    public function test_write_requires_context_and_an_acquired_tab_even_in_shared_mode(): void
    {
        config(['crud.concurrentUriCheckSameUserTabs' => false]);
        $page = $this->page();
        $this->postJson('/concurrent-test/operation')->assertStatus(422);
        $this->write($page)->assertStatus(409);
        $this->tick($page)->assertOk();
        $this->write($page, self::SECOND)->assertStatus(409);
    }

    public function test_normal_forms_can_send_hidden_context_fields(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->post('/concurrent-test/operation', [
            '_crud_concurrent_token' => $page['token'], '_crud_page_key' => self::FIRST,
        ])->assertOk()->assertJsonPath('saved', true);
    }

    public function test_tampered_path_token_and_user_are_rejected(): void
    {
        $page = $this->page();
        $data = $this->data($page);
        $data['url'] = 'arbitrary/page';
        $this->postJson('/concurrent-uri/tick', $data)->assertForbidden();
        $data = $this->data($page);
        $data['token'] = 'tampered';
        $this->postJson('/concurrent-uri/tick', $data)->assertForbidden();
        $this->asUser(2);
        $this->tick($page)->assertForbidden();
    }

    public function test_guest_cannot_render_claim_or_release(): void
    {
        $page = $this->page();
        auth()->forgetGuards();
        $this->getJson('/concurrent-test/page')->assertUnauthorized();
        $this->tick($page)->assertUnauthorized();
        $this->leave($page)->assertUnauthorized();
    }

    public function test_validation_rejects_missing_and_malformed_input(): void
    {
        $this->postJson('/concurrent-uri/tick')->assertUnprocessable()
            ->assertJsonValidationErrors(['url', 'token', 'pageKey']);
        $page = $this->page();
        $this->tick($page, 'short')->assertUnprocessable()->assertJsonValidationErrors('pageKey');
        $data = $this->data($page);
        $data['url'] = [];
        $this->postJson('/concurrent-uri/leave-page', $data)->assertUnprocessable();
    }

    public function test_disabled_feature_does_not_render_or_guard_and_disables_endpoints(): void
    {
        config(['crud.useConcurrentRequestsAlert' => false]);
        $this->get('/concurrent-test/page')->assertOk()->assertDontSee('ib-concurrent-uri');
        $this->postJson('/concurrent-test/operation')->assertOk();
        $this->postJson('/concurrent-uri/tick')->assertNotFound();
        $this->postJson('/concurrent-uri/leave-page')->assertNotFound();
    }

    public function test_cache_keys_preserve_paths_and_isolate_scopes(): void
    {
        $first = $this->checker()->getUriCacheKey('a/b');
        $this->assertNotSame($first, $this->checker()->getUriCacheKey('a-b'));
        config(['crud.concurrentUriScope' => 'tenant-2']);
        $this->assertNotSame($first, $this->checker()->getUriCacheKey('a/b'));
    }

    public function test_foreign_host_cannot_reuse_a_page_token(): void
    {
        $page = $this->page();
        $this->postJson('http://other.test/concurrent-uri/tick', $this->data($page))->assertForbidden();
    }

    public function test_timing_configuration_is_independent_and_rejects_unsafe_values(): void
    {
        config(['crud.concurrentUriCacheLifetime' => 30]);
        $this->assertSame(3000, $this->checker()->getJavascriptTickInterval());
        config(['crud.concurrentUriJavascriptTickInterval' => 11000]);
        $this->expectException(InvalidArgumentException::class);
        $this->checker()->getCacheLifetime();
    }

    public function test_non_positive_intervals_are_rejected(): void
    {
        config(['crud.concurrentUriJavascriptTickInterval' => 0]);
        $this->expectException(InvalidArgumentException::class);
        $this->checker()->getJavascriptTickInterval();
    }

    public function test_lock_contention_prevents_claim_renew_release_and_write(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $lock = Cache::lock($this->checker()->getUriCacheKey($page['url']) . ':lock', 30);
        $this->assertTrue($lock->get());
        try {
            $this->tick($page)->assertStatus(423);
            $this->tick($page, self::SECOND)->assertStatus(423);
            $this->leave($page)->assertStatus(423);
            $this->write($page)->assertStatus(423);
        } finally {
            $lock->release();
        }
        $this->write($page)->assertOk();
    }

    public function test_guard_holds_the_lock_through_the_operation_and_releases_it_on_failure(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        try {
            $this->checker()->runOwnedOperation($page['url'], self::FIRST, function () use ($page) {
                try {
                    $this->checker()->managePrevalentUserData($page['url'], self::SECOND);
                    $this->fail('A second request acquired the page during a write.');
                } catch (HttpException $exception) {
                    $this->assertSame(423, $exception->getStatusCode());
                }
                throw new \RuntimeException('Operation failed');
            });
            $this->fail('Expected the operation exception.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Operation failed', $exception->getMessage());
        }
        $this->write($page)->assertOk();
    }

    public function test_enabling_tab_conflicts_again_selects_only_one_previously_shared_tab(): void
    {
        config(['crud.concurrentUriCheckSameUserTabs' => false]);
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->tick($page, self::SECOND)->assertOk();
        config(['crud.concurrentUriCheckSameUserTabs' => true]);
        $this->write($page)->assertOk();
        $this->write($page, self::SECOND)->assertStatus(409);
    }

    public function test_a_request_exceeding_its_lock_lifetime_cannot_overwrite_the_new_owner(): void
    {
        $page = $this->page();
        $this->tick($page)->assertOk();
        $this->checker()->runOwnedOperation($page['url'], self::FIRST, function () use ($page) {
            $this->travel(121)->seconds();
            $this->checker()->managePrevalentUserData($page['url'], self::SECOND);
        });
        $this->write($page)->assertStatus(409);
        $this->write($page, self::SECOND)->assertOk();
    }

    public function test_independent_cache_store_instances_contend_for_the_same_file_lock(): void
    {
        $directory = sys_get_temp_dir() . '/crud-concurrent-' . bin2hex(random_bytes(6));
        config(['cache.stores.concurrent-test' => ['driver' => 'file', 'path' => $directory],
            'crud.concurrentUriCacheStore' => 'concurrent-test']);
        $page = $this->page();
        $this->tick($page)->assertOk();
        $lock = Cache::store('concurrent-test')->lock($this->checker()->getUriCacheKey($page['url']) . ':lock', 30);
        $this->assertTrue($lock->get());
        try {
            Cache::purge('concurrent-test');
            $this->tick($page, self::SECOND)->assertStatus(423);
            $this->leave($page)->assertStatus(423);
        } finally {
            $lock->release();
        }
        try {
            $this->write($page)->assertOk();
        } finally {
            app('files')->deleteDirectory($directory);
        }
    }
}
