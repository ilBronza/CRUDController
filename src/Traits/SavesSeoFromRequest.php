<?php

namespace IlBronza\CRUD\Traits;

use IlBronza\CRUD\Helpers\Seo\SeoFormHelper;
use IlBronza\CRUD\Models\Seo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait SavesSeoFromRequest
{
    protected function saveSeoFromRequest(Model $model, Request $request): ?Seo
    {
        return SeoFormHelper::saveFromRequest($model, $request);
    }
}
