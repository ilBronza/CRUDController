<?php

namespace IlBronza\CRUD\Providers\ExtraFields;

class ExtraFieldsProvider
{
	static function getExtraFieldsProviderMethod(string $modelName) : string
	{
		return 'provide' . ucfirst($modelName) . 'ModelForExtraFields';
	}
}