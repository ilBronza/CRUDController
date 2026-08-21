<?php

namespace IlBronza\CRUD\Tests\Fixtures\Providers;

use IlBronza\Datatables\Providers\FieldsGroupParametersFile;

class GadgetFieldsGroupParametersFile extends FieldsGroupParametersFile
{
	static function getFieldsGroup() : array
	{
		return [
			'fields' => [
				'name' => 'flat',
				'description' => 'flat'
			]
		];
	}
}
