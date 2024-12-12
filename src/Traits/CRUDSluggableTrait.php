<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

//public $nameField = 'name'
//the field to take the string from, when creating the alias

//public $slugField = 'slug'
//the field where to save the alias value

trait CRUDSluggableTrait
{
	static function getSlugField()
	{
		return static::$slugField ?? 'slug';
	}

	public function getNameForSlug()
	{
		return $this->getName();
	}

	public function getSlug() : ? string
	{
		return $this->{$this->getSlugField()};
	}

	public static function bootCRUDSluggableTrait()
	{
		static::saving(function ($model)
		{
			$slugField = static::getSlugField();

			if(! $slug = $model->{$slugField})
			{
				$slug = $model->getNameForSlug();
				$slug = Str::slug($slug);
			}

			$slug = Str::limit($slug, config('app.slug_length', 128));

			$existingsSlugs = DB::table((new static)->getTable())
					->select($slugField)
					->where($model->getKeyName(), '!=', $model->getKey())
					->where(function($query) use($slug, $slugField)
					{
						$query->where($slugField, $slug);
						$query->orWhere($slugField, 'LIKE', $slug . '-%');
					})
					->get();

			if(! $existingsSlugs->firstWhere($slugField, $slug))
			{
				$model->{$slugField} = $slug;
			}
			else
			{
				$i = 0;

				do
				{
					$i++;
				}
				while($existingsSlugs->firstWhere($slugField, $slug . '-' . $i));
				
				$model->{$slugField} = $slug . '-' . $i;
			}
		});
	}
}