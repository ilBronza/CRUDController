<?php

namespace IlBronza\CRUD\Helpers\AjaxSearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AjaxSearchExecutor
{
	public static function search(string $contextKey, string $query) : array
	{
		$query = trim($query);

		if ($query === '')
			return [];

		$fieldConfig = config('crud.ajaxSearchFields.' . $contextKey);

		if (! is_array($fieldConfig) || empty($fieldConfig['models']) || ! is_array($fieldConfig['models']))
			return [];

		$perModelLimit = (int) ($fieldConfig['per_model_limit']
			?? config('crud.ajaxSearch.per_model_limit', 20));

		if ($perModelLimit < 1)
			$perModelLimit = 20;

		$totalLimit = (int) ($fieldConfig['total_limit']
			?? config('crud.ajaxSearch.total_limit', 100));

		$results = [];

		foreach ($fieldConfig['models'] as $modelKey => $columns)
		{
			if (count($results) >= $totalLimit)
				break;

			if (! is_array($columns) || $columns === [])
				continue;

			$modelClass = static::resolveModelClass((string) $modelKey);

			if (! $modelClass || ! is_subclass_of($modelClass, Model::class))
				continue;

			$remaining = $totalLimit - count($results);
			$limit = min($perModelLimit, $remaining);

			/** @var class-string<Model> $modelClass */
			$rows = static::queryModel($modelClass, $columns, $query, $limit);

			foreach ($rows as $row)
			{
				$results[] = [
					'model' => (string) $modelKey,
					'id' => $row->getKey(),
					'label' => static::buildLabel($row, $columns),
				];
			}
		}

		return $results;
	}

	/**
	 * @param  class-string<Model>  $modelClass
	 * @param  list<string>  $columns
	 * @return Collection<int, Model>
	 */
	protected static function queryModel(string $modelClass, array $columns, string $term, int $limit) : Collection
	{
		$columns = array_values(array_filter($columns, static function ($c) : bool {
			return is_string($c) && $c !== '';
		}));

		if ($columns === [])
			return collect();

		/** @var Model $prototype */
		$prototype = $modelClass::make();
		$keyName = $prototype->getKeyName();

		$select = array_values(array_unique(array_filter(array_merge(
			is_string($keyName) ? [$keyName] : [],
			$columns
		))));

		$like = static::sqlLikePattern($term);

		return $modelClass::query()
			->select($select)
			->where(function ($q) use ($columns, $like) : void {
				foreach ($columns as $column)
				{
					if (! is_string($column) || $column === '')
						continue;

					$q->orWhere($column, 'LIKE', $like);
				}
			})
			->limit($limit)
			->get();
	}

	protected static function sqlLikePattern(string $term) : string
	{
		$escaped = str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term);

		return '%' . $escaped . '%';
	}

	protected static function buildLabel(Model $row, array $columns) : string
	{
		$attributes = $row->getAttributes();

		foreach ($columns as $column)
		{
			if (! is_string($column) || $column === '' || ! array_key_exists($column, $attributes))
				continue;

			$value = $row->getAttribute($column);

			if ($value !== null && $value !== '')
				return is_scalar($value) ? (string) $value : json_encode($value);
		}

		return (string) $row->getKey();
	}

	protected static function resolveModelClass(string $key) : ?string
	{
		if (class_exists($key))
			return $key;

		$morphed = Relation::getMorphedModel($key);

		if ($morphed && class_exists($morphed))
			return $morphed;

		$studly = Str::studly(Str::singular($key));
		$candidates = [
			'App\\Models\\' . $studly,
			'App\\' . $studly,
		];

		foreach ($candidates as $candidate)
			if (class_exists($candidate))
				return $candidate;

		return null;
	}
}
