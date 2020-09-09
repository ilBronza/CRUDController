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
		if(! empty($this->showMethodRelationships))
			return $this->showMethodRelationships;

		return array_keys($this->getRelationTypeFieldsByFormTypes(['create', 'edit']));
	}

	private function getRelationControllerName(string $value)
	{
		if(class_exists($className = '\App\Http\Controllers\\' . ucfirst(Str::singular($value)) . 'Controller'))
			return $className;

		if(! isset($this->relationshipsControllers[$value]))
			throw new MissingRelationshipDeclarationController($value);

		return $this->relationshipsControllers[$value];
	}

	private function addRelationshipTable(Collection $related, string $name)
	{
		if(count($related) == 0)
			return null;

		$controllerName = $this->getRelationControllerName($name);

		$minitable = new minitable($name, class_basename($related->first()), ['index'], null, $related, $controllerName);

		$this->relationshipsTableNames[$name] = $minitable;
	}

	private function relationNeedsTable($related)
	{
		return is_countable($related);
	}

	private function addRelationshipElement(Model $related, string $name)
	{
		$this->relationshipsElements[$name] = $related;
	}

	private function shareRelationships()
	{
		$relationships = $this->getShowRelationships();

		$this->modelInstance->load($relationships);

		$this->relationshipsTableNames = [];
		$this->relationshipsElements = [];

		foreach($relationships as $name)
		{
			$related = $this->modelInstance->{$name};

			if(! $related)
				continue;

			if($this->relationNeedsTable($related))
				$this->addRelationshipTable($related, $name);
			else
				$this->addRelationshipElement($related, $name);
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
}