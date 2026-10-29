<?php

namespace IlBronza\CRUD\Traits\Media;

use IlBronza\CRUD\Models\Media;
use IlBronza\FormField\FormField;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\InteractsWithMedia as SpatieInteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

trait InteractsWithMedia
{
	public $mediaDisks = [];

	use SpatieInteractsWithMedia;

	public function registerMediaConversions(Media|\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
	{
		$this
			->addMediaConversion('thumb')
			->fit(static::getCrudMediaConversionCropFit(), 300, 300)
			->nonQueued();

		$this
			->addMediaConversion('thumb-mini')
			->fit(static::getCrudMediaConversionCropFit(), 100, 100)
			->nonQueued();

		$this
			->addMediaConversion('table')
			->fit(static::getCrudMediaConversionCropFit(), 25, 25)
			->nonQueued();
	}

	private static function getCrudMediaConversionCropFit()
	{
		if(class_exists(Fit::class))
			return Fit::Crop;

		return Manipulations::FIT_CROP;
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

	public function getMainMedia(string $collectionName = null) : ? BaseMedia
	{
		if($this->relationLoaded('media'))
		{
			$media = $this->getRelation('media');

			if($collectionName)
				$media = $media->where('collection_name', $collectionName);

			return $media->sortBy('created_at')->first();
		}

		$query = $this->media();

		if($collectionName)
			$query->where('collection_name', $collectionName);

		return $query->orderBy('created_at')->first();
	}
}
