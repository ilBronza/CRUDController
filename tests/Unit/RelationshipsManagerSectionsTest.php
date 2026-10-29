<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\Buttons\Button;
use IlBronza\CRUD\Providers\RelationshipsManager\FetcherParameters;
use IlBronza\CRUD\Providers\RelationshipsManager\ManagerSection;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipParameters;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;
use IlBronza\CRUD\Traits\CRUDRelationshipsManagerTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;

class RelationshipsManagerSectionsTest extends TestCase
{
	private function parentModel() : SectionsParent
	{
		$parent = new SectionsParent();
		$parent->setRawAttributes(['id' => 42]);
		$parent->setRelation('children', collect([new Gadget(['name' => 'Related gadget'])]));

		return $parent;
	}

	public function test_mixed_sections_preserve_order_and_expose_only_relationships_to_relation_callers() : void
	{
		$manager = new MixedSectionsManager($this->parentModel());
		$sections = $manager->getSections();

		$this->assertSame(['summary', 'children', 'notes'], $sections->keys()->all());
		$this->assertContainsOnlyInstancesOf(ManagerSection::class, $sections);
		$this->assertInstanceOf(FetcherParameters::class, $sections['notes']);
		$this->assertSame(['children'], $manager->getRelationshipsNamesArray());
		$this->assertSame(['children'], $manager->getRelationshipsMethodsArray());
		$this->assertSame($sections['children'], $manager->getRelationships()['children']);
		$this->assertSame($sections, $manager->getSections());
		$this->assertFalse($manager->usesLegacyRelationsParameters());
	}

	#[DataProvider('layouts')]
	public function test_layouts_render_a_fetcher_only_manager_without_accessing_an_eloquent_relation(string $layout) : void
	{
		$controller = new SectionsController();
		$controller->managerClass = FetcherOnlySectionsManager::class;
		// This model deliberately has no relation methods at all.
		$controller->modelInstance = new class extends Model {};
		$controller->modelInstance->setRawAttributes(['id' => 21]);
		$controller->useRelationshipsManager();
		$manager = $controller->relationshipManager;

		$html = view('crud::uikit.__relationships' . $layout)->render();

		$this->assertStringContainsString('Summary section', $html);
		$this->assertStringContainsString('Notes section', $html);
		$this->assertStringContainsString('data-url="/sections/21/notes?ibFetcher=true"', $html);
		$this->assertSame(2, substr_count($html, 'class="ibfetcher"'));
		$this->assertEmpty($manager->getRelationships());
		$this->assertNull($manager->legacyRelationsType);

		if ($layout === 'Toggler')
		{
			$toggleId = $manager->getSections()['notes']->getToggleId();
			$this->assertStringContainsString('uk-toggle="target: #' . $toggleId . '"', $html);
			$this->assertStringContainsString('id="' . $toggleId . '" hidden', $html);
		}
	}

	#[DataProvider('layouts')]
	public function test_layouts_render_real_relation_tables_between_fetchers(string $layout) : void
	{
		$route = (new Route('GET', '/sections', fn () => null))->name('sections.show');
		request()->setRouteResolver(fn () => $route);
		$controller = new SectionsController();
		$controller->modelInstance = $this->parentModel();
		$controller->useRelationshipsManager();

		$html = view('crud::uikit.__relationships' . $layout)->render();

		$this->assertStringContainsString('wannabedatatable', $html);
		$this->assertStringContainsString('data-url="/sections/42/notes?ibFetcher=true"', $html);
		$this->assertLessThan(strpos($html, 'Children section'), strpos($html, 'Summary section'));
		$this->assertLessThan(strpos($html, 'Notes section'), strpos($html, 'Children section'));
		$this->assertSame(2, substr_count($html, 'class="ibfetcher"'));
	}

	public static function layouts() : array
	{
		return [['Flat'], ['Switcher'], ['Toggler']];
	}

	public function test_fetcher_preserves_refresh_expand_and_custom_buttons() : void
	{
		$button = Button::create(['href' => '/notes/create', 'translatedText' => 'Add note']);
		$button->setAsIframe();
		$section = new FetcherParameters('notes', [
			'type' => 'fetcher',
			'title' => 'Notes section',
			'url' => '/sections/42/notes',
			'refresh' => false,
			'canExpand' => true,
			'buttons' => [$button],
		]);

		$section->prepare();
		$fetcher = $section->getFetcher();
		$html = $section->render();

		$this->assertSame($fetcher, $section->getFetcher());
		$this->assertSame('notes', $section->getName());
		$this->assertSame('Notes section', $section->getCardTitle());
		$this->assertSame($button, $fetcher->getButtons()->first());
		$this->assertStringNotContainsString('class="refresh"', $html);
		$this->assertStringContainsString('class="expand"', $html);
		$this->assertStringContainsString('href="/notes/create?iframed=true"', $html);
	}

