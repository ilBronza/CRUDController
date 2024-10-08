<?php

namespace IlBronza\CRUD;

use App\Http\Controllers\Controller;
use Exception;
use IlBronza\Buttons\Button;
use IlBronza\CRUD\Helpers\ModelManagers\CrudModelFormHelper;
use IlBronza\CRUD\Middleware\CRUDConcurrentUrlAlert;
use IlBronza\CRUD\Middleware\CRUDParseComasAndDots;
use IlBronza\CRUD\Traits\CRUDFileParametersTrait;
use IlBronza\CRUD\Traits\CRUDFormTrait;
use IlBronza\CRUD\Traits\CRUDMethodsTrait;
use IlBronza\CRUD\Traits\CRUDRoutingTrait;
use IlBronza\CRUD\Traits\Model\CRUDCacheAutomaticSetterTrait;
use IlBronza\Form\Traits\ExtraViewsTrait;
use IlBronza\UikitTemplate\Fetcher;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

use function config;
use function ini_set;

class CRUD extends Controller
{
	use ExtraViewsTrait;

	static $availableExtraViewsPositions = [
		'outherTop',
		'outherBottom',
		'innerTop',
		'innerBottom',
		'left',
		'right',
		'outherLeft',
		'outherRight'
	];

	public $mustPrintIntestation = null;
	public $relationshipsElements;
	public $relationshipsTableNames;
	public ?Model $modelInstance;
	public ?bool $updateEditor = null;
	public $modelFormHelper;
	public Collection $fetchers;
	public $debug = false;

	use CRUDFileParametersTrait;
	use CRUDFormTrait;
	use CRUDRoutingTrait;
	use CRUDMethodsTrait;

	public $iframed;
	public $modelClass;
	public $neededTraits = ['IlBronza\CRUD\Traits\Model\CRUDModelTrait'];

	//general parameters
	public $extraViews = [];
	public $pageLength;
	public $returnBack = false;
	public $avoidBackToList = false;
	public $avoidShowButton = false;
	public $showFormIntro = true;
	public $rowSelectCheckboxes = false;
	public $indexFieldsGroups = ['index'];
	public $archivedFieldsGroups = ['archived'];

	// index parameters
	public $indexCacheKey;
	public $canEditModelInstance = true;
	public $editModelInstanceUrl;

