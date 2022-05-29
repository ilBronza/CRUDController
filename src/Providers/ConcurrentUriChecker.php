<?php

namespace IlBronza\CRUD\Providers;

use Auth;
use IlBronza\CRUD\Helpers\ConcurrentUriData;
use IlBronza\CRUD\Traits\ConcurrentUri\ConcurrentUriGettersAndSetters;
use Illuminate\Support\Str;

class ConcurrentUriChecker
{
	public $created = false;

	use ConcurrentUriGettersAndSetters;

	public function isMultipleOpening()
	{
		return ! $this->created;
	}

	public function createPrevalentUserData() : ConcurrentUriData
	{
		return new ConcurrentUriData();
	}

	public function createPrevalentUserDataByPageKey(string $pageKey) : ConcurrentUriData
	{
		$uriData = $this->createPrevalentUserData();

		$uriData->setPageKey($pageKey);

		return $uriData;
	}

	private function storePrevalentUserData(string $path, ConcurrentUriData $concurrentUriData) : ConcurrentUriData
	{
		cache()->forget($this->getUriCacheKey($path));
		cache()->put(
			$this->getUriCacheKey($path),
			$concurrentUriData,
			$this->getCacheLifetime()
		);

		return $concurrentUriData;
	}

	public function storeGainedPrevalentUserData(string $path, string $pageKey)
	{
		$prevalentUserData = $this->createPrevalentUserDataByPageKey($pageKey);

		return $this->storePrevalentUserData($path, $prevalentUserData);
	}

	public function calculateAndStorePrevalentUserData(string $path)
	{
		$prevalentUserData = $this->createPrevalentUserData();

		$this->created = true;

		return $this->storePrevalentUserData($path, $prevalentUserData);
	}

	public function getOrCreatePrevalentUserDataByPath(string $path)
	{
		if($prevalentUserData = $this->getPrevalentUserDataByPath($path))
			return $prevalentUserData;

		return $this->calculateAndStorePrevalentUserData($path);
	}

	public function getPrevalentUserDataByPath(string $path) : ? ConcurrentUriData
	{
		return cache()->get(
			$this->getUriCacheKey($path)
		);
	}

	public function getPrevalentUserData()
	{
		return $this->getOrCreatePrevalentUserDataByPath(
			$this->getBasePathKey()
		);
	}

	public function getPrevalentUserId()
	{
		return $this->getPrevalentUserData()->getId();
	}

	public function getPrevalentPageKey()
	{
		return $this->getPrevalentUserData()->getPageKey();
	}

	public function userIsAllowed()
	{
		if(Auth::guest())
			return false;

		$prevalendUserId = $this->getPrevalentUserId();

		return Auth::id() == $prevalendUserId;
	}

	private function responseGainedPersistence(string $path, string $pageKey)
	{
		$this->storeGainedPrevalentUserData($path, $pageKey);

		return [
			'userData' => $this->getPrevalentUserData(),
			'status' => 'gained',
			'message' => trans('crud::crud.theOtherUserClosedThisUriBetterToRefreshPageToSeeUpdates')
		];
	}

	private function responseValidPersistence(string $path, ConcurrentUriData $concurrentUriData, string $pageKey)
	{
		$this->storePrevalentUserData($path, $concurrentUriData);

		return [
			'userData' => $this->getPrevalentUserDataByPath($path),
			'sentPageKey' => $pageKey,
			'status' => 'kept',
			'message' => null
		];
	}

	private function responseDifferentPage()
	{
		return [
			'userData' => $this->getPrevalentUserData(),
			'status' => 'morePages',
			'message' => trans('crud::crud.youAlreadyOpenedThisUrlOnADifferentPage')
		];
	}

	public function responseNotAllowed()
	{
		return [
			'userData' => $this->getPrevalentUserData(),
			'status' => 'notAllowed',
			'message' => trans('crud::crud.aDifferentUserIsWatchingThisPage')
		];
	}

	public function check(string $path)
	{
		return $this->getPrevalentUserDataByPath($path);
	}

	public function managePrevalentUserData(string $path, string $pageKey)
	{
		if(! $currentPrevalent = $this->getPrevalentUserDataByPath($path))
			return $this->responseGainedPersistence($path, $pageKey);

		if(Auth::id() != $currentPrevalent->getId())
			return $this->responseNotAllowed();

		if($pageKey == $currentPrevalent->getPageKey())
			return $this->responseValidPersistence($path, $currentPrevalent, $pageKey);

		return $this->responseDifferentPage();
	}

	private function removePrevalenUserData(string $path, ConcurrentUriData $currentUriData) : bool
	{
		cache()->forget(
			$this->getUriCacheKey($path)
		);

		return true;
	}

	public function managePageLeaveByData(string $path, string $pageKey) : bool
	{
		if(! $currentPrevalent = $this->getPrevalentUserDataByPath($path))
			return true;

		if(Auth::id() != $currentPrevalent->getId())
			return true;

		if($pageKey == $currentPrevalent->getPageKey())
			return $this->removePrevalenUserData($path, $currentPrevalent);

		return true;
	}
}