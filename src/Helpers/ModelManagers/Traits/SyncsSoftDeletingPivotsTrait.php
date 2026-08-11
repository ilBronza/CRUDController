<?php

namespace IlBronza\CRUD\Helpers\ModelManagers\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

use function array_key_exists;
use function class_uses_recursive;
use function collect;
use function get_class;
use function in_array;

trait SyncsSoftDeletingPivotsTrait
{
	static array $pivotTablesWithDeletedAt = [];

	public function pivotUsesSoftDeletes(BelongsToMany $relation) : bool
	{
		if(in_array(SoftDeletes::class, class_uses_recursive($relation->getPivotClass())))
			return true;

		$this->assertPivotTableHasNoDeletedAt($relation);

		return false;
	}

	// Nessun using() dichiarato ma la tabella ha deleted_at: la relazione e' rotta
	// in scrittura e quasi certamente anche in lettura. Si rompe subito e rumorosamente.
	private function assertPivotTableHasNoDeletedAt(BelongsToMany $relation)
	{
		$table = $relation->getTable();

		if(! array_key_exists($table, static::$pivotTablesWithDeletedAt))
			static::$pivotTablesWithDeletedAt[$table] = Schema::connection(
				$relation->getParent()->getConnectionName()
			)->hasColumn($table, 'deleted_at');

		if(! static::$pivotTablesWithDeletedAt[$table])
			return;

		throw new \Exception('La tabella pivot ' . $table . ' ha la colonna deleted_at ma la relazione ' . get_class($relation->getParent()) . ' non dichiara using() con una classe che estende BasePivotModel. Dichiaralo: senza, le riassociazioni falliscono in silenzio.');
	}

	public function syncSoftDeletingPivot(BelongsToMany $relation, array|Collection $toRelate)
	{
		$toRelate = collect($toRelate)->map(
			fn($value) => $value instanceof Model ? $value->getKey() : $value
		)->filter();

		$relatedKey = $relation->getRelatedPivotKeyName();

		$pivots = $relation->newPivotQuery()
			->orderBy($relation->newPivot()->getKeyName())
			->get()
			->map(fn($record) => $relation->newPivot((array) $record, true))
			->groupBy($relatedKey);

		foreach($pivots as $key => $keyPivots)
		{
			$alive = $keyPivots->reject->trashed();

			if(! $toRelate->contains($key))
			{
				foreach($alive as $pivot)
					$pivot->delete();

				continue;
			}

			if($alive->isNotEmpty())
				continue;

			$keyPivots->last()->restore();
		}

		foreach($toRelate->diff($pivots->keys()) as $key)
			$relation->attach($key);
	}
}
