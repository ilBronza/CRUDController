<?php

namespace IlBronza\CRUD\Traits;

use Auth;
use DB;
use Exception;
use IlBronza\Buttons\Button;
use IlBronza\CRUD\Buttons\CreateEmptyButton;
use IlBronza\CRUD\Interfaces\CondensableModelInterface;
use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function is_null;

// use \newdatatable;

trait CRUDIndexTrait
{
	public int $fixedColumnLeft = 0;

	public ?bool $bulkEdit = null;
	public bool $hasSorting = true;
	public bool $hasInlineCreate = false;
	public ?bool $filtersHidden = null;

	public function allowMassDeleting() : bool
	{
		return (bool) ($this->allowMassDeleting ?? false);
	}

	public function mustShowRowSelectCheckboxes() : bool
	{
		return $this->allowMassDeleting() || (bool) ($this->rowSelectCheckboxes ?? false);
	}

	public function getFixedColumnLeft() : int
	{
		return $this->fixedColumnLeft;
	}

	public function getTable() : Datatables
	{
		return $this->table;
	}

	public function getSelectRow()
	{
		return $this->selectRow ?? false;
	}

	public function getTableFieldsGroupsByFile(array $keys) : array|false
	{
		$groups = [];

		foreach ($keys as $key)
		{
			$getterMethod = "get" . ucfirst($key) . "FieldsArray";

			if (! method_exists($this, $getterMethod))
			{
				if ($this->debugMode())
					throw new Exception('Dichiara ' . $getterMethod . ' in ' . get_class($this));

				return false;
			}

			$groups[$key] = $this->$getterMethod();
		}

		return $groups;
	}

	/**
	 * takes all the necessary fieldsGroups by key
	 *
	 * @param  string|string  $fullQualifiedClass
	 * @param  array|string   $keys
	 *
	 * @return array
	 */
	public function getTableFieldsGroups(string|array $keys)
	{
		if (! is_array($keys))
			$keys = [$keys];

		if ($groups = $this->getTableFieldsGroupsByFile($keys))
			return $groups;

		$groups = [];

		foreach ($keys as $key)
			// if(($table = static::NEWgetTableFieldsGroup($key)) !== null)
			if (($table = $this->getTableFieldsGroup($key)) !== null)
				$groups[$key] = $table;

		return $groups;
	}

	public function getTableFieldsGroup(string $key)
	{
		if (($table = $this::$tables[$key] ?? null) === null)
			return null;

		// if(isset($table['fields']))
		//     return $table['fields'];

		return $table;
	}

	public function userCanCreate(User $user = null)
	{
		if (! $user)
			$user = Auth::user();

		if (! $this->methodIsAllowed('index'))
			return false;

		return $this->getModelClass()::userCanCreate($user);
	}

	public function addCreateButton()
	{
		$createButton = $this->getCreateNewModelButton();
		$this->configureCreateButton($createButton);

		$this->table->addButton($createButton);
	}

	public function hasCreateButtonIframe() : bool
	{
		if (isset($this->createButtonIframe))
			return (bool) $this->createButtonIframe;

		return (bool) config('crud.index.createButtonIframe', true);
	}

	protected function configureCreateButton(Button $createButton) : void
	{
		if (! $this->hasCreateButtonIframe())
			return;

		// DataTable toolbar buttons use their delegated iframe opener. The create
		// route renders a form, so it must be loaded with GET instead of the
		// action button's default POST request.
		$createButton->setAjaxTableButton();
		$createButton->setAsIframe();
		$createButton->setData('method', 'GET');
	}

	public function hasCreateEmptyButton() : bool
	{
		return (bool) ($this->createEmpty ?? false);
	}

	public function getCreateEmptyButton() : CreateEmptyButton
	{
		$button = CreateEmptyButton::create([
			'text' => 'crud::buttons.createEmpty',
			'icon' => 'plus',
		]);

		$button->setSecondary();
		$button->setCreateEmptyUrl($this->getRouteUrlByType('createEmpty'));

		return $button;
	}

