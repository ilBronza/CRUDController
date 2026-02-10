<?php

namespace IlBronza\CRUD\Interfaces\TimelineInterfaces;

interface TimelineGroupInterface
{
	public function getTimelineGroupId() : string;

	public function getTimelineGroupName() : string;

	public function getTimelineGroupContent() : string;

	public function getTimelineGroupCssStyles() : array;

	public function getTimelineGroupHtmlClasses() : array;

	public function getTimelineGroupActions() : array;


}