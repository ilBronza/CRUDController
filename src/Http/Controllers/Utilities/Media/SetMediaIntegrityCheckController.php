<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Media;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Models\Media;
use Illuminate\Support\Carbon;

class SetMediaIntegrityCheckController extends CRUD
{
	public $allowedMethods = ['setIntegrityCheck'];
	public $modelClass = Media::class;

	public function setIntegrityCheck(string $media)
	{
		$media = Media::findOrFail($media);

		$media->setIntegrityCheckedAt(
			Carbon::now()
		);

		return [
			'success' => true,
			'integrity_checked_at' => $media->getIntegrityCheckedAtString()
		];
	}
}
