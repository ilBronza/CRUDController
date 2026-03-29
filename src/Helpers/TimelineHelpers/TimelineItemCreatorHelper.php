<?php

namespace IlBronza\CRUD\Helpers\TimelineHelpers;

use IlBronza\CRUD\Helpers\TimelineHelpers\TimelineItem;
use IlBronza\CRUD\Interfaces\TimelineInterfaces\TimelineGroupInterface;
use IlBronza\CRUD\Interfaces\TimelineInterfaces\TimelineItemInterface;

class TimelineItemCreatorHelper
{
	static function createItemByModel(TimelineItemInterface $model, TimelineGroupInterface $groupModel = null) : TimelineItem
	{
		$item = new TimelineItem();

		$item->id = $model->getTimelineItemId($groupModel);
		$item->links = $model->getTimelineItemActions($groupModel);

		$item->rightLinks = $model->getTimelineItemRightLinks($groupModel);

		$item->start = $model->getTimelineItemStartsAt($groupModel);
		$item->end = $model->getTimelineItemEndsAt($groupModel);
		$item->itemType = $model->getTimelineItemType($groupModel);

		$item->progress = $model->getTimelineItemProgress($groupModel);

		if($groupModel)
			$item->group = $groupModel->getTimelineGroupId($groupModel);

		$item->cssStyles = $model->getTimelineItemCssStyles($groupModel);

		$stylesString = [];

		foreach($item->cssStyles as $name => $parameters)
			$stylesString[] = "{$name}: {$parameters};";

		$item->style = implode(" ", $stylesString);


		$item->title = $model->getTimelineItemTitle($groupModel);

		$item->popupTitle = $model->getTimelineItemPopuptitle($groupModel);

		$item->description = $model->getTimelineItemDescription($groupModel);

		$item->htmlClasses = $model->getTimelineItemHtmlClasses($groupModel);

		return $item;
	}
}