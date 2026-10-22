<?php

namespace IlBronza\CRUD\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Seo extends Model
{
    protected $table = 'crud_seo';

    protected $fillable = [
        'locale', 'title', 'description', 'canonical_url', 'robots',
        'og_title', 'og_description', 'og_image', 'og_image_alt', 'og_type',
    ];

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
