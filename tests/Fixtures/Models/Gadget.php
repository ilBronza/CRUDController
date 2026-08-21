<?php

namespace IlBronza\CRUD\Tests\Fixtures\Models;

use IlBronza\CRUD\Tests\Fixtures\Providers\GadgetShowFieldsetsParameters;
use IlBronza\CRUD\Traits\Model\CRUDModelTrait;
use IlBronza\CRUD\Traits\Model\CRUDModelTranslationsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// Modello finto: esiste solo per far girare le 7 route standard di CRUD
class Gadget extends Model
{
	use CRUDModelTrait;
	use CRUDModelTranslationsTrait;
	use SoftDeletes;

	protected $table = 'gadgets';

	protected $fillable = ['name', 'description'];

	static $deletingRelationships = [];

	public function getName() : ? string
	{
		return $this->name;
	}

	public function getShowParametersFile() : string
	{
		return GadgetShowFieldsetsParameters::class;
	}

	public function getRelationshipsManagerClass() : ? string
	{
		return null;
	}
}
