<?php

namespace IlBronza\CRUD\Providers\RelationshipsManager;

use Exception;
use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;

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

	public $mustPrintIntestation = true;

	public ?bool $hasCreateButton = null;
	public $currentView;
	public $extraVariables;
	public $translatedTitle;
	public $buttonsMethods = [];

	//relation-specific custom view name
	public $elementGetterMethod;
	public ?string $fieldsGroupsParametersFile;
	public $view;
	public $fieldsGroupsNames = ['related'];
	public ?bool $selectRowCheckboxes = null;
	public ?bool $reloadButton = null;
	public ?bool $copyButton = null;
	public ?bool $csvButton = null;

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
//		if (request()->rowId)
//			if ($this->name != 'quantities')
//				return;
//
//		dd('qwe');

		$parameters = [
			'name' => $this->getTableName(),
			'fieldsGroups' => $this->getTableFieldsGroups(),
			'elements' => $this->getElements(),
			'selectRowCheckboxes' => $this->hasSelectRowCheckboxes(),
			'extraVariables' => $this->getExtraVariables(),
			'modelClass' => $this->getRelatedModelClass(),
			'reloadButton' => $this->getHasReloadButton(),
			'copyButton' => $this->getHasCopyButton(),
			'csvButton' => $this->getHasCsvButton()
		];

		$this->table = Datatables::createStandAloneTable($parameters);

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

		$this->manageTableButtons();
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
			$helper = new $this->fieldsGroupsParametersFile();

			return [
				'base' => $helper->getFieldsGroup()
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

	public function hasCreateButton()
	{
		if (! is_null($this->hasCreateButton))
			return $this->hasCreateButton;

		return config('crud.realtionshipManagers.createButton', false);
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function manageTableButtons()
	{
		if ($this->hasCreateButton())
		{
			if ($this->isPolimorphicParentingRelationship())
			{
				$this->table->addButton(
					$this->relatedModel->getCreateByPolimorphicRelatedButton(
						$this->getParentModel()
					)
				);

			}
			else if ($this->isParentingRelationship())
			{
				$this->table->addButton(
					$this->relatedModel->getCreateByRelatedButton(
						$this->getParentModel(), $this->relatedModel
					)
				);
			}
		}

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