<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Media;

use IlBronza\CRUD\Helpers\ModelHelpers\ModelFinderHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Controller;

class DeleteMediaController extends Controller
{
	public function destroy(string $modelClass, string $modelKey, string $mediaKey) : array
	{
		$model = ModelFinderHelper::getByClassKey($modelClass, $modelKey);

		if(! $model)
			abort(404);

		if((! method_exists($model, 'userCanDelete')) || (! $model->userCanDelete()))
			abort(403, 'User can not delete this element');

		$media = $this->findMedia($mediaKey);

		if(! $model->is($media->model))
			abort(403, 'Media does not belong to this element');

		$media->delete();

		return [
			'success' => true
		];
	}

	private function findMedia(string $mediaKey) : Model
	{
		$mediaClass = config('media-library.media_model');

		if((! $mediaClass) || (! is_a($mediaClass, Model::class, true)))
			abort(404);

		return $mediaClass::query()->findOrFail($mediaKey);
	}
}
