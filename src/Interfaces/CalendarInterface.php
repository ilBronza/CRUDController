<?php

namespace IlBronza\CRUD\Interfaces;

use Carbon\Carbon;
use Illuminate\Support\Collection;

interface CalendarInterface
{
	public function getCalendarDateStart() : ? Carbon;
	public function getCalendarDateEnd() : ? Carbon;
}