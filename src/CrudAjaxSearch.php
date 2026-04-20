<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\Fields\CrudAjaxSearchFormField;

class CrudAjaxSearch
{
	public static function ajaxSearch(array $parameters = []) : CrudAjaxSearchFormField
	{
		$defaults = [
			'name' => 'ajax_search',
		];

		if (! array_key_exists('searchUrl', $parameters) && ! array_key_exists('searchRouteName', $parameters))
			$defaults['searchUrl'] = route('crud.ajaxSearch');

		return new CrudAjaxSearchFormField(array_merge($defaults, $parameters));
	}
}
