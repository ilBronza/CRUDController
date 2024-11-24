<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

trait CRUDModelTrait
{
	static $teaserFields = [];
	public ?string $translationFolderPrefix = null;

	use CRUDModelRoutingTrait;
	use CRUDModelButtonsTrait;
	use CRUDModelUserRightsTrait;

	// use LogsActivity;
	use CRUDDeleterTrait;

	// public function hasOwnership()
	// {
	//     if(isset($this->hasOwnership))
	//         return $this->hasOwnership;

	//     return true;
	// }

	public function getActivitylogOptions() : LogOptions
	{
		return LogOptions::defaults()->logAll()->logOnlyDirty();
	}

	public function getTranslatedClassname()
	{
		return trans('crudModels.' . $this->getCamelcaseClassBasename());
	}

	public function printJsonFieldHtml($array)
	{
		return view('formfield::show.uikit._json', ['arrayElement' => $array])->render();
	}

	public function getBrowserTitle()
	{
		return $this->getName();
	}

	public function getName() : ?string
	{
		$nameField = $this->getNameFieldName();

		return $this->{$nameField};
	}

	public static function getNameFieldName()
	{
		return static::$nameField ?? 'name';
	}

	public function getNestableName() : ?string
	{
		return $this->getName();
	}

	public function getNestableIndex() : int
	{
		return $this->sorting_index ?? 0;
	}

	public function getNestableKey() : string
	{
		return config('crud.nestableLeadingId') . $this->getKey();
	}

	static function getByName(string $name)
	{
		return static::where('name', $name)->first();
	}

	static function getSelfPossibleValuesArray(string $keyField = null, string $nameField = null) : array
	{
		$placeholder = static::make();

		if (! $keyField)
			$keyField = $placeholder->getKeyName();

		if (! $nameField)
			$nameField = $placeholder->getNameFieldName();

		return self::select(
			$nameField, $keyField
		)->pluck(
			$nameField, $keyField
		)->toArray();
	}

	public function getPluralTranslatedClassname()
	{
		$plural = $this->getPluralCamelcaseClassBasename();

		return trans($this->getTranslationsFileName() . '.' . $plural);
	}

	public static function getPluralCamelcaseClassBasename()
	{
		return Str::plural(static::getCamelcaseClassBasename());
	}

	public static function getCamelcaseClassBasename()
	{
		return lcfirst(class_basename(static::class));
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

	public function getTranslationsFolderPrefix() : ?string
	{
		return $this->translationFolderPrefix;
	}

	public static function getTranslation(string $string, array $parameters = [])
	{
		$fileString = static::pluralLowerClass() . '.' . $string;

		return trans($fileString, $parameters);
	}

	public static function pluralLowerClass()
	{
		return Str::plural(strtolower(class_basename(static::class)));
	}
	//Rimuovere anche _teaser del pacchetto CRUD, come anche lo show
	//DEPRECATO, non voglio niente che non abbia array
	// public function getTeaserFields()
	// {
	//     return $this->teaserFields;
	// }
}