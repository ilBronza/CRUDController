<?php

namespace IlBronza\CRUD\Models;

use IlBronza\CRUD\Models\BaseModel;
use IlBronza\CRUD\Traits\CRUDSluggableTrait;

class SluggableBaseModel extends BaseModel
{
	use CRUDSluggableTrait;

	public $incrementing = false;
	protected $keyType = 'string';
	protected $primaryKey = 'slug';
}