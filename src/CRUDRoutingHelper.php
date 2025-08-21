<?php

namespace IlBronza\CRUD;

use IlBronza\CRUD\Helpers\ForcedUrlData;

class CRUDRoutingHelper
{
	static function hasForcedUrl() : bool
	{
		return count(static::getForcedUrl()) > 0;
	}

	static function getForcedUrl() : array
	{
		return session()->get('crud.forcedUrl', []);
	}

	static function popForcedUrl() : ? ForcedUrlData
	{
		$forcedUrls = static::getForcedUrl();

		$result = array_pop($forcedUrls);

		// static::setForcedUrls($forcedUrls);

		return $result;
	}

    static function addForcedUrl(ForcedUrlData|string $url)
    {
    	if(! session()->get('crud.forcedUrl'))
    		session()->put('crud.forcedUrl', []);

    	if(is_string($url))
            $url = ForcedUrlData::createByParameters([
            	'url' => $url
            ]);

    	session()->push('crud.forcedUrl', $url);
    }
}