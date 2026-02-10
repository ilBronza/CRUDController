<?php

namespace IlBronza\CRUD\Http\Controllers\Timeline;

use IlBronza\CRUD\CRUD;
use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineGroupCreatorHelper;
use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineItemCreatorHelper;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

abstract class BaseTimelineController extends CRUD
{
	public array $groups = [];
	public array $items = [];

	abstract public function getEndpoint() : string;

	public function returnGanttContainer()
	{
		$apiEndpoint = $this->getEndpoint();

		$modelInstance = $this->getModel();

		$buttons = $this->getButtons();

		return view('crud::timeline.timeline', compact( 'apiEndpoint', 'modelInstance', 'buttons'));
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