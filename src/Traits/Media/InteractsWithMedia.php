<?php

namespace IlBronza\CRUD\Traits\Media;

use IlBronza\CRUD\Models\Media;
use IlBronza\FormField\FormField;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia as SpatieInteractsWithMedia;

trait InteractsWithMedia
{
	public $mediaDisks = [];

	use SpatieInteractsWithMedia;

	public function registerMediaConversions(Media|\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
	{
		$this
			->addMediaConversion('thumb')
			->fit(Fit::Crop, 300, 300)
			->nonQueued();

		$this
			->addMediaConversion('thumb-mini')
			->fit(Fit::Crop, 100, 100)
			->nonQueued();

		$this
			->addMediaConversion('table')
			->fit(Fit::Crop, 25, 25)
			->nonQueued();
	}

	static function getMediaFolder()
	{
		if(static::$mediaFolder ?? false)
			return static::$mediaFolder;

		return class_basename(static::class);
	}

	public function getMediaDisksByFieldName(string $fieldName)
	{
		if(! (static::$mediaDisks ?? null))
			return null;

		if(! static::$mediaDisks[$fieldName])
			return false;

		return static::$mediaDisks[$fieldName];
	}

	public function getDiskByField(FormField $formField)
	{
		$fieldName = $formField->getName();

		if($mediaDisk = $this->getMediaDisksByFieldName($fieldName))
			return $mediaDisk;

		return config('media-library.disk_name');		
	}

	public function getLastMedia(string $collectionName = null) : ? Media
	{
		$query = $this->media();

		if($collectionName)
			$query->where('collection_name', $collectionName);

		return $query->orderBy('created_at', 'DESC')->first();
	}

	public function getMainMedia(string $collectionName = null) : ? Media
	{
		$query = $this->media();

		if($collectionName)
			$query->where('collection_name', $collectionName);

		return $query->orderBy('created_at')->first();
	}
}
