<?php

namespace IlBronza\CRUD\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia 
{
	use SoftDeletes;

	public function getDeleteUrl()
	{
		return $this->model->getDeleteMediaUrl($this);
	}
}