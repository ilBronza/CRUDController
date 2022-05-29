<?php

namespace IlBronza\CRUD\Helpers;

use Auth;
use Illuminate\Support\Str;

class ConcurrentUriData
{
	public function __construct()
	{
		$this->id = Auth::id();
		$this->pageKey = Str::random(8);
	}

	public function getId()
	{
		return $this->id;
	}

	public function getPageKey()
	{
		return $this->pageKey;
	}

	public function setPageKey(string $pageKey)
	{
		$this->pageKey = $pageKey;
	}
}