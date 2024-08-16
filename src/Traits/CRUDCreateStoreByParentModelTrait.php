<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Traits\CRUDCreateStoreByParentModelPolimorphicTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait CRUDCreateStoreByParentModelTrait
{
    use CRUDCreateStoreTrait;
    use CRUDCreateStoreByParentModelPolimorphicTrait;
	/**
	 * 
	 * 
	 * 
	 * 
	 * 
	 * 
	 * 
	 * 
	 * 
	 **/


    /**
     * return all possible models class that can be associated to the model
     * 
     * @return array
     * 
     * es. return [
     * 		User::getProjectClassName(),
     * 		App\Models\Car::class
     * ]
     **/
    abstract function getAssociableClassesList() : array;

    public function _getModelDefaultParameters() : array
    {
        if($this->hasCreatingPolimorphicRelationship())
            return $this->getModelDefaultPolimorphicParameters();

        abort(500, 'GESTISCI QUESTO DAI');

        // $result = [
        //     'registered_at' => Carbon::now()
        // ];

        // if($this->vehicle ?? false)
        //     $result['vehicle_id'] = $this->vehicle->getKey();

        return $result;
    }

    /**
     * overridden from base CreateStoreTrait
     **/

    public function getModelDefaultParameters() : array
    {
    	return $this->_getModelDefaultParameters();
    }

    public function setStoreByProperties(string $model, string $key)
    {
        $this->relatedModel = $model;
        $this->relatedKey = $key;
    }

    public function createBy(string $model, string $key)
    {
        $this->setStoreByProperties($model, $key);

        return $this->create();
    }

    public function storeBy(Request $request, string $model, string $key)
    {
        $this->setStoreByProperties($model, $key);

        return $this->store($request);
    }

    public function getStoreModelAction()
    {
        return $this->getRouteUrlByType('store', [
            'model' => $this->relatedModel,
            'key' => $this->relatedKey
        ]);
    }

    public function getParentModel() : Model
    {
        if($this->polimorphicParentModel)
            return $this->polimorphicParentModel;

        die('ritorna il model padre non polimorfico');
    }

    public function getAfterStoredRedirectUrl()
    {
        return $this->getParentModel()->getShowUrl();
    }
}