<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;

trait CRUDTimeRangesTrait
{
	public function scopeByIntersectPeriod($query, Carbon $startsAt, Carbon $endsAt, string $startField = 'starts_at', string $endField = 'ends_at')
	{
		//one of the dates is between the period
		$query->where(function($_query) use ($startsAt, $endsAt, $startField, $endField)
		{
			$_query->whereBetween($startField, [$startsAt, $endsAt])
				->orWhereBetween($endField, [$startsAt, $endsAt]);
		});

		//both dates are outside the period
		$query->orWhere(function($_query) use ($startsAt, $endsAt, $startField, $endField)
		{
			$_query->where($startField, '<', $startsAt)
				->where($endField, '>', $endsAt);
		});
	}

    public function scopeValidByDate($query, Carbon $date = null)
    {
        if(! $date)
            $date = Carbon::now();

        return $query->where(function($_query)
            {
                return $_query->where('valid_from', '<', Carbon::now())->orWhereNull('valid_from');
            })->where(function($_query)
            {
                return $_query->where('valid_to', '>', Carbon::now())->orWhereNull('valid_to');                
            });
    }
}