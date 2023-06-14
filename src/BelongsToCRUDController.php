<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\Traits\CRUDBelongsToButtonsTrait;
use IlBronza\CRUD\Traits\CRUDBelongsToRoutingTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BelongsToCRUDController extends CRUD
{
	use CRUDBelongsToButtonsTrait;
	use CRUDBelongsToRoutingTrait;

    public function __construct()
    {
        parent::__construct();

        $this->middleware(function ($request, $next)
        {
			if(! $parentKey = Route::current()->parameter('parent'))
				$parentKey = Route::current()->parameter(
					Str::camel(class_basename($this->parentModelClass))
				);

			if($parentKey instanceof $this->parentModelClass)
				$this->parentModel = $parentKey;

			else
				$this->parentModel = $this->parentModelClass::findOrFail($parentKey);

			$this->manageParentTeaserDisplay();

			return $next($request);
        });
    }

    public function mustDisplayParentModel()
    {
    	if(isset($this->mustDisplayParentModel))
    		return $this->mustDisplayParentModel;

    	return true;
    }

    public function getParentModelTeaserView()
    {
    	return $this->parentModelTeaserView ?? 'crud::uikit._parentTeaser';
    }

    public function getParentModelTeaserAttributes()
    {
    	if(method_exists($this, 'getControllerSpecificParentModelTeaserAttributes'))
    		return $this->getControllerSpecificParentModelTeaserAttributes();

    	return $this->parentModel->getParentingAttributes();
    }

    public function getParentModelTeaserViewParameters()
    {
    	return [
    		'parentModel' => $this->parentModel,
    		'parentModelTeaserAttributes' => $this->getParentModelTeaserAttributes()
    	];
    }

    public function manageParentTeaserDisplay()
    {
    	if(! $this->mustDisplayParentModel())
    		return ;

		view()->share('parentModelTeaser', [
			'view' => $this->getParentModelTeaserView(),
			'parameters' => $this->getParentModelTeaserViewParameters()
		]);
    }

	// public function getParentModelFullClassName()
	// {
	// 	return '\App\\' . $this->parentModelClass;
	// }

	// public function getParentModelRelationshipsNames()
	// {
	// 	return $this->parentModelRelationships;
	// }

	// public function loadParentModelRelationships()
	// {
	// 	$this->parentModel->load(
	// 		$this->getParentModelRelationshipsNames()
	// 	);
	// }

	public function getParentModelKey()
	{
		if(isset($this->parentModelKey))
			return $this->parentModelKey;

		$kebabRelation = Str::slug(Str::kebab(class_basename($this->parentModel)), '_');

		return $kebabRelation . '_id';
	}

	public function associateParentModel(array $parameters) : array
	{
		$parentModelKey = $this->getParentModelKey();
		$parameters[$parentModelKey] = $this->parentModel->getKey();

		return $parameters;
	}

	// public function loadParentModel()
	// {
	// 	$parentModelClass = $this->getParentModelFullClassName();

 //        $this->parentModel = $parentModelClass::findOrFail(
 //            Route::current()->parameter(lcfirst($this->parentModelClass))
 //        );

 //        $this->loadParentModelRelationships();

 //        $this->addView('top', 'crud::uikit._teaser', [
 //            'teaserModel' => $this->parentModel,
 //            'teaserModelFields' => $this->getTeaserParentModelFields(),
 //            'teaserModelRelationships' => $this->getParentModelRelationshipsNames()
 //        ]);

 //        $this->shareExtraViews();		
	// }

	// public function getTeaserParentModelFields()
	// {
	// 	return $this->getDBFieldsByType('teaser', $this->parentModel);
	// }
}