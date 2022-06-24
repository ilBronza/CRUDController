<?php

namespace IlBronza\CRUD\Traits\Model;

use Carbon\Carbon;
use IlBronza\CRUD\Traits\Model\CRUDBrotherhoodTrait;
use IlBronza\Ukn\Ukn;

trait CRUDValidityTrait
{
    use CRUDBrotherhoodTrait;

    private function unvalidateBrothers()
    {
        static::brothers()
            // ->where($this->getKeyName(), '!=', $this->getKey())
            ->update([
                'valid' => false
            ]);
    }

    private function deleteWithEmptyValidity()
    {
        // if(static::class == "App\ManufacturerWave")
        //     return ;

        static::brothers()->whereNull('valid_from')->delete();
    }

    public function checkTimingValidity()
    {
        // $this->deleteWithEmptyValidity();
        $elements = static::brothers()->get();

        $elements->push($this);

        $elementIds = $elements->pluck('id');

        $elements = $elements->sortBy('valid_from')->values()->all();

        $currentValidFrom = null;

        foreach($elements as $element)
            if($element->isValidAfterNow())
                if($element->isValidBeforeNow())
                    $currentValidFrom = $element;

        if($currentValidFrom)
            return $currentValidFrom->setValid();

        static::whereIn('id', $elementIds)->update(['valid' => false]);

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

    public function isValidAfterNow()
    {
        return $this->isValidAfter(Carbon::now());
    }

    public function isValid()
    {
        return $this->valid;
    }

    private function setValid()
    {
        $this->unvalidateBrothers();

        static::where($this->getKeyName(), $this->getKey())->update(['valid' => true]);

        // dd(static::where($this->getKeyName(), $this->getKey())->first());
    }


}