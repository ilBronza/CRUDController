<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Media;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\ModelHelpers\ModelFinderHelper;
use IlBronza\CRUD\Models\Media;

class MediaCollectionController extends CRUD
{
	public $allowedMethods = ['show', 'destroy'];
	public $modelClass = Media::class;

	public function show(string $model, string $key, string $collection)
	{
		$element = ModelFinderHelper::getByClassKey($model, $key);

		if(! $element)
			abort(404);

		if(! $element->userCanSee())
			abort(403, 'User can not see this element');

		return view('crud::utilities.media.collection', [
			'element' => $element,
			'collection' => $collection,
			'medias' => $element->getMedia($collection)
		]);
	}

	public function destroy(string $model, string $key, string $media)
	{
		$element = ModelFinderHelper::getByClassKey($model, $key);

		if(! $element)
			abort(404);

		if(! $element->userCanDelete())
			abort(403, 'User can not delete this element');

		$media = Media::findOrFail($media);

		if(! $element->is($media->model))
			abort(403, 'Media does not belong to this element');

		$media->delete();

		return [
			'success' => true
		];
	}
}
