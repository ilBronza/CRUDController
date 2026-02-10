<?php

namespace IlBronza\CRUD\Helpers\TimelineHelpers;

class TimelineGroup
{
	public string $id;
	public string $content;
	public string $name;

	public array $cssStyles = [];
	public array $htmlClasses = [];

	public array $actions = [];
}