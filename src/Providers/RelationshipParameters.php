<?php

namespace IlBronza\CRUD\Providers;

use IlBronza\CRUD\Providers\RelationshipsManager;
use IlBronza\Datatables\Datatables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\View;

class RelationshipParameters
{
	public $name;
	public $relation;
	public $relationType;
	public $elements;
	public $controller;
	public $buttons;
	public $currentView;
	public $extraVariables;
	public $translatedTitle;
	public $buttonsMethods = [];
	public $elementGetterMethod;




	//relation-specific custom view name
	public $view;

	public $fieldsGroupsNames = ['related'];
	public $selectRowCheckboxes = true;

	public $relationshipsManager;

	static $standardView = 'crud::uikit._teaser';

	static $renderAsTypes = [
		'BelongsToMany' => 'table',
		'HasMany' => 'table',
		'BelongsTo' => 'view'
	];

	public function __construct(string $name, array $parameters, RelationshipsManager $relationshipsManager)
	{
		$this->name = $name;
		$this->relationshipsManager = $relationshipsManager;

		$this->manageParameters($parameters);
		$this->setRenderingType();
	}

	public function getCardTitle()
	{
		if($this->translatedTitle)
			return $this->translatedTitle;

		return __('relationships.' . $this->getRelationshipMethod());
	}

	/**
	 * set parameters and build default relation parameters
	 **/
	private function manageParameters(array $parameters)
	{
		foreach($parameters as $name => $value)
			$this->{$name} = $value;

		$this->manageRelation($parameters);
	}

	/**
	 * set relation property
	 *
	 * @param $relation | string
	 **/
	public function setRelation(string $relation)
	{
		$this->relation = $relation;
	}

