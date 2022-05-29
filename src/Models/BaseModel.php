<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Traits\Model\CRUDCacheTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class BaseModel extends Model 
{
	use LogsActivity;

	use CRUDCacheTrait;
	use SoftDeletes;
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $dates = [
		'deleted_at'
	];

	public function getActivitylogOptions(): LogOptions
	{
		return LogOptions::defaults();
	}

}