<?php

namespace ilBronza\CRUD;

use Illuminate\Support\Str;
use \App\http\Controllers\Controller;
use ilBronza\CRUD\Traits\CRUDRoutingTrait;

class CRUD extends Controller
{
	use CRUDRoutingTrait;

	//general parameters
	public $modelClass;
	public $allowedMethods;
	public $neededTraits = ['ilBronza\CRUD\Traits\Model\CRUDModelTrait'];
	public $extraViews = [];

	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	public function __construct()
	{
		parent::__construct();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));
		$this->middleware('CRUDCanDelete:' . $this->modelClass)->only(['delete', 'destroy', 'forceDelete']);

		$this->checkIfModelUsesTrait();
	}

	private function checkIfModelUsesTrait()
	{
		foreach($this->neededTraits as $neededTrait)
			if(! in_array($neededTrait, class_uses(new $this->modelClass())))
				throw new \Exception('add ' . $neededTrait . ' to model ' . $this->modelClass);
	}

	private function getAllowedMethods()
	{
		if(! $this->allowedMethods)
			throw new \Exception('public $allowedMethods non dichiarato nella classe estesa ' . get_class($this));

		return $this->allowedMethods;
	}

	/**
	 * get controller's model translation file name prefix
	 *
	 * @return string
	 **/
	protected function getModelTranslationFileName()
	{
		$classNamePieces = explode('\\', $this->modelClass);
		$className = array_pop($classNamePieces);

		return Str::plural(lcfirst($className));
	}

	public function getModelClassBasename()
	{
		if(! $this->modelClass)
			throw new \Exception('public $modelClass non dichiarato nella classe estesa ' . get_class($this));

		return $this->modelClass;
	}

	public function getLcfirstPluralModelClassname($modelInstance)
	{
		return lcfirst(Str::plural(class_basename($modelInstance)));
	}

	public function getCreateButton()
	{
		return $this->modelClass::getCreateButton();
	}

	public function addView(string $position, string $view, array $parameters = [])
	{
		if(empty($this->extraViews[$position]))
			$this->extraViews[$position] = [];

		$this->extraViews[$position][$view] = $parameters;		
	}

	public function shareExtraViews()
	{
		view()->share('extraViews', $this->extraViews);
	}
}