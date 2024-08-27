<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;
use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Models\Casts\ExtraFieldJson;
use IlBronza\CRUD\Providers\ExtraFields\ExtraFieldsProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Str;

use function array_filter;
use function class_basename;
use function dd;
use function get_class;
use function stripos;
use function strpos;

trait CRUDModelExtraFieldsTrait
{
	public function getExtraFieldsCasts() : array
	{
		return array_filter($this->getCasts(), function ($item)
		{
			if (strpos($item, 'ExtraField') !== false)
				return true;

			if (strpos($item, 'CastFieldPrice') !== false)
				return true;

			return false;
		});
	}

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
		if($this instanceof Pivot)
		{
			$foreign = Str::snake(
					class_basename($this)
				) . '_id';

			return $this->hasOne(
				$this->getExtraFieldsClass(),
				$foreign
			);
		}

		return $this->hasOne(
			$this->getExtraFieldsClass()
		);
	}

	public function getProjectExtraFieldsModel()
	{
		try
		{
			if($extraFields = $this->extraFields)
				return $extraFields;
		}
		catch(\Exception $e)
		{
			dd($this->extraFields);
		}

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

			foreach($model->getExtraFieldsCasts() as $attribute => $casting)
				unset($model->$attribute);
		});

		static::saved(function ($model) {

			if(! $model->relationLoaded('extraFields'))
			{
				$model->getProjectExtraFieldsModel();
				$model->extraFields;				
			}

			return ;

			$relationsToSave = ['extraFields'];

			foreach($model->getExtraFieldsCasts() as $attribute => $casting)
			{
				if (strpos($casting, 'CastFieldPrice') !== false)
				{
					if(! $model->relationLoaded($attribute))
						continue;
				}

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