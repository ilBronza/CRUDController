<?php

namespace IlBronza\CRUD\Providers\RelationshipsManager;

use Exception;
use IlBronza\Buttons\Button;
use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use function class_basename;
use function config;
use function dd;
use function is_null;

class RelationshipParameters
{
	use RelationshipParametersGettersTrait;

	static $standardView = 'crud::uikit._teaser';
	static $renderAsTypes = [
		'BelongsToMany' => 'table',
		'MorphToMany' => 'table',
		'HasMany' => 'table',
		'HasManyThrough' => 'table',
		'MorphMany' => 'table',
		'HasOne' => 'view',
		'MorphOne' => 'view',
		'MorphTo' => 'view',
		'BelongsTo' => 'view'
	];

	public ?bool $sorting;
	public $name;
	public $relation;
	public $relationType;
	public $eloquentRelationship;
	public $renderAs;
	public $relatedModel;
	public $elements;

	public $relatedModelClass;
	public $controller;
	public array $buttons = [];

	public bool $onlyButtonsDom = false;
	public ? bool $footerFilters;

	public $mustPrintIntestation = true;

	public ?bool $hasCreateButton = null;
	public ?string $createButtonText = null;
	public ?bool $createButtonIframe = null;
	public ?bool $hasAssociateButton = null;
	public ?string $associateButtonText = null;
	/**
	 * Optional callback returning the models that may be associated. It receives
	 * the parent model and the Eloquent many-to-many relation.
	 */
	public $associateElementsGetter = null;
	public $currentView;
	public $extraVariables;
	public $translatedTitle;
	public $buttonsMethods = [];

	//url caricata da un fetcher sotto la tabella della relazione
	public ?string $fetcherUrl = null;
	public array $fetcherParameters = [];


	//relation-specific custom view name
	public $elementGetterMethod;
	public ?string $fieldsGroupsParametersFile;
	public $view;
	public $fieldsGroupsNames = ['related'];
	public ?bool $selectRowCheckboxes = null;
	public ?bool $reloadButton = null;
	public ?bool $copyButton = null;
	public ?bool $csvButton = null;
	public ?bool $columnsSorting = null;
	public ?string $refreshTableBroadcastChannel = null;
	public int $fixedColumnsLeft = 0;

	public $relationshipsManager;

	public function __construct(string $name, array $parameters, RelationshipsManager $relationshipsManager)
	{
		$this->name = $name;
		$this->relationshipsManager = $relationshipsManager;

		$this->manageParameters($parameters);
		$this->setRenderingType();
	}

	/**
	 * set relation property
	 *
	 * @param $relation  | string
	 **/
	public function setRelation(string $relation)
	{
		$this->relation = $relation;
	}

	/**
	 * set rendering type by given or default
	 *
	 * @param $type  | string
	 *
	 * @return string
	 **/
	public function setRenderingType(string $type = null) : string
	{
		if ($type)
			return $this->renderAs = $type;

		$relationType = $this->getRelationType();

		return $this->renderAs = $this->getRenderAsByRelationType($relationType);
	}

	/**
	 * get eloquent relation type
	 *
	 * @return string
	 **/
	public function getRelationType() : string
	{
		if (! $this->relationType)
			$this->setRelationType();

		return $this->relationType;
	}

	/**
	 * set all relation properties by relation type
	 *
	 * set $relationType => as BelongsToMany, BelongsTo, etc.
	 * set related model instance => ex. post
	 * set renderingType
	 **/
	public function setRelationType()
	{
		$relationMethod = $this->getRelationshipMethod();

		if (! $this->relationType)
		{
			$this->eloquentRelationship = $this->relationshipsManager->model->{$relationMethod}();
			$this->relationType = class_basename($this->eloquentRelationship);
		}

		$this->setRelatedModel();

		$this->setRenderingType();
	}

