<?php

namespace IlBronza\CRUD\Models;

use Illuminate\Database\Eloquent\Model;

class CrudChannel extends Model
{
	protected $table = 'crud__channels';

	protected $fillable = [
		'name',
	];
}