	public function addCreateEmptyButton() : void
	{
		if (! $this->hasCreateEmptyButton())
			return;

		$this->table->addButton($this->getCreateEmptyButton());
	}

	protected function makeEmptyModel() : Model
	{
		$model = $this->getModelClass()::make();
		$parameters = $this->getModelDefaultParameters();

		if (method_exists($this, 'addParentModelAssociationParameter'))
			$parameters = $this->addParentModelAssociationParameter($parameters);
		elseif (isset($this->parentModel) && method_exists($this, 'associateParentModel'))
			$parameters = $this->associateParentModel($parameters);

		foreach ($parameters as $key => $value)
			$model->$key = $value;

		return $model;
	}

	/**
	 * Create the model without passing through the form validation flow.
	 * The model preserves the controller defaults and a possible parent-model
	 * association before it is saved.
	 */
	public function createEmpty(Request $request)
	{
		abort_unless($this->hasCreateEmptyButton(), 404);
		abort_unless($this->userCanCreate(), 403);

		$this->setModel($this->makeEmptyModel());
		$this->getModel()->save();

		return response()->json([
			'success' => true,
			'key' => $this->getModel()->getKey(),
		]);
	}

	public function getPageLength()
	{
		return $this->pageLength ?? config('datatables.pageLength', 50);
	}

	public function addIndexButtons() {}

	public function addMassDeleteButton()
	{
		if(! $this->allowMassDeleting())
			return;

		$button = Button::create([
			'href' => 'javascript:void(0)',
			'text' => 'crud::buttons.massDeleteSelected',
			'icon' => 'trash'
		]);

		$button->setHtmlClass('uk-button uk-button-danger ib-table-action-button');
		$button->setHtmlClass('ib-dt-selection-action uk-hidden');
		$button->setData('route', $this->getRouteUrlByType('bulkDelete'));

		$this->table->addButton($button);
	}

	public function addCondenseButton()
	{
		$model = $this->getModelClass()::make();

		if(! $model instanceof CondensableModelInterface)
			return;

		if(! $this->mustShowRowSelectCheckboxes() && ! $this->getRowSelectCheckboxes())
			return;

		$this->getTable()->createPostButtonSamePage([
			'href' => $model->getBulkCondenseUrl(),
			'text' => 'crud::buttons.condenseSelected',
			'icon' => 'compress',
		]);
	}

	public function addPostFieldsToTable()
	{
		$this->addCondenseButton();
	}

	public function beforeRenderIndex() {}

	/** Runs for both freshly calculated and cached index responses. */
	public function beforeIndex() : void {}

	public function usesIndexCache() : bool
	{
		return (bool) ($this->useIndexCache ?? true);
	}

	protected function getIndexJsonDataCacheKey(string $modelClass) : string
	{
		return ($this->indexCacheKey ?? $modelClass::getIndexCacheKey()) . '_jsonData';
	}

	protected function getIndexDataCacheContext(Request $request, array $definition) : ?string
	{
		try
		{
			// One entry per model: a different index replaces it, never reads its cells.
			return hash('sha256', serialize([
				static::class, $request->method(), $request->url(), app()->getLocale(),
				$request->except(['_', '_token', 'draw', 'cachedtablekey']), $definition,
			]));
		}
		catch (\Throwable $exception)
		{
			// Definitions containing closures or other non-serializable values stay live.
			return null;
		}
	}

	public function getCaption() : null|bool|string
	{
		if (isset($this->caption))
			return $this->caption;

		if ($caption = $this->getTable()->getcaption())
			return $caption;

		return $this->getTable()?->getPlaceholderElement()?->getPluralTranslatedClassname();
	}

	public function manageTableCaption()
	{
		if($this->getCaption() === false)
			$this->getTable()->setCaption(false);

		else if ($caption = $this->getCaption())
			$this->getTable()->setCaption($caption);
	}