	/**
	 * get default render method by relation type
	 *
	 * a many relation returns 'table', a single relation returns 'view'
	 *
	 * @param $relationType  | string
	 *
	 * @return string
	 **/
	public function getRenderAsByRelationType(string $relationType) : string
	{
		if (! isset(static::$renderAsTypes[$relationType]))
			throw new Exception ('Crea lo script di caricamento relazione per ' . $relationType . ' in ' . __METHOD__);

		return static::$renderAsTypes[$relationType];
	}

	/**
	 * get relationship method name.
	 * Ex. posts() relation returns "posts"
	 *
	 * @return string
	 **/
	public function getRelationshipMethod() : string
	{
		return $this->relation;
	}

	public function getToggleId()
	{
		return str_replace('.', '-', $this->getCardTitle());
	}

	public function getCardTitle()
	{
		if ($this->translatedTitle)
			return $this->translatedTitle;

		if ($this->isPlural())
			return $this->getRelatedModel()->getPluralTranslatedClassname();

		return $this->getRelatedModel()->getTranslatedClassname();
	}

	public function isPlural() : bool
	{
		return $this->renderAsTable();
	}

	/**
	 * check if relation must be rendered as a table
	 *
	 * @return bool
	 **/
	public function renderAsTable() : bool
	{
		return $this->renderAs == 'table';
	}

	public function getElementGetterMethod()
	{
		return $this->elementGetterMethod;
	}

	/**
	 * set relation rendering type and parameters based on relation type
	 *
	 * if more elements render as table, otherwise render as view
	 **/
	public function setShowParameters()
	{
		if ($this->renderAsTable())
			return $this->setTable();

		return $this->setCurrentView();
	}

	public function returnSingleRow()
	{
		return $this->setTable();
	}

	public function getTable() : Datatables
	{
		return $this->table;
	}

	public function hasSorting()
	{
		if (isset($this->sorting))
			return $this->sorting;

		if (! $table = $this->getTable())
			return false;

		$fields = $table->getFields();

		foreach ($fields as $name => $_field)
			if ($name == 'sorting_index')
				return true;

		return false;
	}

	public function setSortingIndexParameters()
	{
		$this->table->setDragAndDropColumnIntestation('sorting_index');
		$this->table->setDragAndDropSelector('sorting_index');
		$this->table->setDragAndDropStoringReorderUrl(
			$this->getRelatedModel()->getStoreMassReorderUrl()
		);
	}

	/**
	 * set elements table by class properties
	 **/
	public function setTable()
	{
		$parameters = [
			'name' => $this->getTableName(),
			'fieldsGroups' => $this->getTableFieldsGroups(),
			'elements' => function ()
			{
				return $this->getElements();
			},
			'selectRowCheckboxes' => $this->hasSelectRowCheckboxes(),
			'extraVariables' => $this->getExtraVariables(),
			'modelClass' => $this->getRelatedModelClass(),
			'reloadButton' => $this->getHasReloadButton(),
			'copyButton' => $this->getHasCopyButton(),
			'csvButton' => $this->getHasCsvButton(),
			'id' => Str::slug($this->getName())
		];

		$this->table = Datatables::createStandAloneTable($parameters);
		$this->manageRefreshTableBroadcastChannel();
		$this->table->setFixedColumnsLeft($this->fixedColumnsLeft);
		$this->table->setHasSorting($this->getHasColumnsSorting());
		$this->manageFetcher();

		if ($this->onlyButtonsDom)
			$this->table->setOnlyButtonsDom();

		if ($this->hasSorting())
			$this->setSortingIndexParameters();

		$this->manageDomTable();

		if (request()->ajax())
			return $this->table->renderPage();

		// $this->table->setScrollX(false);

		$this->table->setAjaxTable();
		$this->table->setCaption(false);

		$this->table->addHtmlClass('related-table');

		$this->table->setMustPrintIntestation($this->mustPrintIntestation);

		$this->table->setScrollY(false);

		$this->table->setFooterFilters(
			$this->hasFooterFilters()
		);

		$this->manageTableButtons();
	}

