<?php

namespace IlBronza\CRUD\Traits\Model;

trait CRUDModelJsonFieldTrait
{
    private function jsonField($value)
    {
        if(! $value)
            return [];

        return json_decode($value, true);
    }
}