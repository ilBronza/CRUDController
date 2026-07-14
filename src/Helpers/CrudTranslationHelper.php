<?php

namespace IlBronza\CRUD\Helpers;

use Illuminate\Database\Eloquent\Model;

use function trans;

class CrudTranslationHelper
{
	public static function getByModelKeyParameters(
		Model $model,
		string $key,
		array $parameters = [],
		?string $packageConfig = null
	) : string
	{
		if(! method_exists($model, 'getTranslationsFileName'))
			return trans('crud::crud.' . $key, $parameters);

		$translationFileName = $model->getTranslationsFileName();
		$packageConfig = $model->getPackageConfigPrefix();

		foreach(static::buildTranslationKeyCandidates($translationFileName, $key, $packageConfig) as $string)
			if(trans()->has($string))
				return trans($string, $parameters);

		return trans('crud::crud.' . $key, $parameters);
	}

	private static function buildTranslationKeyCandidates(
		string $translationFileName,
		string $key,
		?string $packageConfig
	) : array
	{
		$candidates = [
			$translationFileName . '.' . $key,
		];

		if(! $packageConfig)
			return $candidates;

		if(strpos($translationFileName, '::') !== false)
			return $candidates;

		$candidates[] = $packageConfig . '::' . $translationFileName . '.' . $key;
		$candidates[] = $packageConfig . '::' . strtolower($translationFileName) . '.' . $key;

		return $candidates;
	}
}
