<?php

namespace ilBronza\CRUD;

use Illuminate\Support\Str;
use \App\http\Controllers\Controller;

class CRUD extends Controller
{
	//general parameters
	public $modelClass;
	public $allowedMethods;

	public $middlewareGuardedMethods = ['index', 'edit', 'update', 'create', 'store', 'delete'];

	public function __construct()
	{
		parent::__construct();

		$this->middleware('CRUDAllowedMethods:' . implode(",", $this->getAllowedMethods()));
		$this->checkIfModelUsesTrait();
	}

	private function checkIfModelUsesTrait()
	{
		if(! in_array('ilBronza\CRUD\Traits\Model\CRUDModelTrait', class_uses(new $this->modelClass())))
			throw new \Exception('add CRUDModelTrait to model ' . $this->modelClass);
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

	public function getPluralModelClassname($modelInstance)
	{
		return lcfirst(Str::plural(class_basename($modelInstance)));
	}
}