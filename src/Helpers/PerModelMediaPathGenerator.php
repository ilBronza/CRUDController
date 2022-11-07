<?php

namespace IlBronza\CRUD\Helpers;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\DefaultPathGenerator;

class PerModelMediaPathGenerator extends DefaultPathGenerator
{
    /*
     * Get a unique base path for the given media.
     */
    protected function getBasePath(Media $media): string
    {
    	$pieces = [
    		strtolower($media->model_type::getMediaFolder()),
    		Str::slug($media->collection_name, '_')
    	];

        return implode("/", 
        	array_merge(
        		$pieces,
        		str_split($media->getKey())
        	));
    }

 	/*
     * Get the path for the given media, relative to the root storage path.
     */
    // public function getPath(Media $media): string
    // {
    //     return $this->getBasePath($media).'/';
    // }


    /*
     * Get the path for conversions of the given media, relative to the root storage path.
     */
    // public function getPathForConversions(Media $media): string
    // {
    //     return $this->getBasePath($media).'/conversions/';
    // }


    /*
     * Get the path for responsive images of the given media, relative to the root storage path.
     */
    // public function getPathForResponsiveImages(Media $media): string
    // {
    //     return $this->getBasePath($media).'/responsive-images/';
    // }

}