	public function getRowSelectCheckboxes()
	{
		return $this->rowSelectCheckboxes;
	}

	public function _index(Request $request, string $tableName = null, array $fieldsGroupsNames = null, callable $elementsGetter = null, bool $selectRow = false, array $tableVariables = [], string $baseModel = null)
	{
		if ($this->allowMassDeleting())
			$this->rowSelectCheckboxes = true;

		if (! $tableName)
			$tableName = $this->getTableName();

		if (! $fieldsGroupsNames)
			$fieldsGroupsNames = $this->getIndexFieldsGroups();

		if (! $selectRow)
			$selectRow = $this->getSelectRow();

		$this->setPagetitle();
		$this->beforeIndex();

		$modelClass = $baseModel ?? $this->getModelClass();
		$fieldsGroups = $this->getTableFieldsGroups($fieldsGroupsNames);
		$selectRows = $selectRow ?: $this->mustShowRowSelectCheckboxes();
		$cacheKey = $cacheContext = null;

		if ($request->ajax() && ! $request->ibFetcher
			&& ! $request->hasAny(['rowId', 'rowIds', 'ibeditor'])
			&& $elementsGetter === null && $this->usesIndexCache()
			&& in_array(CRUDCacheTrait::class, class_uses_recursive($modelClass), true))
		{
			$cacheContext = $this->getIndexDataCacheContext($request, [
				$modelClass, $tableName, $fieldsGroups, $selectRows, $tableVariables, $this->bulkEdit,
			]);

			if ($cacheContext !== null)
			{
				$cacheKey = $this->getIndexJsonDataCacheKey($modelClass);
				$cached = cache()->get($cacheKey);
				if (is_array($cached) && ($cached['context'] ?? null) === $cacheContext
					&& is_array($cached['response'] ?? null))
					return $cached['response'];
			}
		}

		$queryProvider = $elementsGetter === null && method_exists($this, 'getIndexQuery')
			&& is_callable([$this, 'getIndexQuery'])
			? fn () => $this->getIndexQuery()
			: null;

		$this->table = Datatables::create(
			$tableName, $fieldsGroups, function () use ($elementsGetter)
		{
			if ($elementsGetter)
				return $elementsGetter();

			return $this->getIndexElements();
		}, $selectRows, $tableVariables, $modelClass, $this->bulkEdit, null, $queryProvider
		);

		$this->table->setFiltersHidden($this->filtersHidden);

		if ($request->isMethod('post'))
			$this->table->setAjaxMethod('POST');

		if ((request()->ajax()) && (! request()->ibFetcher))
		{
			$response = $this->table->renderPage();
			if ($cacheKey !== null)
				cache()->forever($cacheKey, ['context' => $cacheContext, 'response' => $response]);

			return $response;
		}

		if ($this->fixedHeader ?? null)
			$this->table->fixedHeader = $this->fixedHeader;

		$this->table->addBaseModelClass($this->getModelClass());

		$this->table->addHtmlClass(Str::slug(get_class($this)));
		$this->table->addHtmlClass(Str::slug(class_basename($this)));

		$this->table->setPageLength(
			$this->getPageLength()
		);

		$this->table->setFixedColumnsLeft($this->getFixedColumnLeft());

		if (isset($this->parentModel) && ($this->mustDisplayParentModel()))
			$this->table->addParentModel($this->parentModel);

		if (! is_null($this->mustPrintIntestation ?? null))
			$this->table->setMustPrintIntestation($this->mustPrintIntestation);

		$this->addIndexButtonsToTable();
		$this->addPostFieldsToTable();

		$this->table->setHasSorting($this->hasSorting);
		$this->table->setHasInlineCreate($this->hasInlineCreate);

		$this->manageTableCaption();

		$this->beforeRenderIndex();

		$this->shareExtraViews();

		$this->table->getFetchersFromController($this);

		return $this->table->renderPage();
	}

