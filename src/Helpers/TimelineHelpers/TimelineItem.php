<?php

namespace IlBronza\CRUD\Helpers\TimelineHelpers;

use Carbon\Carbon;

class TimelineItem
{
	public string $id;
	public ? string $group;
	public string $title;
	public string $itemType;
	public string $style;
	public string $popupTitle;
	public ?string $updateUrl;
	public ? string $description;

	public array $links = [];
	public array $rightLinks = [];
	public array $cssStyles = [];
	public array $htmlClasses = [];

	public Carbon $start;
	public Carbon $end;

	public ? float $progress;

}