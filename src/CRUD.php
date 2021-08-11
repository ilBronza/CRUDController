<?php

namespace IlBronza\CRUD;

use App\Providers\Helpers\dgButton;
use IlBronza\CRUD\Traits\CRUDFormTrait;
use IlBronza\CRUD\Traits\CRUDMethodsTrait;
use IlBronza\CRUD\Traits\CRUDRoutingTrait;
use Illuminate\Support\Str;
use \App\Http\Controllers\Controller;

class CRUD extends Controller
{
	use CRUDFormTrait;
	use CRUDRoutingTrait;
	use CRUDMethodsTrait;

	//general parameters
	public $modelClass;
	public $allowedMethods;
	public $neededTraits = ['IlBronza\CRUD\Traits\Model\CRUDModelTrait'];
	public $extraViews = [];

    public $returnBack = false;
    public $avoidBackToList = false;
    public $showFormIntro = true;

	// index parameters
	public $indexFieldsGroups = ['index'];
	public $archivedFieldsGroups = ['archived'];
	public $indexCacheKey;

	public $editFormDivider = false;
	public $createFormDivider = false;


	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	public function __construct()
	{
		if (method_exists(parent::class, '__construct'))
			parent::__construct();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));
		$this->middleware('CRUDCanDelete:' . $this->modelClass)->only(['destroy', 'forceDelete']);

		//perchè si applica solo se non viene usato il metodo only()???
		$this->middleware('CRUDPareseAjaxBooleansAndNull');

		$this->checkIfModelUsesTrait();
	}

	public function mustReturnBack()
	{
		return !! $this->returnBack;
	}

	public function manageReturnBack() : ? string
	{
		if(! $this->mustReturnBack())
			return null;

		return $this->setReturnUrl(url()->previous());
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
		// 	if(! in_array($neededTrait, class_uses(new $this->modelClass())))
		// 		throw new \Exception('add ' . $neededTrait . ' to model ' . $this->modelClass);
	}

	/**
	 * get controller's model translation file name prefix
	 *
	 * @return string
	 **/
	protected function getModelTranslationFileName() : string
	{
		$classNamePieces = explode('\\', $this->modelClass);
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
		if(! $this->modelClass)
			throw new \Exception('public $modelClass non dichiarato nella classe estesa ' . get_class($this));

		return class_basename($this->modelClass);
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
	 * @return dgButton
	 */
	public function getCreateNewModelButton() : dgButton
	{
		if(isset($this->parentModel))
			return $this->modelClass::getCreateChildButton($this->parentModel);

		return $this->modelClass::getCreateButton();
	}

	/**
	 * add extra view to default page. can be used in index, show, create, edit
	 *
	 * @param string $position
	 * @param string $view //view name
	 * @param array $parameters //view parameters
	 **/
	public function addView(string $position, string $view, array $parameters = [])
	{
		if(empty($this->extraViews[$position]))
			$this->extraViews[$position] = [];

		$this->extraViews[$position][$view] = $parameters;		
	}

	/**
	 * share extraViews parameter to view
	 **/
	public function shareExtraViews()
	{
		view()->share('extraViews', $this->extraViews);
	}

	public function avoidBackToList()
	{
		return $this->avoidBackToList;
	}
}