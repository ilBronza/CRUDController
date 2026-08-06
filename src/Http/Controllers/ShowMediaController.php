<?php

namespace IlBronza\CRUD\Http\Controllers;

use App\Http\Controllers\Controller;
use IlBronza\CRUD\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ShowMediaController extends Controller
{
	public function show($media, Request $request)
	{
		$media = Media::with('model')->find($media);

		if(! $media->model->userCanSee())
			abort(403, 'User can not see this element');

		if($conversion = $request->input('conversion'))
			return response()->file($media->getPath($conversion));

		$fileName = $media->file_name ?: basename($media->getPath());
		$fallbackFileName = str_replace(['%', '/', '\\'], '-', Str::ascii($fileName));

		return response()->file($media->getPath())
			->setContentDisposition(
				ResponseHeaderBag::DISPOSITION_INLINE,
				$fileName,
				$fallbackFileName ?: "media-{$media->getKey()}"
			);
	}
}
