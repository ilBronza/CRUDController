<?php

namespace IlBronza\CRUD\Helpers\PackagesHelpers;

use function app;
use function array_map;
use function array_pop;
use function array_shift;
use function explode;
use function get_class;
use function implode;
use function strpos;
use function ucfirst;

class PackageClassesResolverHelper
{
	static function getPackageClassNameByType(string $fieldType) : string
	{
		$pieces = explode("::", $fieldType);

		$package = array_shift($pieces);

		$fieldType = static::getClassNameByType(implode(".", $pieces), false);

		$fullnamespace = get_class(app($package));

		$namespacePieces = explode("\\", $fullnamespace);
		array_pop($namespacePieces);

		return implode("\\", $namespacePieces) . "\\" . $fieldType;
	}

	static function getClassNameByType(string $fieldType, bool $fullQualifiedNamespace = true) : string
	{
		//if contains "::" in string, it's a custom field
		if(strpos($fieldType, "::") !== false)
			return static::getPackageClassNameByType($fieldType);

		$pieces = explode(".", $fieldType);

		$fieldType = ucfirst(array_pop($pieces));

		$folders = implode("", array_map(function($item)
		{
			return ucfirst($item) . '\\';
		}, $pieces));

		if(! $fullQualifiedNamespace)
			return $folders . $fieldType;

		return __NAMESPACE__ . '\\' . $folders  . $fieldType;
	}
}