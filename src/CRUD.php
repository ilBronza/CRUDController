<?php

namespace ilBronza\CRUD;

use App\Providers\Helpers\dgButton;
use Illuminate\Support\Str;
use \App\Http\Controllers\Controller;
use ilBronza\CRUD\Traits\CRUDMethodsTrait;
use ilBronza\CRUD\Traits\CRUDRoutingTrait;

class CRUD extends Controller
{
	use CRUDRoutingTrait;
	use CRUDMethodsTrait;

	//general parameters
	public $modelClass;
	public $allowedMethods;
	public $neededTraits = ['ilBronza\CRUD\Traits\Model\CRUDModelTrait'];
	public $extraViews = [];

	// index parameters
	public $indexFieldsGroups = ['index'];
	public $indexCacheKey;


	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	public function __construct()
	{
		parent::__construct();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));
		$this->middleware('CRUDCanDelete:' . $this->modelClass)->only(['destroy', 'forceDelete']);

		$this->checkIfModelUsesTrait();
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
	public function getCreateButton() : dgButton
	{
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
}