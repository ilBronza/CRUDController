<?php

namespace IlBronza\CRUD\Providers\RouterProvider;

interface RoutedObjectInterface
{
	public function getRoutePrefix() : ? string;
}