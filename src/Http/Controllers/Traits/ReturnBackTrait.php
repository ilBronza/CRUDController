<?php

namespace IlBronza\CRUD\Http\Controllers\Traits;

use IlBronza\CRUD\Helpers\RouteHelpers\RouteHelper;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Str;

use function session;
use function url;

trait ReturnBackTrait
{
	public function getReturnBackKey() : string
	{
		return RouteHelper::getReturnBackKey($this);
	}

	public function setReturnUrlToPrevious()
	{
		return $this->setReturnUrl(
			url()->previous()
		);
	}

	public function checkReturnUrl() : bool
	{
		$classKey = $this->getReturnBackKey();

		$url = session($classKey, null);

		return !! $url;
	}

	public function getReturnUrl() : ?string
	{
		$classKey = $this->getReturnBackKey();

		return session($classKey, null);
//		$url = session($classKey, null);
//		session()->forget($classKey);

//		return $url;
	}

	public function setReturnUrl(string $url) : string
	{
		$classKey = $this->getReturnBackKey();

		$urlKey = RouteHelper::getUrlReturnBackKey($url);

		session([$classKey => $url]);
		session([$urlKey => $classKey]);

		return $classKey;
	}

	public function manageReturnBack()
	{
		if (! $this->mustReturnBack())
			return;

		return $this->setReturnUrlIfEmpty(
			url()->previous()
		);
	}

	public function setReturnUrlIfEmpty(string $url)
	{
		if (! $this->checkReturnUrl())
			$this->setReturnUrl(
				$url
			);
	}

	public function mustReturnBack() : bool
	{
		return ! ! $this->returnBack;
	}

}