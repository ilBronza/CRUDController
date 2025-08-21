<?php

namespace IlBronza\CRUD\Helpers\RouteHelpers;

use IlBronza\CRUD\CRUD;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;

use function get_class;
use function http_build_query;
use function ksort;
use function request;

use const PHP_QUERY_RFC3986;

class RouteHelper
{
	static function getReturnBackKey(CRUD $controller) : string
	{
		$pieces = [
			'returnBack.key',
			Auth::id(),
			get_class($controller)
		];

//		if($parent = $controller->getParentModel())
//		{
//			$pieces[] = $parent->getMorphClass();
//			$pieces[] = $parent->getKey();
//		}
//
//		if(($model = $controller->getModel())&&($model->exists))
//		{
//			$pieces[] = $parent->getMorphClass();
//			$pieces[] = $parent->getKey();
//		}

		return implode('.', $pieces);
	}

	static function getUrlReturnBackKey(string $url) : string
	{
		return 'returnBack.url.' . Auth::id() . '.' . Str::slug($url);
	}
}