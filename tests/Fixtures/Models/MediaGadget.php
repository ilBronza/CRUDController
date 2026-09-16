<?php

namespace IlBronza\CRUD\Tests\Fixtures\Models;

use IlBronza\CRUD\Traits\Media\InteractsWithMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class MediaGadget extends Model implements HasMedia
{
	use InteractsWithMedia;

	protected $table = 'gadgets';

	protected $fillable = ['name'];

	public function registerMediaCollections(): void
	{
	}
}