	public function test_relation_ajax_still_returns_rows_in_a_mixed_manager() : void
	{
		$this->app->instance('request', Request::create('/sections?model=children', 'GET', [], [], [], [
			'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
		]));
		$controller = new SectionsController();
		$controller->modelInstance = $this->parentModel();

		$result = $controller->useRelationshipsManager();

		$this->assertSame(1, $result['recordsTotal']);
		$this->assertStringContainsString('Related gadget', json_encode($result['data']));
	}

	public function test_fetcher_names_are_excluded_from_table_and_row_refresh_dispatch() : void
	{
		$this->app->instance('request', Request::create('/sections?model=notes', 'GET', [], [], [], [
			'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
		]));
		$manager = new MixedSectionsManager($this->parentModel());

		$this->assertNull($manager->manageAjaxTableRequest());
		$this->assertNull($manager->manageRefreshRow());
	}

	public function test_single_relation_can_be_resolved_from_a_section_map() : void
	{
		$manager = new MixedSectionsManager($this->parentModel(), 'children', [42]);

		$this->assertInstanceOf(RelationshipParameters::class, $manager->getRelationship());
		$this->assertSame('children', $manager->getRelationship()->getRelationshipMethod());
		$this->assertSame(['children'], $manager->getSections()->keys()->all());
	}

	public function test_single_relation_api_rejects_a_fetcher_before_attempting_to_resolve_a_relation() : void
	{
		$manager = new MixedSectionsManager($this->parentModel(), 'notes');

		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('Section "notes" is not a relationship');

		$manager->getRelationship();
	}

	public function test_unknown_section_type_is_reported_explicitly() : void
	{
		$manager = new MixedSectionsManager($this->parentModel());

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown section type for "notes"');

		$manager->createSection('notes', ['type' => 'fecther']);
	}

	#[DataProvider('invalidUrls')]
	public function test_fetcher_requires_a_nonempty_string_url(array $parameters) : void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Fetcher section "notes" requires a non-empty URL.');

		new FetcherParameters('notes', $parameters);
	}

	public static function invalidUrls() : array
	{
		return [[[]], [['url' => '  ']], [['url' => ['invalid']]]];
	}

	public function test_existing_relation_footer_fetcher_remains_separate_from_sections() : void
	{
		$manager = new MixedSectionsManager($this->parentModel());
		$relation = $manager->createRelationship('children', array_merge($manager->getRelationParameters('children'), [
			'fetcherUrl' => '/children/totals',
			'fetcherParameters' => ['refresh' => false],
		]));
		$relation->prepare();

		$fetcher = $relation->getTable()->getFetchersPosition('bottom')->sole();
		$this->assertSame('/children/totals?ibFetcher=true', $fetcher->getUrl());
		$this->assertFalse($fetcher->hasRefresh());
	}
}

class SectionsParent extends Model
{
	public function children() : HasMany
	{
		return $this->hasMany(Gadget::class, 'parent_id');
	}
}

class MixedSectionsManager extends RelationshipsManager
{
	public function getSectionsParameters() : array
	{
		return [
			'summary' => [
				'type' => 'fetcher',
				'title' => 'Summary section',
				'url' => '/sections/' . $this->getModel()->getKey() . '/summary',
			],
			'children' => [
				'type' => 'relationship',
				'translatedTitle' => 'Children section',
				'fieldsGroups' => ['base' => ['fields' => ['name' => 'flat']]],
				'selectRowCheckboxes' => false,
				'footerFilters' => false,
			],
			'notes' => [
				'type' => 'fetcher',
				'title' => 'Notes section',
				'url' => '/sections/' . $this->getModel()->getKey() . '/notes',
			],
		];
	}
}

class FetcherOnlySectionsManager extends MixedSectionsManager
{
	public function getSectionsParameters() : array
	{
		return array_diff_key(parent::getSectionsParameters(), ['children' => true]);
	}
}

class SectionsController
{
	use CRUDRelationshipsManagerTrait;

	public string $managerClass = MixedSectionsManager::class;
	public Model $modelInstance;
	public $relationshipManager;

	public function getRelationshipsManagerClass() : string
	{
		return $this->managerClass;
	}
}
