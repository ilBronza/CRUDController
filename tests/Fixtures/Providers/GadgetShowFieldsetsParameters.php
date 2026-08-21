<?php

namespace IlBronza\CRUD\Tests\Fixtures\Providers;

use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;

class GadgetShowFieldsetsParameters extends FieldsetParametersFile
{
	public function _getFieldsetsParameters() : array
	{
		return [
			'gadget' => [
				'fields' => [
					'name' => ['text' => 'string|required'],
					'description' => ['textarea' => 'string|nullable']
				],
				'width' => ['1-1']
			]
		];
	}
}
