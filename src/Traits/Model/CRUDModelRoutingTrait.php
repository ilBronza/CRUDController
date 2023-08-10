<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait CRUDModelRoutingTrait
{
	public $routeBasenamePrefix = null;

	static function getModelRoutesPrefix() : ? string
	{
		return static::$routePrefix ?? null;
	}

	public function getRouteClassname()
	{
		if($this->routeClassname ?? false)
			return $this->routeClassname;

		return lcfirst(class_basename($this));
	}

    static function getStaticRouteBasename()
    {
        return static::make()->getRouteBasename();
    }

	public function getRouteBasename()
	{
		if($this->routeBasename ?? false)
			return $this->routeBasename;

		$className = $this->getRouteClassname();

		return implode("", [
			$this->getRouteBaseNamePrefix(),
			Str::plural($className)
		]);
	}

	public function getRouteBaseNamePrefix() : ? string
	{
		return $this->routeBasenamePrefix;
	}

	public function setRouteBaseNamePrefix(string $prefix = null)
	{
		$this->routeBasenamePrefix = $prefix;
	}

	public function getKeyedRoute(string $action, array $data = [])
	{
		$routeBasename = $this->getRouteBasename();
		$routeClassname = $this->getRouteClassname();

		return route($routeBasename . '.' . $action, [$routeClassname => $this->getKey()], $data);
	}

	public function getDeleteMediaUrlByKey($fileId, array $data = []) : string
	{
		$routeBasename = $this->getRouteBasename();
		$routeClassname = $this->getRouteClassname();

		return route($routeBasename . '.deleteMedia', [
			$routeClassname => $this->getKey(),
			'media' => $fileId
		], $data);        
	}

	public function getDeleteMediaUrlByMedia(Media $file, array $data = []) : string
	{
		$routeBasename = $this->getRouteBasename();
		$routeClassname = $this->getRouteClassname();

		return route($routeBasename . '.deleteMedia', [
			$routeClassname => $this->getKey(),
			'media' => $file->getKey()
		], $data);        
	}

	public function getDeleteMediaUrl($fileId, array $data = []) : string
	{
		throw new \Exception('DEPRECATO in favore di getDeleteMediaUrlByKey');
		//TODO DOGODO SISDO DEPRECATED
		// $routeBasename = $this->getRouteBasename();
		// $routeClassname = $this->getRouteClassname();

		// return route($routeBasename . '.deleteMedia', [
		//     $routeClassname => $this->getKey(),
		//     'media' => $fileId
		// ], $data);
	}

	public function getShowUrl(array $data = [])
	{
		return $this->getKeyedRoute('show', $data);
	}

	public function getIndexUrl(array $data = [])
	{
		return $this->getKeyedRoute('index', $data);
	}

	public function getEditUrl(array $data = [])
	{
		return $this->getKeyedRoute('edit', $data);
	}

	public function getUpdateUrl(array $data = [])
	{
		return $this->getKeyedRoute('update', $data);
	}

	public function getDestroyUrl(array $data = [])
	{
		return $this->getKeyedRoute('forceDelete', $data);
	}

	public function getDeleteUrl(array $data = [])
	{
		return $this->getKeyedRoute('destroy', $data);
	}

}