<?php

namespace IlBronza\CRUD\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia 
{
	use SoftDeletes;

	protected $casts = [
		'manipulations' => 'array',
		'custom_properties' => 'array',
		'generated_conversions' => 'array',
		'responsive_images' => 'array',
		'integrity_checked_at' => 'datetime'
	];

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

	public function getServeImageUrl(? string $conversion = null)
	{
		if($this->isPublic())
			return $conversion ? $this->getUrl($conversion) : $this->getUrl();

		if(! $conversion)
			return route('media.show', [$this]);

		return route('media.show', [$this, 'conversion' => $conversion]);
	}

	public function getServeThumbUrl() : ? string
	{
		if(! $this->hasGeneratedConversion('thumb'))
			return null;

		return $this->getServeImageUrl('thumb');
	}

	public function getOriginalFileCreatedAt()
	{
		return $this->original_file_created_at;
	}

	public function getIntegrityCheckedAt()
	{
		return $this->integrity_checked_at;
	}

	public function getIntegrityCheckedAtString() : ? string
	{
		if(! $integrityCheckedAt = $this->getIntegrityCheckedAt())
			return null;

		return Carbon::parse($integrityCheckedAt)->format('d/m/Y H:i');
	}

	public function setIntegrityCheckedAt(Carbon $integrityCheckedAt) : void
	{
		$this->integrity_checked_at = $integrityCheckedAt;
		$this->save();
	}

	public function getSetIntegrityCheckUrl() : string
	{
		return route('media.setIntegrityCheck', [$this]);
	}
}
