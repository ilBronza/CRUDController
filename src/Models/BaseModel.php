<?php

namespace IlBronza\CRUD\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDRelationshipModelTrait;

class BaseModel extends Model 
{
	use SoftDeletes;
	use CRUDModelTrait;
	use CRUDRelationshipModelTrait;

	protected $dates = [
		'deleted_at'
	];

}