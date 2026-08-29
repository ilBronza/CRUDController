<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Archive;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\ModelHelpers\ModelFinderHelper;

class ArchiveController extends CRUD
{
	public $allowedMethods = ['archive', 'unarchive'];

	public function archive(string $model, string $key)
	{
		$element = ModelFinderHelper::getByClassKey($model, $key);

		if(! $element)
			abort(404);

		if(! $element->userCanArchive())
			abort(403, 'User can not archive this element');

		$element->archive();

		$this->broadcastCrudModel($element, 'archived');

		return [
			'success' => true,
			'action' => 'removeRow',
			'ids' => [
				$element->getKey()
			]
		];
	}

	public function unarchive(string $model, string $key)
	{
		$element = ModelFinderHelper::getArchivedByClassKey($model, $key);

		if(! $element)
			abort(404);

		if(! $element->userCanArchive())
			abort(403, 'User can not unarchive this element');

		$element->unarchive();

		$this->broadcastCrudModel($element, 'unarchived');

		return [
			'success' => true,
			'action' => 'removeRow',
			'ids' => [
				$element->getKey()
			]
		];
	}
}
