<?php

namespace IlBronza\CRUD\Tests\Fixtures\Controllers;

use IlBronza\CRUD\Tests\Fixtures\Providers\GadgetFieldsGroupParametersFile;
use IlBronza\CRUD\Traits\CRUDIndexTrait;
use IlBronza\CRUD\Traits\CRUDPlainIndexTrait;

class GadgetIndexController extends GadgetCRUD
{
	use CRUDPlainIndexTrait;
	use CRUDIndexTrait;

	public $allowedMethods = ['index'];

	public function getIndexFieldsArray()
	{
		return GadgetFieldsGroupParametersFile::getTracedFieldsGroup();
	}

	public function getRelatedFieldsArray()
	{
		return GadgetFieldsGroupParametersFile::getTracedFieldsGroup();
	}

	public function getIndexElements()
	{
		return $this->getModelClass()::get();
	}
}