	//show parameters
	public $editFormDivider = false;
	public $createFormDivider = false;
	public $relationshipManager;
	public $showStickyButtonsNavbar = false;
	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	public function __construct()
	{
		if (method_exists(parent::class, '__construct'))
			parent::__construct();

		ini_set('max_execution_time', 40);
		ini_set('memory_limit', - 1);

		$this->setModelClass();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));

		if ((in_array('destroy', $this->getAllowedMethods())) || (in_array('forceDelete', $this->getAllowedMethods())))
			$this->middleware('CRUDCanDelete:' . $this->getModelClass())->only(['destroy', 'forceDelete']);

		if (config('crud.useConcurrentRequestsAlert'))
			$this->middleware(CRUDConcurrentUrlAlert::class);

		//perchè si applica solo se non viene usato il metodo only()???
		$this->middleware('CRUDParseAjaxBooleansAndNull');
		$this->middleware(CRUDParseComasAndDots::class);

		$this->checkIfModelUsesTrait();

		$this->setFetchers();
	}

	/**
	 * get subject model class
	 *
	 * @return string
	 **/
	public function getModelClass() : string
	{
		if (! $this->modelClass)
			throw new Exception('public $modelClass non dichiarato nella classe estesa ' . get_class($this));

		return $this->modelClass;
	}

	/**
	 * function to set $modelClass dinamically
	 **/
	public function setModelClass()
	{
		// example
		// $this->modelClass = config('someting.model');
	}

	private function checkIfModelUsesTrait()
	{
		//TODO RISOLVERE STA ROBA
		// foreach($this->neededTraits as $neededTrait)
		// 	if(! in_array($neededTrait, class_uses(new ($this->getModelClass())())))
		// 		throw new \Exception('add ' . $neededTrait . ' to model ' . $this->getModelClass());
	}

	public function getValidExtraViewsPositions() : array
	{
		return static::$availableExtraViewsPositions;
	}

	public function debugMode() : bool
	{
		return $this->debug;
	}

	public function isIframed()
	{
		return request()->input('iframed', false);
	}

	public function setReturnUrlToPrevious()
	{
		return $this->setReturnUrl(
			url()->previous()
		);
	}

	public function setReturnUrl(string $url) : string
	{
		$classKey = static::getClassKey();
		session([$classKey => $url]);

		return $classKey;
	}

	static function getClassKey() : string
	{
		return Str::slug(static::class);
	}

	public function manageReturnBack() : ?string
	{
		if (! $this->mustReturnBack())
			return null;

		return $this->setReturnUrlIfEmpty(
			url()->previous()
		);
	}

	public function mustReturnBack() : bool
	{
		return ! ! $this->returnBack;
	}

	public function setReturnUrlIfEmpty(string $url)
	{
		if (! $this->checkReturnUrl())
			$this->setReturnUrl(
				$url
			);
	}

	public function checkReturnUrl() : bool
	{
		$classKey = static::getClassKey();
		$url = session($classKey, null);

		return ! ! $url;
	}

	public function getReturnUrl() : ?string
	{
		$classKey = static::getClassKey();

		$url = session($classKey, null);
		session()->forget($classKey);

		return $url;
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

	public function findModel(string $key, array $relations = []) : ?Model
	{
		$query = $this->getFindModelQuery($key, $relations);

		return $query->find($key);
	}

	public function getFindModelQuery(string $key, array $relations = []) : Builder
	{
		$query = $this->getModelClass()::query();

		foreach ($relations as $relation)
			$query->with($relation);

		return $query;
	}

	public function findModelWithTrashed(string $key, array $relations = []) : ?Model
	{
		$query = $this->getFindModelQuery($key)->onlyTrashed();

		return $query->find($key);
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
		if (isset($this->parentModel))
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
	 * @param  string  $position
	 * @param  string  $view        //view name
	 * @param  array   $parameters  //view parameters
	 **/
	public function addFormExtraView(string $position, string $view, array $parameters = [])
	{
		$this->getModelFormHelper()->getForm()->addExtraView($position, $view, $parameters);
	}

	public function getModelFormHelper() : CrudModelFormHelper
	{
		return $this->modelFormHelper;
	}

	public function addFormFetcher(string $position, Fetcher $fetcher)
	{
		return $this->getModelFormHelper()->getForm()->addFetcher($position, $fetcher);
	}

	public function provideFormDefaultSettings() : array
	{
		$defaults = [];

		if (in_array('index', $this->allowedMethods) && (! $this->avoidBackToList()))
			$defaults['backToListUrl'] = $this->getIndexUrl();

		if (in_array('show', $this->allowedMethods) && (! $this->avoidShowButton()) && ($this->getModel()?->exists))
			$defaults['showElement'] = $this->getShowUrl();

		$defaults['saveAndNew'] = $this->hasSaveAndNew();
		$defaults['saveAndRefresh'] = $this->hasSaveAndRefresh();
		$defaults['saveAndCopy'] = $this->hasSaveAndCopy();

		$defaults['updateEditor'] = $this->hasUpdateEditor();

		return $this->overrideWithCustomSettingsToDefaults($defaults);
	}

	public function avoidBackToList()
	{
		return $this->avoidBackToList;
	}

	public function avoidShowButton() : bool
	{
		return $this->avoidShowButton;
	}

	public function getModel() : ?Model
	{
		return $this->modelInstance;
	}

	public function hasUpdateEditor()
	{
		if (is_null($this->updateEditor))
			return config('form.updateEditor', false);

		return $this->updateEditor;
	}

	public function overrideWithCustomSettingsToDefaults(array $settings) : array
	{
		return $settings;
	}

	public function setModel(Model $model)
	{
		$this->modelInstance = $model;

		$this->modelInstance->setRouteBaseNamePrefix(
			$this->getRouteBaseNamePrefix()
		);
	}

	public function makeModel() : Model
	{
		$model = $this->getModelClass()::make();

		$parameters = $this->addParentModelAssociationParameter(
			$this->getModelDefaultParameters()
		);

		foreach ($parameters as $key => $value)
			$model->$key = $value;

		return $model;
	}

	public function getModelDefaultParameters() : array
	{
		return [];
	}

	public function modelHasAutomaticCache() : bool
	{
		return in_array(
			CRUDCacheAutomaticSetterTrait::class, class_uses_recursive($this->getModelClass())
		);
	}

	public function shareExtraViews()
	{
		if (count($this->extraViews))
		{
			throw new Exception('GESTIRE EXTRA VIEW PER SHOW E INDEX');
			view()->share('extraViews', $this->extraViews);
		}
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

}