	public function hasFooterFilters() : bool
	{
		if(isset($this->footerFilters))
			return $this->footerFilters;

		return config('datatables.footerFilters');
	}

	/**
	 * get relation's table name
	 *
	 * @return string
	 **/
	public function getTableName() : string
	{
		return $this->name;
	}

	/**
	 * return fieldsGroups controller's declared array
	 *
	 * get fieldsgroups names array, get the dedicated controller and ask for fields's array
	 *
	 * @return array
	 **/
	public function getTableFieldsGroups() : array
	{
		if ($this->fieldsGroups ?? false)
			return $this->fieldsGroups;

		if ($this->fieldsGroupsParametersFile ?? false)
		{
			app('uikittemplate')->addFieldsGroupsNames($this->fieldsGroupsParametersFile);

			$helper = new $this->fieldsGroupsParametersFile();

			return [
				'base' => $helper->getFieldsGroup($this->getParentModel())
			];
		}

		$fieldsGroupsNames = $this->getFieldsGroupsNames();

		// try
		// {
		return app($this->controller)->getTableFieldsGroups($fieldsGroupsNames);
		// }
		// catch(\Throwable $e)
		// {
		// 	dd('dichiara i fieldsgroups ' . json_encode($fieldsGroupsNames) . ' su ' . ($this->controller) . '->' . $e->getMessage());
		// }
	}

	/**
	 * get fieldsGroups names array
	 *
	 * ex. ['related', 'prices']
	 *
	 * return array
	 **/
	public function getFieldsGroupsNames() : array
	{
		return $this->fieldsGroupsNames;
	}

	/**
	 * return relation's elements
	 *
	 * if set return property value, if not set call the setter
	 *
	 * returns a collection if many, a model instance if single
	 *
	 * @return mixed
	 **/
	public function getElements()
	{
		if (! $this->elements)
			$this->setElements();

		return $this->elements;
	}

	/**
	 * set relation's elements by given or load defaults
	 *
	 * if given, set elements collection
	 * if missing, get the relation method and lazy load elements
	 *
	 * @param $elements  | Collection
	 **/
	public function setElements(Collection $elements = null)
	{
		if ($elements)
			return $this->elements = $elements;

		if ($elementGetterMethod = $this->getElementGetterMethod())
			return $this->elements = $this->relationshipsManager->model->{$elementGetterMethod}();

		$relationMethod = $this->getRelationshipMethod();

		return $this->elements = $this->relationshipsManager->model->{$relationMethod};
	}

	public function getRelationshipsManager() : RelationshipsManager
	{
		return $this->relationshipsManager;
	}

	/**
	 * check if table must show selection checkboxes
	 *
	 * @return bool
	 **/
	public function hasSelectRowCheckboxes() : bool
	{
		if (! is_null($this->selectRowCheckboxes))
			return $this->selectRowCheckboxes;

		return config('crud.realtionshipManagers.selectRowCheckboxes');
	}

	public function getHasCsvButton()
	{
		if (! is_null($this->csvButton))
			return $this->csvButton;

		return config('crud.realtionshipManagers.csvButton');
	}

	public function getHasCopyButton()
	{
		if (! is_null($this->copyButton))
			return $this->copyButton;

		return config('crud.realtionshipManagers.copyButton');
	}

	public function getHasReloadButton()
	{
		if (! is_null($this->reloadButton))
			return $this->reloadButton;

		return config('crud.realtionshipManagers.reloadButton');
	}

	/**
	 * ordinamento delle colonne della tabella
	 * (da non confondere con hasSorting(), che e' il drag&drop su sorting_index)
	 **/
	public function getHasColumnsSorting() : bool
	{
		if (! is_null($this->columnsSorting))
			return $this->columnsSorting;

		return config('crud.realtionshipManagers.columnsSorting', true);
	}

