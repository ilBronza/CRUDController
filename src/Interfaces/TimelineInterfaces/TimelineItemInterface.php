<?php

namespace IlBronza\CRUD\Interfaces\TimelineInterfaces;

use Carbon\Carbon;
use IlBronza\CRUD\Interfaces\TimelineInterfaces\TimelineGroupInterface;

interface TimelineItemInterface
{
	public function getTimelineItemId(? TimelineGroupInterface $groupModel) : string;
	public function getTimelineItemGroupId(? TimelineGroupInterface $groupModel) : string;
	public function getTimelineItemType(? TimelineGroupInterface $groupModel) : string;

	public function getTimelineItemStartsAt(? TimelineGroupInterface $groupModel) : Carbon;
	public function getTimelineItemEndsAt(? TimelineGroupInterface $groupModel) : Carbon;

	public function getTimelineItemProgress(? TimelineGroupInterface $groupModel) : float;

	public function getTimelineItemTitle(? TimelineGroupInterface $groupModel) : string;
	public function getTimelineItemPopuptitle(? TimelineGroupInterface $groupModel) : string;
	public function getTimelineItemDescription(? TimelineGroupInterface $groupModel) : ? string;

	// public function getTimelineItemContent(? TimelineGroupInterface $groupModel) : string;

	public function getTimelineItemCssStyles(? TimelineGroupInterface $groupModel) : array;
	public function getTimelineItemHtmlClasses(? TimelineGroupInterface $groupModel) : array;

	// public function getTimelineItemHtmlClasses(? TimelineGroupInterface $groupModel) : array;

	public function getTimelineItemUpdateUrl(? TimelineGroupInterface $groupModel) : ? string;

	public function getTimelineItemActions(? TimelineGroupInterface $groupModel) : array;
	public function getTimelineItemRightLinks(? TimelineGroupInterface $groupModel) : array;


}