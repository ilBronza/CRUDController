<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

class BasePivotModel extends Pivot 
{
	use SoftDeletes;
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $dates = [
		'deleted_at'
	];

}