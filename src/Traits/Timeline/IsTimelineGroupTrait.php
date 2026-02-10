<?php

namespace IlBronza\CRUD\Traits\Timeline;

use Illuminate\Support\Str;

trait IsTimelineGroupTrait
{
	public function getTimelineGroupId() : string
	{
		return $this->getKey();
	}

	public function getTimelineGroupContent() : string
	{
		return $this->getName();
	}

	public function getTimelineGroupName() : string
	{
		return $this->getName();		
	}

	public function getTimelineGroupCssStyles() : array
	{
		$result = [];

		if($value = $this->getCssBackgroundColorValue())
		{
			$result['background-color'] = $value;
			$result['color'] = $this->getCssTextColorValue();
		}

		return $result;
	}

	public function getTimelineGroupHtmlClasses() : array
	{
		return [
			'group-' . Str::slug($this->getTimelineGroupContent())
		];
	}

	public function getTimelineGroupActions() : array
	{
		return [
			[
				'action' => 'open',
				'faIcon' => 'chart-gantt',
				'title' => 'Gantt ' . $this->getTimelineGroupName(),
				'url' => $this->getGanttUrl()
			]
		];
	}
}