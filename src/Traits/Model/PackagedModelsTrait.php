<?php

namespace IlBronza\CRUD\Traits\Model;

trait PackagedModelsTrait
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

	static function getProjectClassName() : string
	{
		try
		{
			return config(
				static::getConfigParameterKey('class')
			);			
		}
		catch(\Throwable $e)
		{
			dd("Manca la dichiarazione in config. " . $e->getMessage() . ' -> ' . static::getConfigParameterKey('class'));
		}
	}

	public function getRouteBaseNamePrefix() : ? string
	{
		return config(static::getPackageConfigPrefix() . '.routePrefix');
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

	public function getTable() : string
	{
		return config(
			static::getConfigParameterKey('table')
		);
	}

	public function getTranslationFilePrefix(string $file = null)
	{
		if(! $file)
			$file = static::getPackageConfigPrefix();

		return static::getPackageConfigPrefix() . "::{$file}.";
	}

    public function getPluralTranslatedClassname()
    {
		return trans($this->getTranslationFilePrefix() . $this->getPluralCamelcaseClassBasename());
    }

    public function getTranslatedClassname()
    {
    	return trans($this->getTranslationFilePrefix() . $this->getCamelcaseClassBasename());
    }
}