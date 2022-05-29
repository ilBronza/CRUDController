<?php

namespace IlBronza\CRUD\Traits\ConcurrentUri;

use Illuminate\Support\Str;

trait ConcurrentUriGettersAndSetters
{
	public function getJavascriptTickInterval()
	{
		return config('crud.concurrentUriJavascriptTickInterval');
	}

	public function getCacheLifetime()
	{
		return config('crud.concurrentUriCacheLifetime');
	}

	public function getBasePathKey() : string
	{
		return request()->path();
	}

	public function getUriCacheKey(string $path = null)
	{
		if(! $path)
			$path = $this->getBasePathKey();

		return Str::slug(
			config('crud.concurrentUriPrefix') . "-" . $path
		);
	}

	
}
