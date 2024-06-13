<?php

namespace IlBronza\CRUD\Http\Controllers\Traits;

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

    public function edit(string $type)
    {
        $type = $this->findModel($type);

        return $this->_edit($type);
    }

    public function update(Request $request, string $type)
    {
        $type = $this->findModel($type);

        return $this->_update($request, $type);
    }
}