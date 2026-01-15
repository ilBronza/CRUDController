<?php

namespace IlBronza\CRUD\Traits;

trait PackagedClassesTrait
{
	/**
	 * 
	 * static $modelConfigPrefix 
	 * 
	 * is the name of the array key
	 * that define the correct config property
	 * for this model
	 * 
	 * ex. 
	 * static $modelConfigPrefix = 'destinationReferent';
	 * 
	 **/
	static function getModelConfigPrefix()
	{
		return static::$modelConfigPrefix;
	}

	/**
	 * 
	 * static $packageConfigPrefix 
	 * 
	 * is the name of the array key
	 * that define the correct config property
	 * for this whole package
	 * 
	 * ex. 
	 * static $packageConfigPrefix = 'clients';
	 * 
	 **/
	static function getPackageConfigPrefix()
	{
		return static::$packageConfigPrefix;
	}

	static function gpc() : string
	{
		return static::getProjectClassName();
	}

	public static function getClassname() : string
	{
		return config(
			static::getConfigParameterKey('class')
		);
	}

	static function getProjectClassName() : string
	{
		try
		{
			return static::getClassname();
		}
		catch(\Throwable $e)
		{
			throw new \Exception("Manca la dichiarazione in config. " . $e->getMessage() . ' -> ' . static::getConfigParameterKey('class'));
		}
	}

	public static function getConfigByKey(string $key)
	{
		return config(static::getConfigParameterKey($key));
	}

	public static function getConfigParameterKey(string $key) : string
	{
		return implode(".", [
			static::getPackageConfigPrefix(),
			'models',
			static::getModelConfigPrefix(),
			$key
		]);
	}
}