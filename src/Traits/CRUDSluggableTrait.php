<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait CRUDSluggableTrait
{
	static function getSlugField()
	{
		return static::$slugField ?? 'slug';
	}

	protected static function boot()
	{
		parent::boot();

		static::saving(function ($model) {
			$slugField = static::getSlugField();

			if(! $slug = $model->{$slugField})
				$slug = $model->getName();

			$slug = Str::slug($slug);

			$existingsSlugs = DB::table((new self())->getTable())
					->select($slugField)
					->where($model->getKeyName(), '!=', $model->getKey())
					->where(function($query) use($slug, $slugField)
					{
						$query->where($slugField, $slug);
						$query->orWhere($slugField, 'LIKE', $slug . '-%');
					})
					->get();

			if(! $existingsSlugs->firstWhere($slugField, $slug))
				return $model->{$slugField} = $slug;

			$i = 0;

			do
			{
				$i++;
			}
			while($existingsSlugs->firstWhere($slugField, $slug . '-' . $i));
				return $model->{$slugField} = $slug . '-' . $i;
		});
	}
}