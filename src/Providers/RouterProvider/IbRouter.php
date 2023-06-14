<?php

namespace IlBronza\CRUD\Providers\RouterProvider;

use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;

class IbRouter
{
	static function routeName(RoutedObjectInterface $object, string $name)
	{
		if(! $prefix = $object->getRoutePrefix())
			return $name;

		return $prefix . $name;
	}

	static function route(RoutedObjectInterface $object, string $name, array $parameters = [])
	{
		$name = static::routeName($object, $name);

		return route(
			$name,
			$parameters
		);
	}

}