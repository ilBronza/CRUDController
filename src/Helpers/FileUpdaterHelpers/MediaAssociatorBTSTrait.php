<?php

namespace IlBronza\CRUD\Helpers\FileUpdaterHelpers;

use IlBronza\CRUD\Models\Media;
use Illuminate\Database\Eloquent\Model;

trait MediaAssociatorBTSTrait
{
	public function getDisk() : string
	{
		return $this->disk;
	}

	public function setDisk(? string $disk) : void
	{
		$this->disk = $disk ?? config('media-library.disk_name');
	}

	public function getMediaThumb() : string
	{
		return $this->getMedia()->getUrl();
	}

	public function getMedia() : Media
	{
		return $this->file;
	}

	public function setCollectionName(? string $collectionName)
	{
		$this->collectionName = $collectionName;
	}

	public function getCollectionName() : string
	{
		return $this->collectionName;
	}

	/**
	 * @param  MediaAssociatorHelper  $this
	 * @param  string|null            $collectionName
	 *
	 * @return void
	 */
	public function clearCollectionIfNeeded() : void
	{
		if ($this->isMultipleFile())
			return ;

		$this->getModel()->clearMediaCollection(
			$this->getCollectionName()
		);
	}

	public function isMultipleFile() : bool
	{
		return $this->getParameter('multiple', false);
	}

	public function getParameter(string $name, $default = null) : mixed
	{
		return $this->getParameters()[$name] ?? $default;
	}

	public function getParameters() : array
	{
		return $this->parameters;
	}

	public function setParameters(array $parameters) : void
	{
		$this->parameters = $parameters;
	}

	public function getModel() : Model
	{
		return $this->model;
	}

	public function setModel(Model $model) : void
	{
		$this->model = $model;
	}

	public function storeModelAttributes()
	{
		//TODO TODO TODO TODO TODO
		if ($this->isMultipleFile())
			return null;

		$attributeName = $this->getParameter('attributeName', null);

		$this->getModel()->{$attributeName} = $this->getMedia()->getKey();
		$this->getModel()->save();
	}

	public function getDeleteUrl() : string
	{
		return $this->getModel()
			->getDeleteMediaUrlByKey(
				$this->getMedia()->getKey()
			);
	}

	public function getMediaName() : string
	{
		return $this->getMedia()->name;
	}

	public function getServeImageUrl() : string
	{
		return $this->getMedia()->getServeImageUrl();
	}

	public function getMediaLabel() : ? string
	{
		return $this->getParameter('label', null);
	}

	public function getMediaFilename() : ? string
	{
		return $this->getParameter('filename', null);
	}

	public function storeDataAndReturnMessage()
	{
		$this->closeMediaProcedure();

		$this->storeModelAttributes();

		return [
			'success' => true,
			'filename' => $this->getMediaName(),
			'fileurl' => $this->getServeImageUrl(),
			'deleteurl' => $this->getDeleteUrl(),
			'thumburl' => $this->getMediaThumb()
		];

	}

	/**
	 * @return void
	 */
	public function setMediaLabel() : void
	{
		if ($label = $this->getMediaLabel())
			$this->file = $this->file->usingName($label);
	}

	/**
	 * @return void
	 */
	public function setMediaFilename() : void
	{
		if ($filename = $this->getMediaFilename())
			$this->file = $this->file->usingFileName($filename);
	}

	/**
	 * @return void
	 */
	public function closeMediaProcedure() : void
	{
		$this->setMediaLabel();
		$this->setMediaFilename();

		$this->file = $this->file->toMediaCollection(
			$this->getCollectionName(), $this->getDisk()
		);
	}
}