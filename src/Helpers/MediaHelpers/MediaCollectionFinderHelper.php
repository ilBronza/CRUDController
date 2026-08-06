<?php

namespace IlBronza\CRUD\Helpers\MediaHelpers;

class MediaCollectionFinderHelper
{
	static function getCollectionUrl(string $morphClass, string $key, string $collection) : string
	{
		return route('media.collection', [
				'model' => $morphClass,
				'key' => $key,
				'collection' => $collection
			]);
	}

	static function getCollectionUrlByModel($model, string $collection) : string
	{
		return static::getCollectionUrl(
			$model->getMorphClass(),
			$model->getKey(),
			$collection
		);
	}

	static function getMediaDeleteUrl($model, $media) : string
	{
		return route('media.collection.destroy', [
				'model' => $model->getMorphClass(),
				'key' => $model->getKey(),
				'media' => $media->getKey()
			]);
	}
}