	/** 
	 * set relation name if given, otherwise set name as relation
	 *
	 * @param $parameters | array
	 **/
	private function manageRelation(array $parameters)
	{
		if(isset($parameters['relation']))
			$this->setRelation($parameters['relation']);

		else
			$this->setRelation($this->name);
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

	/**
	 * get default render method by relation type
	 *
	 * a many relation returns 'table', a single relation returns 'view'
	 *
	 * @param $relationType | string
	 *
	 * @return string
	 **/
	public function getRenderAsByRelationType(string $relationType) : string
	{
		return static::$renderAsTypes[$relationType];
	}

	/**
	 * set rendering type by given or default
	 *
	 * @param $type | string 
	 *
	 * @return string
	**/
	public function setRenderingType(string $type = null) : string
	{
		if($type)
			return $this->renderAs = $type;

		$relationType = $this->getRelationType();

		return $this->renderAs = $this->getRenderAsByRelationType($relationType);
	}

	/** 
	 * set related model classname by related model instance
	 **/
	private function setRelatedModelClass()
	{
		$this->relatedModelClass = get_class($this->relatedModel);
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
	 * set all relation properties by relation type
	 *
	 * set $relationType => as BelongsToMane, BelongsTo, etc.
	 * set related model instance => ex. post
	 * set renderingType
	 **/
	public function setRelationType()
	{
		$relationMethod = $this->getRelationshipMethod();

		$this->eloquentRelationship = $this->relationshipsManager->model->{$relationMethod}();
		$this->relationType = class_basename($this->eloquentRelationship);

		$this->setRelatedModel();

		$this->setRenderingType();
	}

	/**
	 * get eloquent relation type 
	 *
	 * @return string
	 **/
	public function getRelationType() : string
	{
		if(! $this->relationType)
			$this->setRelationType();

		return $this->relationType;
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

	/**
	 * check if relation must be rendered as a view
	 *
	 * @return bool
	 **/
	public function renderAsView() : bool
	{
		return $this->renderAs == 'view';
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

	public function getElementGetterMethod()
	{
		return $this->elementGetterMethod;
	}

	/**
	 * set relation's elements by given or load defaults
	 *
	 * if given, set elements collection
	 * if missing, get the relation method and lazy load elements
	 *
	 * @param $elements | Collection
	 **/
	public function setElements(Collection $elements = null)
	{
		if($elements)
			return $this->elements = $elements;

		if($elementGetterMethod = $this->getElementGetterMethod())
			return $this->elements = $this->relationshipsManager->model->{$elementGetterMethod}();

		$relationMethod = $this->getRelationshipMethod();
		
		return $this->elements = $this->relationshipsManager->model->{$relationMethod};
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

		if(! $this->elements)
			$this->setElements();

		return $this->elements;
	}

	/**
	 * return single model relations instance
	 *
	 * get $elements property, if countable throw error because something went wrong, being this called on a single model relation 
	 *
	 * @return Model
	 **/
	public function getElement() : Model
	{
		$elements = $this->getElements();

		if(is_countable($elements))
			throw new \Exception('problema con numero di associazioni ad un belongsTo');

		return $elements;
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
	 * check if table must show selection checkboxes
	 *
	 * @return bool
	 **/
	public function hasSelectRowCheckboxes() : bool
	{
		return $this->selectRowCheckboxes;
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

	/** 
	 * return fieldsGroups controller's declared array
	 *
	 * get fieldsgroups names array, get the dedicated controller and ask for fields's array
	 *
	 * @return array
	 **/
	public function getTableFieldsGroups() : array
	{
		if($this->fieldsGroups ?? false)
			return $fieldsGroups;

		$fieldsGroupsNames = $this->getFieldsGroupsNames();

		return app($this->controller)->getTableFieldsGroups($fieldsGroupsNames);
	}

	public function getParentModel()
	{
		return $this->relationshipsManager->getModel();
	}

	public function manageTableButtons()
	{
		foreach($this->buttonsMethods as $buttonsMethod)
			$this->table->addButton(
				$this->relatedModel->{$buttonsMethod}(
					$this->getParentModel()
				)
			);
	}

	/**
	 * set elements table by class properties
	 **/
	public function setTable()
	{

		if(request()->rowId)
			if($this->name != 'quantities')
				return ;

		$parameters = [
			'name' => $this->getTableName(),
			'fieldsGroups' => $this->getTableFieldsGroups(),
			'elements' => $this->getElements(),
			'selectRowCheckboxes' => $this->hasSelectRowCheckboxes(),
			'extraVariables' => $this->getExtraVariables(),
			'modelClass' => $this->getRelatedModelClass()
		];

		$this->table = Datatables::createStandAloneTable($parameters);

		if(request()->ajax())
			return $this->table->renderPage();

		$this->table->setAjaxTable();

		$this->manageTableButtons();
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
	 * check if class must render standard view
	 *
	 * @return bool
	 **/
	public function hasStandardView() : bool
	{
		return $this->getView() == $this->getStandardView();
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
	public function getView() : string
	{
		if($this->currentView)
			return $this->currentView;

		if($this->hasOwnView())
			return $this->view;

		$modelFolderName = $this->getElement()->getRouteBasename();
		$view = $modelFolderName . '._teaser';

		if(View::exists($view))
			return $view;

		return $this->getStandardView();
	}

	/**
	 * set currentView by element property
	 **/
	public function setCurrentView()
	{
		$element = $this->getElement();
		$this->currentView = $this->getView();
	}

	/** 
	 * set relation rendering type and parameters based on relation type
	 *
	 * if more elements render as table, otherwise render as view
	 **/
	public function setShowParameters()
	{
		if($this->renderAsTable())
			return $this->setTable();

		return $this->setCurrentView();
	}

	private function renderTable()
	{
		return $this->table->renderPortion();
	}

	private function renderView()
	{
		if($this->hasStandardView())
			return view(
				$this->getView(),
				['teaserModel' => $this->getElement()]
			);

		$modelClassName = $this->getElement()->getRouteClassname();

		return view(
			$this->getView(),
			[
				$modelClassName => $this->getElement()
			]
		);
	}

	public function renderTableRowsArray()
	{
        $this->table = Datatables::create(
            $this->getTableName(),
            $this->getTableFieldsGroups(),
            $this->elementsGetter,
            $this->hasSelectRowCheckboxes(),
            $this->getExtraVariables(),
            $this->getRelatedModelClass()
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
		if($this->renderAsView())
			return $this->renderView();

		if($this->renderAsTable())
			return $this->renderTable();

		throw new \Exception ('No render method set on ' . class_basename($this));
	}
}