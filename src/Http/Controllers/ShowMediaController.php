<?php

namespace IlBronza\CRUD\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\CRUD\Models\Media;

class ShowMediaController extends Controller
{
	public function show($media)
	{
		$media = Media::with('model')->find($media);

		if(! $media->model->userCanSee())
			abort(403, 'User can not see this element');

		return response()->file($media->getPath());
	}
}

