<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Models\Media;

trait CRUDDeleteMediaTrait
{
	public function _deleteMedia($modelKey, Media $media)
	{
        if(! $element = $this->findModel($modelKey))
        	abort(404);

        if(! $element->is($media->model))
            return [
                'success' => false
            ];

        $media->delete();

        return [
            'success' => true
        ];
	}
}