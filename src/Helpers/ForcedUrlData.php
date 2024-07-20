<?php

namespace IlBronza\CRUD\Helpers;

use Carbon\Carbon;

class ForcedUrlData
{
	public Carbon $created_at;
	public string $url;
	public ? string $message = null;

	static function createByParameters(array $parameters) : static
	{
		$forcedUrl = new static();

		foreach($parameters as $key => $value)
			$forcedUrl->$key = $value;

		if(! isset($forcedUrl->created_at))
			$forcedUrl->created_at = Carbon::now();

		return $forcedUrl;
	}

	public function getUrl() : string
	{
		return $this->url;
	}

	public function getMessage() : ? string
	{
		return $this->message;
	}
}