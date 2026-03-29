<?php

namespace IlBronza\CRUD\Traits\Timeline;

use Carbon\Carbon;
use IlBronza\CRUD\Interfaces\TimelineInterfaces\TimelineGroupInterface;
use Illuminate\Support\Str;

trait IsTimelineItemTrait
{
	public function getTimelineUpdateUrl() : ? string
	{
		try
		{
			return $this->getKeyedRoute('asTimelineItem.update');			
		}
		catch(\Exception $e)
		{
			return null;
		}
	}

	public function getTimelineItemId(? TimelineGroupInterface $groupModel) : string
	{
		return $this->getKey();
	}

	public function getTimelineItemStartsAt(? TimelineGroupInterface $groupModel) : Carbon
	{
		return $this->getStartsAt() ?? Carbon::now();
	}

	public function getTimelineItemType(? TimelineGroupInterface $groupModel) : string
	{
		if(! $groupModel)
			return class_basename($this);

		return class_basename($this) . '-' . class_basename($groupModel);
	}

	public function getTimelineItemEndsAt(? TimelineGroupInterface $groupModel) : Carbon
	{
		return $this->getEndsAt() ?? Carbon::now()->addDays(3);
	}

	public function getTimelineItemProgress(? TimelineGroupInterface $groupModel) : float
	{
		return 0;
	}

	// public function getTimelineGroupContent(? TimelineGroupInterface $groupModel) : string
	// {
	// 	return $this->getName();
	// }

	// public function getTimelineGroupName(? TimelineGroupInterface $groupModel) : string
	// {
	// 	return $this->getName();		
	// }

	public function getTimelineItemCssStyles(? TimelineGroupInterface $groupModel) : array
	{
		$result = [];

		if($value = $this->getCssBackgroundColorValue($groupModel))
		{
			$result['background-color'] = $value;
			$result['color'] = $this->getCssTextColorValue();
		}

		return $result;
	}

	public function getTimelineItemDescription(? TimelineGroupInterface $groupModel) : ? string
	{
		return $this->getDescription();
	}

	public function getTimelineItemHtmlClasses(? TimelineGroupInterface $groupModel) : array
	{
		return [
			class_basename($this)
		];
	}

	public function getTimelineItemUpdateUrl(? TimelineGroupInterface $groupModel) : ? string
	{
		return $this->getUpdateUrl();
	}



	// public function getTimelineGroupHtmlClasses(? TimelineGroupInterface $groupModel) : array
	// {
	// 	return [
	// 		'group-' . Str::slug($this->getTimelineGroupContent())
	// 	];
	// }

}