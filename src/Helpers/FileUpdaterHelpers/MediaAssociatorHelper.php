<?php

namespace IlBronza\CRUD\Helpers\FileUpdaterHelpers;

use Illuminate\Database\Eloquent\Model;

class MediaAssociatorHelper
{
	use MediaAssociatorBTSTrait;

	public Model $model;
	public array $parameters;
	public string $collectionName;
	public string $disk;

	public function __construct(Model $model, string $collectionName = null, string $disk = null, array $parameters = [])
	{
		$this->setModel($model);

		$this->setParameters($parameters);
		$this->setCollectionName($collectionName);
		$this->setDisk($disk);

		$this->clearCollectionIfNeeded();
	}

	public static function associateFromRequest(Model $model, string $fileRequestAttributeName, string $collectionName = null, string $disk = null, array $parameters = [])
	{
		$helper = new static($model, $collectionName, $disk, $parameters);

		$helper->file = $helper->getModel()
					->addMediaFromRequest($fileRequestAttributeName);

		return $helper->storeDataAndReturnMessage();
	}

}