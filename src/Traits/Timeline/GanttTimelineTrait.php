<?php

namespace IlBronza\CRUD\Traits\Timeline;

use IlBronza\Buttons\Button;

trait GanttTimelineTrait
{
	public function getGanttButton() : Button
	{
		return Button::create([
			'href' => $this->getGanttUrl(),
			'text' => 'crud::fields.gantt',
			'icon' => 'chart-gantt'
		]);
	}

	public function getGanttUrl() : string
	{
		return $this->getKeyedRoute('timelineContainer');
	}


}