<?php

namespace IlBronza\CRUD\Helpers\TimelineHelpers;

use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineGroup;
use IlBronza\CRUD\Interfaces\TimelineInterfaces\TimelineGroupInterface;

class TimelineGroupCreatorHelper
{
	static function createGroupByModel(TimelineGroupInterface $model) : TimelineGroup
	{
		$timelineGroup = new TimelineGroup();

		$timelineGroup->id = $model->getTimelineGroupId();
		$timelineGroup->content = $model->getTimelineGroupContent();
		$timelineGroup->cssStyles = $model->getTimelineGroupCssStyles();

		$timelineGroup->name = $model->getTimelineGroupName();

		$timelineGroup->htmlClasses = $model->getTimelineGroupHtmlClasses();

		$timelineGroup->actions = $model->getTimelineGroupActions();


		$stylesString = [];

		foreach($timelineGroup->cssStyles as $name => $parameters)
			$stylesString[] = "{$name}: {$parameters};";

		$timelineGroup->style = implode(" ", $stylesString);
		
		return $timelineGroup;
	}
}