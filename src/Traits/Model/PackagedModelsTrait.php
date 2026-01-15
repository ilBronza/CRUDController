<?php

namespace IlBronza\CRUD\Traits\Model;

use IlBronza\CRUD\Traits\PackagedClassesTrait;

use function config;

trait PackagedModelsTrait
{
	use PackagedClassesTrait;

	public function getRouteBaseNamePrefix() : ? string
	{
		return config(static::getPackageConfigPrefix() . '.routePrefix');
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