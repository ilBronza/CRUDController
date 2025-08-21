<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;

trait CRUDFlatSortingTrait
{
	public function storeMassReorder(Request $request)
	{
		$request->validate([
			'indexes' => 'array|required',
			'indexes.*' => 'string|max:36'
		]);

		$sortingIndexes = $request->indexes;

		foreach($sortingIndexes as $id => $sortingIndex)
		{
			if(! $element = $this->getModelClass()::find($id))
				continue;

			$element->setSortingIndex($sortingIndex);
			$element->save();
		}

		return ['success' => true];
	}

}