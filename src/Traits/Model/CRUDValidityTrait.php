<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;
use IlBronza\CRUD\Traits\Model\CRUDBrotherhoodTrait;
use IlBronza\Ukn\Ukn;

trait CRUDValidityTrait
{
    use CRUDBrotherhoodTrait;

    abstract public function canBeValid();

    private function unvalidateBrothers()
    {
        foreach(static::brothers()->get() as $brother)
            if($brother->isValid()||$this->hasNotBeenUnvalidated())
                $brother->setUnvalid($save = true);
    }

    public function checkTimingValidity()
    {
        $elements = $this->brothers()->get();

        if($this->created_at === null)
            $this->created_at = Carbon::now();

        $elements->push($this);

        $elementIds = $elements->pluck('id');

        $elements = $elements->sortBy('valid_from')->values()->all();

        $validElements = collect();

        foreach($elements as $element)
            if($element->isValidAfterNow())
                if($element->isValidBeforeNow())
                    if($element->canBeValid())
                        $validElements->push($element);

        if(count($validElements) == 0)
        {
            static::whereIn('id', $elementIds)->update(['valid' => false]);

            throw new \Exception('nessun elemento valido trovato per ' . class_basename($this) . ' ' . $this->getKey());
        }

        $valid = $validElements->sortBy('created_at')->last();

        $valid->unvalidateBrothers();

        return $valid->setValid();
    }

    public function scopeValid($query)
    {
        return $query->where('valid', true);
    }

    public function scopeNotValid($query)
    {
        return $query->where('valid', false)->orWhereNull('valid');
    }

    private function isValidBefore(Carbon $date)
    {
        if(empty($this->valid_from))
            return true;

        if($this->valid_from < $date)
            return true;

        return false;
    }

    private function isValidAfter(Carbon $date)
    {
        if(empty($this->valid_to))
            return true;

        if($this->valid_to > $date)
            return true;

        return false;
    }

    public function hasNotBeenUnvalidated()
    {
        if(! array_key_exists('unvalidated_at', $this->attributes))
            false;

        return ! $this->unvalidated_at;
    }

    private function isValidBeforeNow()
    {
        return $this->isValidBefore(Carbon::now());
    }

    public function isValidAfterNow()
    {
        return $this->isValidAfter(Carbon::now());
    }

    public function isValid()
    {
        return $this->valid;
    }

    private function setValid(bool $save = true)
    {
        $this->validated_at = Carbon::now();
        $this->valid = true;

        if($save)
            $this->save();
    }

    public function setUnvalid(bool $save = true)
    {
        $this->unvalidate();

        if($save)
            $this->save();
    }

    public function unvalidate()
    {
        $this->valid = false;
        $this->unvalidated_at = Carbon::now();
    }
}