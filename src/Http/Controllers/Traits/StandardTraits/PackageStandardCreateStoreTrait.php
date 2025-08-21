<?php

namespace IlBronza\CRUD\Http\Controllers\Traits\StandardTraits;

use IlBronza\CRUD\Traits\CRUDCreateStoreTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;

use function config;

trait PackageStandardCreateStoreTrait
{
	use CRUDCreateStoreTrait;
	use CRUDRelationshipTrait;

	public $allowedMethods = [
		'create',
		'store',
	];

	public function getCreateParametersFile() : ? string
	{
		return config($this->getBaseConfigName() . ".models.$this->configModelClassName.parametersFiles.create");
	}
}