	public function getIndependentTable(Collection $elements, $fieldsGroupsName)
	{
		$tableName = $this->getModelClassBasename();
		$fieldsGroupsName = $this->getOrRelatedFieldsGroup($fieldsGroupsName);
		$fieldsGroup = $this->getTableFieldsGroups([$fieldsGroupsName]);

		$this->table = Datatables::create(
			$tableName, $fieldsGroup, function () use ($elements)
		{
			return $elements;
		}, false, [], $this->getModelClass()
		);

		$this->table->setArrayTable();
		$this->table->setPageLength(30);

		// $this->table->setMinimalDom();

		return $this->table;
	}

	public function getIndexFieldsGroups()
	{
		return $this->indexFieldsGroups;
	}

	public function getIndexCacheKey()
	{
		return $this->indexCacheKey ?? $this->getModelClass()::getIndexCacheKey();
	}

	public function getPlaceholderElement()
	{
		return $this->getModelClass()::make();
	}

	public function getIndexModelIds()
	{
		$placeholder = $this->getPlaceholderElement();

		return DB::table(
			$placeholder->getTable()
		)->select(
			$placeholder->getKeyName()
		)->get()->pluck(
			$placeholder->getKeyName()
		)->toArray();
	}

	public function getCachedModelsByIds(array $ids)
	{
		$cacheKeys = [];

		foreach ($ids as $id)
			$cacheKeys[] = $this->getModelClass()::staticCacheKey($id);

		return cache()->many($cacheKeys);
	}

	public function getIndexMissingIds($totalElementIds, $cachedModels)
	{
		$cachedIds = array_column(
			$cachedModels, 'id'
		);

		return array_diff(
			$totalElementIds, $cachedIds
		);
	}

	public function setExecutionLimitsByMissingIds($missingIds)
	{
		$maxExecutionSeconds = (int) (count($missingIds) / 10);

		if ($maxExecutionSeconds > 300)
			$maxExecutionSeconds = 300;

		if ($maxExecutionSeconds < 30)
			$maxExecutionSeconds = 30;

		ini_set('max_execution_time', $maxExecutionSeconds);
		ini_set('memory_limit', "-1");
	}

	public function getCachedIndexElements()
	{
		$totalElementIds = $this->getIndexModelIds();

		$cachedModels = $this->getCachedModelsByIds(
			$totalElementIds
		);

		$missingIds = $this->getIndexMissingIds(
			$totalElementIds, $cachedModels
		);

		$this->setExecutionLimitsByMissingIds(
			$missingIds
		);

		$missingElements = $this->_getIndexElements($missingIds);

		foreach ($missingElements as $missingElement)
			$missingElement->storeInCache();

		return $missingElements->merge($cachedModels);
	}

	private function canReorder()
	{
		return in_array('reorder', $this->allowedMethods);
	}

	private function manageReorderButton()
	{
		if (! $this->canReorder())
			return;

		$reorderButton = $this->getReorderButton();

		$this->table->addButton($reorderButton);
	}

	private function manageCreateButton()
	{
		if ($this->avoidCreateButton ?? false)
			return;

		try
		{
			if (! $this->userCanCreate())
				return;
		}
		catch (Exception $e)
		{
			throw new Exception('Associa il trait CRUDModelTrait al model ' . $this->getModelClass() . '. ' . $e->getMessage());
		}

		$this->addCreateButton();
	}

	private function addIndexButtonsToTable()
	{
		try
		{
			$this->manageCreateButton();
		}
		catch (Exception $e)
		{
			throw new Exception('errore nella creazione del pulsante nuovo elemento per la route ' . $e->getMessage());
			dd('Usa avoid create button per evitare l\'eccezione: ' . $e->getMessage());
		}

		$this->manageReorderButton();

		$this->addCreateEmptyButton();

		$this->addMassDeleteButton();

		$this->addIndexButtons();
	}

	private function getTableName()
	{
		return Str::slug($this->getModelClassBasename());
	}

	private function getOrRelatedFieldsGroup($fieldsGroup)
	{
		return 'related';
	}
}
