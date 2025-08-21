<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

use IlBronza\UikitTemplate\Fetcher;

trait CRUDLogoTrait
{
	public function getMissingLogoUrl()
	{
		return config('crud.missingImageUrl');
	}

	public function getLogoImageUrl() : ?string
	{
		if (! $media = $this->getMedia('logo'))
			return null;

		return $media->first()?->getUrl();
	}

	public function getLogoImageFetcher() : Fetcher
	{
		return new Fetcher([
			'url' => $this->getLogoFetcherUrl(),
			'title' => __('crud::fetchers.avatarFetcherTitle')
		]);
	}

	public function getLogoFetcherUrl() : string
	{
		return $this->getKeyedRoute('logoFetcher');
	}

	public function getUploadLogoFormUrl() : string
	{
		return $this->getKeyedRoute('logoUploadForm');
	}
}