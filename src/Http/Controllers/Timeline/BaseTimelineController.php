<?php

namespace IlBronza\CRUD\Http\Controllers\Timeline;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineGroupCreatorHelper;
use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineItemCreatorHelper;
use IlBronza\Products\Models\Orders\Orderrow;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class BaseTimelineController extends CRUD
{
	public array $groups = [];
	public array $items = [];

	abstract public function getEndpoint() : string;

	public function getTimelineUpdateRoute() : string
	{
		$placeholder = Orderrow::gpc()::make();
		$placeholder->id = config('datatables.replace_model_id_string');

		return $placeholder->getTimelineUpdateUrl();
	}

	public function returnGanttContainer()
	{
		$apiEndpoint = $this->getEndpoint();
		$timelineUpdateRoute = $this->getTimelineUpdateRoute();

		$modelInstance = $this->getModel();

		$buttons = $this->getButtons();

		$zoom = $this->zoom ?? config('crud.timelineZoom', 14);

		return view('crud::timeline.timeline', compact( 'apiEndpoint', 'timelineUpdateRoute', 'modelInstance', 'buttons', 'zoom'));
	}

	public function getOptionMethod(string $option) : string
	{
		return 'get' . Str::studly($option) . 'TimelineData';
	}

	public $allowedMethods = [
		'timeline',
		'container'
	];

	public function createGroupsByCollection(Collection $elements)
	{
		foreach($elements as $element)
			$this->groups[] = TimelineGroupCreatorHelper::createGroupByModel($element);
	}

	public function createItemsByCollection(Collection $elements)
	{
		foreach($elements as $element)
			$this->items[] = TimelineItemCreatorHelper::createItemByModel($element);
	}

	public function createItemsByCollectionAndGetter(Collection $elements, string $groupGetterMethod)
	{
		foreach($elements as $element)
			$this->items[] = TimelineItemCreatorHelper::createItemByModel($element, $element->{$groupGetterMethod}());

	}

	public function getGroups() : array
	{
		return $this->groups;
	}

	public function getItems() : array
	{
		return $this->items;
	}

	public function sendResponse()
	{
		return [
			'itemTemplate' => 'operator',
			'groups' => $this->getGroups(),
			'items' => $this->getItems()
		];		
	}
}