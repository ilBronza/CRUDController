<?php

namespace IlBronza\CRUD\Traits\Media;

use IlBronza\FormField\FormField;
use Spatie\MediaLibrary\InteractsWithMedia as SpatieInteractsWithMedia;

trait InteractsWithMedia
{
	public $mediaDisks = [];

	use SpatieInteractsWithMedia;

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
}
