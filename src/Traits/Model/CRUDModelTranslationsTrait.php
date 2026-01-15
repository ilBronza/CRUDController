<?php

namespace IlBronza\CRUD\Traits\Model;

use function implode;
use function trans;

trait CRUDModelTranslationsTrait
{
	public ?string $translationFolderPrefix = null;

	public static function getTranslation(string $string, array $parameters = [])
	{
		$fileString = static::pluralLowerClass() . '.' . $string;

		return trans($fileString, $parameters);
	}

	public function getPluralTranslatedClassname()
	{
		$plural = $this->getPluralCamelcaseClassBasename();

		return trans($this->getTranslationsFileName() . '.' . $plural);
	}

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}

	public function getTranslationsFolderPrefix() : ?string
	{
		return $this->translationFolderPrefix;
	}

	public function getTranslationsFileName()
	{
		if ($this->translationsFilename ?? false)
			return $this->translationsFilename;

		$plural = $this->getPluralCamelcaseClassBasename();

		if ($prefix = ($this->getTranslationsFolderPrefix()))
			return $prefix . '::' . $plural;

		return $plural;
	}

	public function getShowTitle()
	{
		$parts = [];

		if(strpos($translationsFilename = $this->getTranslationsFileName(), '::') === false)
			if(isset(static::$packageConfigPrefix))
				$parts[] = static::$packageConfigPrefix . '::';


		$parts[] = $translationsFilename . '.';
		$parts[] = 'titles.show';

		try
		{
			return trans(implode('', $parts), ['element' => $this->getName()]);
		}
		catch(\Throwable $e)
		{
			dd([$e->getMEssage(), implode('', $parts), $this->getName()]);
			return null;
		}
	}

	public function cardIntroShow() : ? string
	{
		return null;
	}
}