	/**
	 * check if table must show extra variables to be called on row cell's methods
	 *
	 * @return mixed
	 **/
	public function getExtraVariables()
	{
		return $this->extraVariables;
	}

	/**
	 * return related model classname
	 **/
	public function getRelatedModelClass() : string
	{
		return $this->relatedModelClass;
	}

	public function manageDomTable()
	{
		if ($this->domMode ?? false)
			$this->table->setDomMode($this->domMode);
	}

	public function manageRefreshTableBroadcastChannel() : void
	{
		if (! is_null($this->refreshTableBroadcastChannel))
			$this->table->setRefreshTableBroadcastChannel($this->refreshTableBroadcastChannel);
	}

	public function manageFetcher() : void
	{
		if (is_null($this->fetcherUrl))
			return;

		$this->table->addFetcherByUrl($this->fetcherUrl, 'bottom', $this->fetcherParameters);
	}

	public function hasCreateButton()
	{
		if (! is_null($this->hasCreateButton))
			return $this->hasCreateButton;

		return config('crud.realtionshipManagers.createButton', false);
	}

	/**
	 * Enable the "associate existing models" action for a many-to-many
	 * relationship (BelongsToMany or MorphToMany). This deliberately has a different name from
	 * hasCreateButton(): no related record is created by this action.
	 */
	public function hasAssociateButton() : bool
	{
		if (! is_null($this->hasAssociateButton))
			return $this->hasAssociateButton;

		return config('crud.realtionshipManagers.associateButton', false);
	}

	public function isAssociatableManyToManyRelationship() : bool
	{
		return in_array($this->getRelationType(), [
			'BelongsToMany',
			'MorphToMany',
		]);
	}

	public function getAssociateButtonText() : string
	{
		if (! is_null($this->associateButtonText))
			return $this->associateButtonText;

		return trans('crud::crud.associateRelated', [
			'what' => $this->getCardTitle(),
		]);
	}

	/**
	 * The relation name, rather than its method name, identifies the manager
	 * configuration on the receiving endpoint. They may differ when `relation`
	 * is explicitly configured.
	 */
	public function getAssociateButtonUrl() : string
	{
		$parent = $this->getParentModel();

		return route('crud.relationships.associate', [
			// Morph aliases are stable public identifiers. Do not expose (or accept)
			// a base64-encoded FQCN in a public association URL.
			'model' => $parent->getMorphClass(),
			'key' => $parent->getKey(),
			'relation' => $this->getName(),
		]);
	}

	public function getAssociateButton() : ?Button
	{
		if (! $this->hasAssociateButton() || ! $this->isAssociatableManyToManyRelationship())
			return null;

		$button = Button::create([
			'href' => $this->getAssociateButtonUrl(),
			'translatedText' => $this->getAssociateButtonText(),
			'icon' => 'plus',
		]);

		// The selector is always rendered in the DataTable modal iframe.
		$button->setAjaxTableButton();
		$button->setAsIframe();
		$button->setData('method', 'GET');

		return $button;
	}

	/**
	 * Return the models displayed by the association picker. Existing related
	 * models are excluded, so the endpoint can safely perform an additive attach.
	 */
	public function getAssociateableElements() : Collection
	{
		$this->getRelationType();

		if ($this->associateElementsGetter)
			return collect(($this->associateElementsGetter)(
				$this->getParentModel(), $this->eloquentRelationship
			));

		$related = $this->getRelatedModel();
		$alreadyRelatedKeys = $this->eloquentRelationship->allRelatedIds();

		return $related->newQuery()
			->whereNotIn($related->qualifyColumn($related->getKeyName()), $alreadyRelatedKeys)
			->get()
			->filter(function (Model $model)
			{
				return ! method_exists($model, 'userCanSee') || $model->userCanSee();
			})
			->values();
	}

