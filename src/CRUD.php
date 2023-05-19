<?php

namespace IlBronza\CRUD;

use IlBronza\Buttons\Button;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\CRUD\Middleware\CRUDConcurrentUrlAlert;
use IlBronza\CRUD\Middleware\CRUDParseComasAndDots;
use IlBronza\CRUD\Traits\CRUDFileParametersTrait;
use IlBronza\CRUD\Traits\CRUDFormTrait;
use IlBronza\CRUD\Traits\CRUDMethodsTrait;
use IlBronza\CRUD\Traits\CRUDRoutingTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use \App\Http\Controllers\Controller;

class CRUD extends Controller
{
	use CRUDFileParametersTrait;
	use CRUDFormTrait;
	use CRUDRoutingTrait;
	use CRUDMethodsTrait;

	//general parameters
	public $modelClass;
	public $allowedMethods;
	public $neededTraits = ['IlBronza\CRUD\Traits\Model\CRUDModelTrait'];
	public $extraViews = [];

	public $pageLength = 50;

    public $returnBack = false;
    public $avoidBackToList = false;
    public $showFormIntro = true;

    public $rowSelectCheckboxes = false;

	// index parameters
	public $indexFieldsGroups = ['index'];
	public $archivedFieldsGroups = ['archived'];
	public $indexCacheKey;

	//show parameters
	public $canEditModelInstance = true;
	public $editModelInstanceUrl;

	public $editFormDivider = false;
	public $createFormDivider = false;
	public $relationshipManager;

	public $showStickyButtonsNavbar = false;


	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	/**
	 * function to set $modelClass dinamically 
	 **/
	public function setModelClass()
	{
		// example
		// $this->modelClass = config('someting.model');
	}

	public function __construct()
	{
		if (method_exists(parent::class, '__construct'))
			parent::__construct();

		$this->setModelClass();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));

		if((in_array('destroy', $this->getAllowedMethods()))||(in_array('forceDelete', $this->getAllowedMethods())))
			$this->middleware('CRUDCanDelete:' . $this->getModelClass())->only(['destroy', 'forceDelete']);

		if(config('crud.useConcurrentRequestsAlert'))
			$this->middleware(CRUDConcurrentUrlAlert::class);

		//perchè si applica solo se non viene usato il metodo only()???
		$this->middleware('CRUDParseAjaxBooleansAndNull');
		$this->middleware(CRUDParseComasAndDots::class);
		$this->checkIfModelUsesTrait();
	}

	public function isIframed()
	{
		return request()->input('iframed', false);
	}

	public function mustReturnBack() : bool
	{
		return !! $this->returnBack;
	}

	public function setReturnUrlToPrevious()
	{
		return $this->setReturnUrl(
			url()->previous()
		);
	}

	public function setReturnUrlIfEmpty(string $url)
	{
		if(! $this->checkReturnUrl())
			$this->setReturnUrl(
				$url
			);
	}

	public function manageReturnBack() : ? string
	{
		if(! $this->mustReturnBack())
			return null;

		return $this->setReturnUrlIfEmpty(
			url()->previous()
		);
	}

	static function getClassKey() : string
	{
		return Str::slug(static::class);
	}

	public function setReturnUrl(string $url) : string
	{
		$classKey = static::getClassKey();
		session([$classKey => $url]);

		return $classKey;
	}

	public function checkReturnUrl() : bool
	{
		$classKey = static::getClassKey();
		$url = session($classKey, null);

		return !! $url;
	}

	public function getReturnUrl() : ? string
	{
		$classKey = static::getClassKey();

		$url = session($classKey, null);
		session()->forget($classKey);

		return $url;
	}

	private function checkIfModelUsesTrait()
	{
		//TODO RISOLVERE STA ROBA
		// foreach($this->neededTraits as $neededTrait)
		// 	if(! in_array($neededTrait, class_uses(new ($this->getModelClass())())))
		// 		throw new \Exception('add ' . $neededTrait . ' to model ' . $this->getModelClass());
	}

	/**
	 * get controller's model translation file name prefix
	 *
	 * @return string
	 **/
	protected function getModelTranslationFileName() : string
	{
		$classNamePieces = explode('\\', $this->getModelClass());
		$className = array_pop($classNamePieces);

		return Str::plural(Str::camel($className));
	}

	/**
	 * get subject model class's basename
	 *
	 * @return string
	 **/
	public function getModelClassBasename() : string
	{
		return class_basename($this->getModelClass());
	}

	/**
	 * get subject model class
	 *
	 * @return string
	 **/
	public function getModelClass() : string
	{
		if(! $this->modelClass)
			throw new \Exception('public $modelClass non dichiarato nella classe estesa ' . get_class($this));

		return $this->modelClass;
	}

	/**
	 * return camel plural class's basename used for routing
	 *
	 * example App\Models\Commercials\CommercialDocument becomes commercialDocuments
	 *
	 * @param $modelInstance
	 *
	 * @return string
	 **/
	public function getLcfirstPluralModelClassname($modelInstance) : string
	{
		return (Str::plural(
			Str::camel(
				class_basename($modelInstance)
			)
		));
	}

	/**
	 * return a button to create new given model instance
	 *
	 * @return Button
	 */
	public function getCreateNewModelButton() : Button
	{
		if(isset($this->parentModel))
			return $this->getModelClass()::getCreateChildButton($this->parentModel);

		return $this->getModelClass()::getCreateButton();
	}

	public function getReorderButton() : Button
	{
		return $this->getModelClass()::getReorderButton();		
	}

	/**
	 * add extra view to default page. can be used in index, show, create, edit
	 *
	 * @param string $position
	 * @param string $view //view name
	 * @param array $parameters //view parameters
	 **/
	public function addFormExtraView(string $position, string $view, array $parameters = [])
	{
		$this->getModelFormHelper()->getForm()->addExtraView($position, $view, $parameters);
	}

	public function getModelFormHelper() : CrudModelFormHelper
	{
		return $this->modelFormHelper;
	}

	/**
	 * share extraViews parameter to view
	 **/
	public function shareExtraViews()
	{
		if(count($this->extraViews))
		{
			throw new \Exception('GESTIRE EXTRA VIEW PER SHOW E INDEX');
			view()->share('extraViews', $this->extraViews);	
		}
	}

	public function avoidBackToList()
	{
		return $this->avoidBackToList;
	}




	public function overrideWithCustomSettingsToDefaults(array $settings) : array
	{
		return $settings;
	}

	public function provideFormDefaultSettings() : array
	{
		$defaults = [];

		if(in_array('index', $this->allowedMethods)&&(! $this->avoidBackToList()))
			$defaults['backToListUrl'] = $this->getIndexUrl();

		$defaults['saveAndNew'] = $this->hasSaveAndNew();
		$defaults['saveAndRefresh'] = $this->hasSaveAndRefresh();

		return $this->overrideWithCustomSettingsToDefaults($defaults);
	}


	public function getModelDefaultParameters() : array
	{
		return [];
	}

	public function setModel(Model $model)
	{
		$this->modelInstance = $model;

		$this->modelInstance->setRouteBaseNamePrefix(
			$this->getRouteBaseNamePrefix()
		);
	}

	public function getModel() : Model
	{
		return $this->modelInstance;
	}

	public function makeModel() : Model
	{
		$parameters = $this->addParentModelAssociationParameter(
			$this->getModelDefaultParameters()
		);

		return $this->getModelClass()::make(
			$parameters
		);
	}





}