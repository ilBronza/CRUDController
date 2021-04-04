<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use \minitable;
use ilBronza\CRUD\Exceptions\MissingRelationshipDeclarationController;

trait CRUDShowRelationshipsTrait
{
	public function getShowRelationships()
	{
		if(isset($this->showMethodRelationships))
			return $this->showMethodRelationships;

		return array_keys($this->getRelationTypeFieldsByFormTypes(['create', 'edit']));
	}

	private function getRelationControllerName(string $value)
	{
		if(isset($this->relationshipsControllers[$value]))
			return $this->relationshipsControllers[$value];

		if(class_exists($className = '\App\Http\Controllers\\' . ucfirst(Str::singular($value)) . 'Controller'))
			return $className;

		throw new MissingRelationshipDeclarationController($value);
	}

	private function addRelationshipTable(Collection $elements, string $name)
	{
		if(count($elements) == 0)
			return null;

		$controllerName = $this->getRelationControllerName($name);

		// $minitable = new minitable($name, class_basename($elements->first()), ['elements'], null, $related, $controllerName);

		// $this->relationshipsTableNames[$name] = $minitable;

		$fieldsGroup = Str::camel(class_basename($this->modelClass));

		$this->relationshipsTableNames[$name] = app($controllerName)->getIndependentTable($elements, $fieldsGroup);
	}

	private function relationNeedsTable($related)
	{
		return is_countable($related);
	}

	public function getRelationshipType(string $relationship)
	{
		$relation = $this->modelInstance->{$relationship}();

		return class_basename($relation);
	}

	private function addRelationshipElement(Model $related, string $name)
	{
		$this->relationshipsElements[$name] = $related;
	}

	private function shareRelationships()
	{
		// ['account', 'roles', 'permissions', 'images']
		$relationships = $this->getShowRelationships();

		// $user->load(['account', 'roles', 'permissions', 'images'])
		$this->modelInstance->load($relationships);

		$this->relationshipsTableNames = [];
		$this->relationshipsElements = [];

		//['account', 'roles', 'permissions', 'images']
		foreach($relationships as $name)
		{
			$relatedElements = $this->modelInstance->{$name};

			if(! $relatedElements)
				continue;

			if($this->relationNeedsTable($relatedElements))
				$this->addRelationshipTable($relatedElements, $name);
			else
				$this->addRelationshipElement($relatedElements, $name);
		}

		view()->share('relationshipsElements', $this->relationshipsElements);
		view()->share('relationshipsTableNames', $this->relationshipsTableNames);
		view()->share('relationships', $relationships);
	}


	private function loadShowRelationshipsValues()
    {
    	dd($this->relatedFields);
        foreach($this->relatedFields as $relation => $fieldName)
        {
            $this->modelInstance->{$fieldName} = [];

            $elements = $this->modelInstance->{$relation}()->get();

            if(count($elements) == 0)
                continue;

            $this->modelInstance->{$fieldName} = $elements->pluck(
                $elements->first()->getKeyName()
            )->toArray();
        }
	}

	public function getCamelCaseSingularModelClass()
	{
		return lcfirst(class_basename($this->modelInstance));
	}

	public function getCamelCasePluralModelClass()
	{
		return Str::plural(lcfirst(class_basename($this->modelInstance)));
	}

	public function getHasManyRelationshipButton(string $relationship)
	{
		$modelClasses = $this->getCamelCasePluralModelClass();

		return route($modelClasses . '.' . $relationship .  '.create', [$this->modelInstance]);
	}

	public function getBelongsToRelationshipButton(string $relationship)
	{
		return false;
	}

	public function getBelongsToManyRelationshipButton(string $relationship)
	{
		return false;
	}
}