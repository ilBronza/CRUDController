<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Relationships;

use IlBronza\CRUD\Helpers\ModelManagers\CrudModelAssociatorHelper;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipParameters;
use IlBronza\CRUD\Providers\RelationshipsManager\RelationshipsManager;
use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AssociateBelongsToManyController
{
	/**
	 * The picker behaves as a normal CRUD index. Its initial page is rendered
	 * here; DataTables then performs an AJAX GET against this same route to
	 * retrieve the selectable rows.
	 */
	public function index(Request $request, string $model, string $key, string $relation)
	{
		$parent = $this->resolveParentModel($model, $key);
		$relationship = $this->resolveRelationship($parent, $relation);

		$this->authorizeAssociation($parent);

		$table = $relationship->createAssociateTable();

		// Mirrors CRUDIndexTrait::_index(): AJAX requests only need DataTables'
		// payload, while the initial request receives the complete table page.
		if ($request->ajax() && ! $request->ibFetcher)
			return $table->renderPage();

		$this->configurePickerTable($request, $relationship, $table);

		return $table->renderPage();
	}

	public function store(Request $request, string $model, string $key, string $relation)
	{
		$parent = $this->resolveParentModel($model, $key);
		$relationship = $this->resolveRelationship($parent, $relation);

		$this->authorizeAssociation($parent);

		return $this->associate($request, $parent, $relationship);
	}

	private function configurePickerTable(
		Request $request,
		RelationshipParameters $relationship,
		Datatables $table
	) : void
	{
		$table->setCaption(__('crud::crud.associateSelectTitle', [
			'what' => $relationship->getCardTitle(),
		]));
		$table->setScrollY(false);
		$table->setFooterFilters($relationship->hasFooterFilters());

		$table->createPostButtonSamePage([
			'href' => $this->getStoreUrl($request),
			'text' => 'crud::crud.associateSelected',
			'icon' => 'plus',
		]);
	}

	/**
	 * Keep the iframe context on the form action. The DataTable helper submits
	 * selected `ids[]` itself, so they must not be carried over from the GET.
	 */
	private function getStoreUrl(Request $request) : string
	{
		$query = $request->only(['iframed', 'callertablename']);

		if (! $query)
			return $request->url();

		return $request->url() . '?' . http_build_query($query);
	}

	private function resolveParentModel(string $modelAlias, string $key) : Model
	{
		// Resolve only aliases deliberately registered by the host application.
		// Accepting arbitrary FQCNs here would let the URL select its model class.
		$modelClass = Relation::getMorphedModel($modelAlias);

		if (! $modelClass || ! is_a($modelClass, Model::class, true))
			abort(404);

		return $modelClass::query()->findOrFail($key);
	}

	private function resolveRelationship(Model $parent, string $relationName) : RelationshipParameters
	{
		if (! method_exists($parent, 'getRelationshipsManagerClass'))
			abort(404);

		$managerClass = $parent->getRelationshipsManagerClass();

		if (! $managerClass || ! is_a($managerClass, RelationshipsManager::class, true))
			abort(404);

		/** @var RelationshipsManager $manager */
		$manager = new $managerClass($parent);

		if ($manager->usesLegacyRelationsParameters())
			$manager->setLegacyRelationsType('show');
		$relationship = $manager->getRelationships()->get($relationName);

		if (! $relationship instanceof RelationshipParameters
			|| ! $relationship->hasAssociateButton()
			|| ! $relationship->isAssociatableManyToManyRelationship())
			abort(404);

		return $relationship;
	}

	private function authorizeAssociation(Model $parent) : void
	{
		if (method_exists($parent, 'userCanUpdate') && ! $parent->userCanUpdate())
			abort(403);
	}

	private function associate(Request $request, Model $parent, RelationshipParameters $relationship)
	{
		$ids = collect($request->input('ids', []))
			->filter(fn($id) => is_scalar($id) && (string) $id !== '')
			->map(fn($id) => (string) $id)
			->unique()
			->values();

		if ($ids->isEmpty())
			return back()->withErrors([
				'ids' => __('crud::crud.associateSelectAtLeastOne'),
			]);

		$elements = $this->getSelectedAssociateableElements($relationship, $ids);

		if ($elements->count() !== $ids->count())
			abort(403);

		CrudModelAssociatorHelper::associateAdditionalRelation(
			$parent,
			$relationship->getRelationshipMethod(),
			$elements
		);

		if ($request->boolean('iframed') && $request->filled('callertablename'))
			return view('crud::utilities.messages.closeIframe', [
				'closeMessage' => __('crud::crud.associateSuccess'),
				'tableIdsToRefresh' => [$request->input('callertablename')],
			]);

		return back()->with('success', __('crud::crud.associateSuccess'));
	}

	/**
	 * The POST never trusts IDs received from the browser: they must still be
	 * visible in the same picker collection used for the GET page.
	 */
	private function getSelectedAssociateableElements(RelationshipParameters $relationship, Collection $ids) : Collection
	{
		$elementsByKey = $relationship->getAssociateableElements()->keyBy(
			fn(Model $model) => (string) $model->getKey()
		);

		return $ids->map(
			fn(string $id) => $elementsByKey->get($id)
		)->filter();
	}
}
