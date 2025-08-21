<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class BasePivotModel extends Pivot 
{
	use LogsActivity;
	use SoftDeletes;
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $casts = [
		'deleted_at' => 'datetime'
	];

}