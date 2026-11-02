<?php

namespace App\Models;

use IlBronza\CRUD\Models\Casts\TranslatableField;

class TranslatableCommandArticle extends TranslatableCommandArticleBase
{
    protected $table = 'translation_command_articles';

    protected function casts(): array
    {
        return ['body' => TranslatableField::class];
    }
}
