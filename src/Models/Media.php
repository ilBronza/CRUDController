<?php

namespace IlBronza\CRUD\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia 
{
	use SoftDeletes;

	// public function getDeleteUrl()
	// {
	// 	return $this->model->getDeleteMediaUrl($this);
	// }

	public function getTemporaryOrPermanentUrl()
	{
		try
		{
			return $this->getTemporaryUrl(Carbon::now()->addMinutes(5));
		}
		catch(\Exception $e)
		{
			return $this->getUrl();
		}
	}

	public function isPublic()
	{
		return $this->getDiskDriverName() == 'public';
	}

	public function isImage() : bool
	{
		return $this->getTypeFromMime() == 'image';
	}

	public function getServeImageUrl()
	{
		if($this->isPublic())
			return $this->getUrl();

		return route('media.show', [$this]);
	}
}