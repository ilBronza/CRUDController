<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;

trait CRUDFlatSortingTrait
{
	public function validateRequest(Request $request)
	{
		$request->validate([
			'indexes' => 'array|required',
			'indexes.*' => 'string|max:36'
		]);		
	}

	public function storeMassReorder(Request $request)
	{
		$this->validateRequest($request);

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