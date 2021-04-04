<?php

namespace ilBronza\CRUD;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ilBronza\CRUD\Traits\CRUDBelongsToButtonsTrait;
use ilBronza\CRUD\Traits\CRUDBelongsToRoutingTrait;

class BelongsToCRUDController extends CRUD
{
	use CRUDBelongsToButtonsTrait;
	use CRUDBelongsToRoutingTrait;

    public function __construct()
    {
        parent::__construct();

        $this->parentModel = $this->parentModelClass::findOrFail(
            Route::current()->parameter(
            	Str::camel(class_basename($this->parentModelClass))
            )
        );
    }

	public function getParentModelFullClassName()
	{
		return '\App\\' . $this->parentModelClass;
	}

	public function getParentModelRelationshipsNames()
	{
		return $this->parentModelRelationships;
	}

	public function loadParentModelRelationships()
	{
		$this->parentModel->load(
			$this->getParentModelRelationshipsNames()
		);
	}

	public function getParentModelKey()
	{
		$kebabRelation = Str::slug(Str::kebab(class_basename($this->parentModel)), '_');

		return $kebabRelation . '_id';
	}

	public function associateParentModel()
	{
		$parentModelKey = $this->getParentModelKey();
		$this->modelInstance->{$parentModelKey} = $this->parentModel->getKey();
	}

	public function loadParentModel()
	{
		$parentModelClass = $this->getParentModelFullClassName();

        $this->parentModel = $parentModelClass::findOrFail(
            Route::current()->parameter(lcfirst($this->parentModelClass))
        );

        $this->loadParentModelRelationships();

        $this->addView('top', 'crud::uikit._teaser', [
            'teaserModel' => $this->parentModel,
            'teaserModelFields' => $this->getTeaserParentModelFields(),
            'teaserModelRelationships' => $this->getParentModelRelationshipsNames()
        ]);

        $this->shareExtraViews();		
	}

	public function getTeaserParentModelFields()
	{
		return $this->getDBFieldsByType('teaser', $this->parentModel);
	}
}