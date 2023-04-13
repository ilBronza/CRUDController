<?php

namespace IlBronza\CRUD\Providers\RouterProvider;

use IlBronza\CRUD\Providers\RouterProvider\RoutedObjectInterface;

class IbRouter
{
	static function route(RoutedObjectInterface $object, string $name, array $parameters = [])
	{
		if(! $prefix = $object->getRoutePrefix())
			return route($name, $parameters);

		return route($prefix . $name, $parameters);
	}

}