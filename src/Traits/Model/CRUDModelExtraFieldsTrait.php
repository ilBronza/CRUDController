<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;
use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Casts\ExtraFieldJson;
use IlBronza\CRUD\Providers\ExtraFields\ExtraFieldsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Log;

trait CRUDModelExtraFieldsTrait
{
    public function update(array $attributes = [], array $options = [])
    {
        if (! $this->exists) {
            return false;
        }

        foreach($attributes as $key => $value)
        	$this->$key = $value;

        return $this->save($options);
    }

	abstract function getExtraFieldsClass() : string;

	public function extraFields()
	{
		return $this->hasOne(
			$this->getExtraFieldsClass()
		);
	}

	public function getProjectExtraFieldsModel()
	{
		if($extraFields = $this->extraFields)
			return $extraFields;

		if(! $this->exists)
			throw new \Exception('Extra fields creato prima della persistenza del model base ' . class_basename($this) . '. SALVA il modello base per prima cosa');

		$extraFields = $this->extraFields()->create();

		$this->setRelation('extraFields', $extraFields);

		return $this->extraFields;
	}

	public function getCachedProjectExtraFieldsModel()
	{
		// return cache()->rememberForever(
		// 	$this->getExtraFieldsClass()::staticCacheKey($this->getKey() . $this->updated_at),
		// 	function ()
		// 	{
				// return $this->getProjectExtraFieldsModel();
				return $this->getProjectExtraFieldsModel();
		// 	}
		// );
	}

	public function getCachedProjectCustomExtraFieldsModel(string $customExtraAttributesModel)
	{
		if($this->relationLoaded($customExtraAttributesModel))
			return $this->{$customExtraAttributesModel};

		// return cache()->rememberForever(
		// 	$this->$customExtraAttributesModel()->make()::staticCacheKey($this->getKey() . $this->updated_at),
		// 	function () use($customExtraAttributesModel)
		// 	{
		// 		// if($model = $this->$customExtraAttributesModel()->first())
		// 		// {
		// 		// 	return $model;
		// 		// }

				$providerMethod = ExtraFieldsProvider::getExtraFieldsProviderMethod($customExtraAttributesModel);

				if(method_exists($this, $providerMethod))
					return $this->$providerMethod();

				throw new \Exception('Please declare ' . $providerMethod . ' inside ' . class_basename($this) . ' to provide custom extra fields');
		// 	}
		// );
	}

	public function getCustomExtraAttribute(string $customExtraAttributesModel, string $attribute)
	{
        $projectExtraFieldsModel = $this->getCachedProjectCustomExtraFieldsModel($customExtraAttributesModel);

        return $projectExtraFieldsModel->$attribute;
	}

	public function getExtraAttribute(string $attribute)
	{
        $projectExtraFieldsModel = $this->getCachedProjectExtraFieldsModel();

        return $projectExtraFieldsModel->$attribute;
	}

	public static function bootCRUDModelExtraFieldsTrait()
	{
		static::saving(function ($model) {

			$model->updated_at = Carbon::now();

			foreach($model->casts as $attribute => $casting)
				if(strpos($casting, "ExtraField") !== null)
					unset($model->$attribute);
		});

		static::saved(function ($model) {

			if(! $model->relationLoaded('extraFields'))
			{
				$model->getProjectExtraFieldsModel();
				$model->extraFields;				
			}

			$relationsToSave = ['extraFields'];

			foreach($model->casts as $attribute => $casting)
			{
				$pieces = explode(":", $casting);

				if(! ($castingParameters = ($pieces[1] ?? null)))
					continue;

				$castingParametersArray = explode(",", $castingParameters);

				if(! in_array($castingParametersArray[0], $relationsToSave))
					$relationsToSave[] = $castingParametersArray[0];
			}

			foreach($relationsToSave as $relationToSave)
			{
				if(($model->relationLoaded($relationToSave))&&($model->$relationToSave))
					$model->$relationToSave->save();

				elseif((! $model->relationLoaded($relationToSave))||(! $model->$relationToSave))
				{
					if($relationToSave == 'extraFields')
						$model->getProjectExtraFieldsModel();

					else
						$model->provideExtraFieldCustomModel($relationToSave);
				}
				else
				{
					die($relationToSave);
				}
			}
		});

	}

	public function provideExtraFieldCustomModel(string $modelName) : Model
	{
		$providerMethod = ExtraFieldsProvider::getExtraFieldsProviderMethod($modelName);

		return $this->$providerMethod();
	}
}