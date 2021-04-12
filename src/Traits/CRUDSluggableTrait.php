<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

trait CRUDSluggableTrait
{
	protected static function boot()
	{
		parent::boot();

		static::saving(function ($model) {
			if(! $slug = $model->slug)
				$slug = $model->getName();

			$slug = Str::slug($slug);

			$existingsSlugs = DB::table((new self())->getTable())
					->select('slug')
					->where($model->getKeyName(), '!=', $model->getKey())
					->where(function($query) use($slug)
					{
						$query->where('slug', $slug);
						$query->orWhere('slug', 'LIKE', $slug . '-%');
					})
					->get();

			if(! $existingsSlugs->firstWhere('slug', $slug))
				return $model->slug = $slug;

			$i = 0;

			do
			{
				$i++;
			}
			while($existingsSlugs->firstWhere('slug', $slug . '-' . $i));
				return $model->slug = $slug . '-' . $i;
		});
	}
}