<?php

namespace IlBronza\CRUD\Traits\Calendar;

use Carbon\Carbon;
use IlBronza\Buttons\Button;

trait HasCalendarTrait
{
	public function getCalendarButton() : Button
	{
		return Button::create([
			'href' => $this->getCalendarUrl(),
			'text' => 'crud::fields.calendar',
			'icon' => 'calendar-days'
		]);
	}

	public function getCalendarUrl(): string
	{
		return $this->getKeyedRoute('calendar.index');
	}
	public function getCalendarDateStart() : ? Carbon
	{
		return $this->getStartsAt();
	}

	public function getCalendarDateEnd() : ? Carbon
	{
		return $this->getEndsAt();
	}

	public function getCalendarColor() : ? string
	{
		return config('products.models.order.calendar.colors.ok');
	}

	public function getCalendarBackgroundColor() : ? string
	{
		return 'white';
	}

	public function getCalendarHtmlClasses() : array
	{
		$result = [];

		if($this->getCalendarDateEnd()->day != $this->getCalendarDateStart()->day)
			$result = ['overnight'];

		return $result;
	}

	public function getCalendarTitle() : string
	{
		return $this->getTitle();
	}

	public function getCalendarStatus() : ? string
	{
		return null;
	}

	public function toCalendarEvent(): array
	{
		return [
			'id'    => $this->getKey(),
			'title' => $this->getCalendarTitle(),

			'start' => $this->getCalendarDateStart()?->toIso8601String(),
			'end'   => $this->getCalendarDateEnd()?->toIso8601String(),

			'allDay' => false,

			'color' => $this->getCalendarColor(),
			'backgroundColor' => $this->getCalendarBackgroundColor(),
			'classNames' => $this->getCalendarHtmlClasses(),

			'url' => $this->getEditUrl(),

			'extendedProps' => [
				'type' => static::class,
				'model_id' => $this->getKey(),
				'status' => $this->getCalendarStatus(),
			],
		];
	}}