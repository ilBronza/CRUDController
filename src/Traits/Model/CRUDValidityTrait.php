<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;
use IlBronza\CRUD\Traits\Model\CRUDBrotherhoodTrait;

trait CRUDValidityTrait
{
    use CRUDBrotherhoodTrait;

    private function unvalidateBrothers()
    {
        static::brothers()
            ->where($this->getKeyName(), '!=', $this->getKey())
            ->update([
                'valid' => false
            ]);
    }

    public function checkTimingValidity()
    {
        $elements = static::brothers()->get();

        $elements->push($this);

        $elements = $elements->sortBy('valid_from')->values()->all();

        $currentValidFrom = null;

        foreach($elements as $element)
            if($element->isValidBeforeNow())
                $currentValidFrom = $element;

        if($currentValidFrom->isValidAfterNow())
            return $currentValidFrom->setValid();

        static::whereIn('id', $elements->pluck($this->getKeyName()))->update(['valid' => false]);

        Ukn::w('Nessun element valido per ' . $this->getName());
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

    private function isValidBeforeNow()
    {
        return $this->isValidBefore(Carbon::now());
    }

    private function isValidAfterNow()
    {
        return $this->isValidAfter(Carbon::now());
    }

    private function setValid()
    {
        static::where($this->getKeyName(), $this->getKey())->update(['valid' => true]);

        $this->unvalidateBrothers();
    }


}