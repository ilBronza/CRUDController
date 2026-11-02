<?php

namespace App\Models;

use IlBronza\CRUD\Models\Casts\TranslatableField;
use Illuminate\Database\Eloquent\Model;

abstract class TranslatableCommandArticleBase extends Model
{
    protected $casts = ['title' => TranslatableField::class];
}
