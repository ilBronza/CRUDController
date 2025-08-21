<?php

namespace IlBronza\CRUD\Http\Controllers\Traits\StandardTraits;

use IlBronza\CRUD\Traits\CRUDEditUpdateTrait;
use IlBronza\CRUD\Traits\CRUDRelationshipTrait;
use Illuminate\Http\Request;

trait PackageStandardEditUpdateTrait
{
    use CRUDEditUpdateTrait;
    use CRUDRelationshipTrait;

    public $allowedMethods = [
        'edit',
        'update',
    ];

    public function getEditParametersFile() : ? string
    {
        return config($this->getBaseConfigName() . ".models.$this->configModelClassName.parametersFiles.edit");
    }

    public function edit(string $model)
    {
        $model = $this->findModel($model);

        return $this->_edit($model);
    }

    public function update(Request $request, string $model)
    {
        $model = $this->findModel($model);

        return $this->_update($request, $model);
    }
}