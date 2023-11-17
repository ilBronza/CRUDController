<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Models\Casts\ExtraField;
use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRestoreTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class BaseDestroyableModel extends Model 
{
	use CRUDRestoreTrait;

	use CRUDCacheTrait;
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $dates = [
		'deleted_at'
	];

    public function updateWithoutEvent(array $data = [])
    {
        if(count($data))
            static::where('id', $this->id)
                ->update($data);
    }

	public function scopeWhereBooleanNotFalse($query, string $fieldName)
	{
		return $query->whereNull($fieldName)
				->orWhere($fieldName, true);
	}

	public function scopeWhereBooleanNotTrue($query, string $fieldName)
	{
		return $query->whereNull($fieldName)
				->orWhere($fieldName, false);
	}

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults();
	}

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}

	public function _customSetter(string $fieldName, mixed $value, bool $save = false) : mixed
	{
		if($this->casts[$fieldName] ?? null)
		{
			$caster = class_basename($this->getCastType($fieldName));

			if(strpos($caster, 'extrafield') === 0)
				{
					$type = explode(":", $caster)[1] ?? null;

					ExtraField::staticSet($type, $this, $fieldName, $value);
				}
			else
				$this->$fieldName = $value;
		}
		else
			$this->$fieldName = $value;

		if($save)
			$this->save();

		return $this->$fieldName;
	}
}