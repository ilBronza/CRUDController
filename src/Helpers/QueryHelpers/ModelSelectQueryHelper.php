<?php

namespace IlBronza\CRUD\Helpers\QueryHelpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ModelSelectQueryHelper
{
	/**
	 * return a cache key for the method whom called this method
	 * 
	 * @return string
	 */
	static function getMethodCacheKey() : string
	{
		$backtrace = debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT,2)[1];

		return Str::slug($backtrace['function'] . $backtrace['args'][0]);
	}

	/**
	 * give the necessary table and fields to perform the query
	 * for select input fields
	 * 
	 * @param string $modelClass
	 * @return array
	 */
	static function getQueryFieldsForSelect(string $modelClass) : array
	{
		$placeholderModel = $modelClass::make();

		$keyField = $placeholderModel->getKeyName();
		$nameField = $placeholderModel->getNameFieldName();

		return compact('placeholderModel', 'keyField', 'nameField');
	}

	/**
	 * create an array for select fields with key => name
	 * of the given model class elements
	 * 
	 * example: 
	 * ModelSelectQueryHelper::getArrayForSelect(User::class)
	 * 
	 * @param string $modelClass
	 * @return array
	 */
	static function _getArrayForSelect(string $modelClass) : array
	{
		extract(static::getQueryFieldsForSelect($modelClass));

		return DB::table($placeholderModel->getTable())
			->select($keyField, $nameField)
			->pluck($nameField, $keyField)
			->toArray();
	}

	/**
	 * return a cached (or not) array for select fields
	 * with key => name of the given model class elements
	 * 
	 * cached example: 
	 * ModelSelectQueryHelper::getArrayForSelect(User::class)
	 * 
	 * cached example with time: 
	 * ModelSelectQueryHelper::getArrayForSelect(User::class, 1200)
	 * 
	 * not cached example: 
	 * ModelSelectQueryHelper::getArrayForSelect(User::class, false)
	 * 
	 * @param string $modelClass
	 * @param false|int $cacheTime = null
	 * @return array
	 */
	static function getArrayForSelect(string $modelClass, false|int $cacheTime = null) : array
	{
		if($cacheTime === false)
			return static::_getArrayForSelect($modelClass);

		return cache()->remember(
			static::getMethodCacheKey(),
			$cacheTime ?? config('crud.cacheTime', 3600),
			function() use($modelClass)
			{
				return static::_getArrayForSelect($modelClass);
		});
	}

	/**
	 * create an array for select fields with key => name
	 * of the given model class elements filtered by given scopes
	 * 
	 * 
	 * example: 
	 * ModelSelectQueryHelper::getArrayForSelectWithScopes(
	 * 		User::class,
	 *		[
	 * 			'byRolesIds' => $rolesIds,
	 * 			'withTrashed'
	 * 		])
	 * 
	 * @param string $modelClass, false|int $cacheTime = null
	 * @return array
	 */
	static function _getArrayForSelectWithScopes(string $modelClass, array $scopes) : array
	{
		extract(static::getQueryFieldsForSelect($modelClass));

		$result = $placeholderModel
			->select($keyField, $nameField);

		foreach($scopes as $key => $value)
			if(is_string($key))
				$result->$key($value);
			else
				$result->$value();

		return $result
			->pluck($nameField, $keyField)
			->toArray();		
	}

	/**
	 * create an array for select fields with key => name
	 * of the given model class elements filtered by given scopes
	 * 
	 * 
	 * cached example: 
	 * ModelSelectQueryHelper::getArrayForSelectWithScopes(
	 * 		User::class,
	 *		[
	 * 			'byRolesIds' => $rolesIds,
	 * 			'withTrashed'
	 * 		])
	 * 
	 * cached example with time: 
	 * ModelSelectQueryHelper::getArrayForSelectWithScopes(
	 * 		User::class,
	 * 		[
	 * 			'byRolesIds' => $rolesIds,
	 * 			'withTrashed'
	 * 		],
	 * 		1200)
	 * 
	 * not cached example: 
	 * ModelSelectQueryHelper::getArrayForSelectWithScopes(
	 * 		User::class,
	 * 		[
	 * 			'byRolesIds' => $rolesIds,
	 * 			'withTrashed'
	 * 		],
	 * 		false)
	 * 
	 * @param string $modelClass, false|int $cacheTime = null
	 * @return array
	 */
	static function getArrayForSelectWithScopes(string $modelClass, array $scopes, false|int $cacheTime = null) : array
	{
		if($cacheTime === false)
			return static::_getArrayForSelectWithScopes($modelClass, $scopes);

		return cache()->remember(
			static::getMethodCacheKey(),
			$cacheTime ?? config('crud.cacheTime', 3600),
			function() use($modelClass, $scopes)
			{
				return static::_getArrayForSelectWithScopes($modelClass, $scopes);
		});
	}
}