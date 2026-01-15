<?php

namespace IlBronza\CRUD\Helpers\RouteHelpers;

use Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RouteHelper
{
	static function getReturnBackKey(Model $model) : string
	{
		$pieces = [
			'returnBack.key',
			Auth::id(),
			get_class($model),
			$model->getKey()
		];

		return Str::slug(implode('.', $pieces));
	}

	static function getUrlReturnBackKey(string $url) : string
	{
		return 'returnBack.url.' . Auth::id() . '.' . Str::slug($url);
	}
}