	/**
	 * Build the association picker table.
	 *
	 * This deliberately uses the same AJAX lifecycle as a CRUD index: the
	 * initial GET renders the DataTable shell and its subsequent GET to this
	 * very endpoint gets the rows as JSON. `createStandAloneTable()` turns the
	 * initial request into an array table, which is appropriate for embedded
	 * relation tables but prevents that lifecycle here.
	 */
	public function createAssociateTable() : Datatables
	{
		return Datatables::create(
			$this->getName() . '-associate',
			$this->getTableFieldsGroups(),
			fn() => $this->getAssociateableElements(),
			true,
			$this->getExtraVariables(),
			$this->getRelatedModelClass()
		);
	}

	/**
	 * Return the already translated text to use for the relation create button.
	 */
	public function getCreateButtonText() : ?string
	{
		return $this->createButtonText;
	}

	/**
	 * Whether the create button for this specific relation must open its form
	 * inside the DataTable modal iframe. A relation-level declaration takes
	 * precedence over the package default.
	 */
	public function hasCreateButtonIframe() : bool
	{
		if (! is_null($this->createButtonIframe))
			return $this->createButtonIframe;

		return config('crud.realtionshipManagers.createButtonIframe', false);
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function manageTableButtons()
	{
		$createButton = null;

		if ($this->hasCreateButton())
		{
			if ($this->isPolimorphicParentingRelationship())
			{
				$createButton = $this->relatedModel->getCreateByPolimorphicRelatedButton(
					$this->getParentModel()
				);

			}
			else if ($this->isParentingRelationship())
			{
				$createButton = $this->relatedModel->getCreateByRelatedButton(
					$this->getParentModel(), $this->relatedModel
				);
			}
		}

		if ($createButton)
		{
			if (! is_null($this->getCreateButtonText()))
				$createButton->setTranslatedText($this->getCreateButtonText());

			if ($this->hasCreateButtonIframe())
			{
				// Relation create URLs render a form, therefore the iframe opener
				// must request them with GET rather than its default POST method.
				// DataTable toolbars only delegate iframe clicks for AJAX table
				// buttons; without this flag their own action would redirect.
				$createButton->setAjaxTableButton();
				$createButton->setAsIframe();
				$createButton->setData('method', 'GET');
			}

			$this->table->addButton($createButton);
		}

		if ($associateButton = $this->getAssociateButton())
			$this->table->addButton($associateButton);

		foreach ($this->buttons as $button)
			$this->table->addButton($button);

		foreach ($this->buttonsMethods as $buttonsMethod)
			$this->table->addButton(
				$this->relatedModel->{$buttonsMethod}(
					$this->getParentModel()
				)
			);
	}

	public function getParentModel()
	{
		return $this->relationshipsManager->getModel();
	}

	/**
	 * set currentView by element property
	 **/
	public function setCurrentView()
	{
		//		$element = $this->getElement()
		$this->currentView = $this->getView();
	}

	/**
	 * get final view name
	 *
	 * check if view has been set and return it, otherwise calculated view name and return it
	 *
	 * possible scenario
	 *
	 * 1. return custom $view property
	 * 2. check if model has dedicated _teaser view inside its own resources folder
	 * 3. get standard package view
	 *
	 * @return string
	 **/
	public function getView() : ?string
	{
		if ($this->currentView)
			return $this->currentView;

		if ($this->hasOwnView())
			return $this->view;

		if (! $this->getElement())
			return null;

		$modelFolderName = $this->getElement()->getRouteBasename();
		$view = $modelFolderName . '._teaser';

		if (View::exists($view))
			return $view;

		return $this->getStandardView();
	}

	/**
	 * check if class has its own view
	 *
	 * @return mixed
	 **/
	public function hasOwnView()
	{
		return $this->view;
	}

	/**
	 * return single model relations instance
	 *
	 * get $elements property, if countable throw error because something went wrong, being this called on a single model relation
	 *
	 * @return Model
	 **/
	public function getElement() : ?Model
	{
		if (! $elements = $this->getElements())
		{
			return null;
			//			throw new \Exception(json_encode(['non trovl elementi RelationshipParameters 324']));
		}

		if (is_countable($elements))
			throw new Exception('problema con numero di associazioni ad un belongsTo');

		return $elements;
	}

	/**
	 * get standard view name
	 *
	 * @return string
	 **/
	public function getStandardView() : string
	{
		return static::$standardView;
	}

	public function renderTableRowsArray()
	{
		$this->table = Datatables::create(
			$this->getTableName(), $this->getTableFieldsGroups(), $this->elementsGetter, $this->hasSelectRowCheckboxes(), $this->getExtraVariables(), $this->getRelatedModelClass()
		);

		return $this->table->renderPage();

		// $parameters = [
		// 	'name' => $this->getTableName(),
		// 	'fieldsGroups' => $this->getTableFieldsGroups(),
		// 	'elements' => $this->getElements(),
		// 	'selectRowCheckboxes' => $this->hasSelectRowCheckboxes(),
		// 	'extraVariables' => $this->getExtraVariables(),
		// 	'modelClass' => $this->getRelatedModelClass()
		// ];

		// $this->table = Datatables::createStandAloneTable($parameters);

		// return $this->table->returnSingleElement($this->elementsGetter);
	}

	public function render()
	{
		if ($this->renderAsView())
			return $this->renderView();

		if ($this->renderAsTable())
			return $this->renderTable();

		throw new Exception ('No render method set on ' . class_basename($this));
	}

	/**
	 * check if relation must be rendered as a view
	 *
	 * @return bool
	 **/
	public function renderAsView() : bool
	{
		return $this->renderAs == 'view';
	}

	public function controllerHasTeaserMethod() : bool
	{
		if (! $controller = $this->getController())
			return false;

		return method_exists($controller, 'teaser');
	}

	/**
	 * return model's management controller full qualified className
	 *
	 * return string
	 **/
	public function getController() : string
	{
		return $this->controller;
	}

	public function renderControllerTeaser()
	{
		return app($this->getController())->teaser(
			$this->getElement()
		);
	}

	/**
	 * check if class must render standard view
	 *
	 * @return bool
	 **/
	public function hasStandardView() : bool
	{
		return $this->getView() == $this->getStandardView();
	}

	public function getRelatedModel() : Model
	{
		return $this->relatedModel;
	}

	/**
	 * set parameters and build default relation parameters
	 **/
	private function manageParameters(array $parameters)
	{
		foreach ($parameters as $name => $value)
			$this->{$name} = $value;

		$this->manageRelation($parameters);
	}

	/**
	 * set relation name if given, otherwise set name as relation
	 *
	 * @param $parameters  | array
	 **/
	private function manageRelation(array $parameters)
	{
		if (isset($parameters['relation']))
			$this->setRelation($parameters['relation']);

		else
			$this->setRelation($this->name);
	}

	private function renderView()
	{
		if ($this->controllerHasTeaserMethod())
			return $this->renderControllerTeaser();

		if (! $this->getElement())
			return view('crud::utilities.messages._modelMissingOrNotSet');

		return app($this->getController())->teaserMode()->_show(
			$this->getElement()
		);

		throw new Exception('dichiara il controller teaser method? O facciamo un fetcher?');

		if ($this->hasStandardView())
			return view(
				$this->getView(), ['teaserModel' => $this->getElement()]
			);

		$modelClassName = $this->getElement()->getRouteClassname();

		return view(
			$this->getView(), [
				$modelClassName => $this->getElement()
			]
		);
	}

	private function renderTable()
	{
		return $this->table->renderPortion();
	}

	/**
	 * set related model instance By eloquent relationship, and call set model class
	 **/
	private function setRelatedModel()
	{
		$this->relatedModel = $this->eloquentRelationship->getRelated();

		$this->setRelatedModelClass();
	}

	/**
	 * set related model classname by related model instance
	 **/
	private function setRelatedModelClass()
	{
		$this->relatedModelClass = get_class($this->relatedModel);
	}
}
