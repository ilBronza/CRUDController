<?php

namespace IlBronza\CRUD\Http\Controllers;

trait CompletePackageShowTrait
{
    use BasePackageShowTrait;

    public function show(string $string)
    {
        $model = $this->findModel($string);

        return $this->_show($model);
    }
}