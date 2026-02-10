<?php

namespace IlBronza\CRUD\Traits\Timeline;

use Illuminate\Support\Collection;

trait GlobalTimelineTrait
{
	public function timeline(string $option = 'main')
	{
		$method = $this->getOptionMethod($option);

		return $this->$method();
	}

	public function container(string $option = 'main')
	{
		$this->option = $option;

		return $this->returnGanttContainer();
	}

	public function getButtons() : Collection
	{
		return collect();
	}

}