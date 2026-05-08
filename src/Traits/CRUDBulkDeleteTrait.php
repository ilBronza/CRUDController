<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Http\Request;

trait CRUDBulkDeleteTrait
{
	public function bulkDelete(Request $request)
	{
		if($request->input('_method') === 'DELETE')
			$request->setMethod('DELETE');

		$this->validateBulkKeys($request);

		$keyName = $this->getPlaceholderModel()->getKeyName();

		$ids = $request->input('ids', []);

		$models = $this->getModelClass()::whereIn(
			$keyName,
			$ids
		)->get();


		$deletedIds = [];

		foreach($models as $model)
		{
			$model->deleterDelete();
			$deletedIds[] = $model->getKey();
		}

		$message = __('crud::crud.elementsSuccesfullyDeleted', [
			'elements' => count($deletedIds)
		]);

		if(request()->ajax())
		{
			return response()->json([
				'success' => true,
				'message' => $message,
				'action' => 'remove',
				'ids' => $deletedIds
			]);
		}

		return redirect()->to(
			$this->getDeletedRedirectUrl()
		)->with('crud.success', $message);
	}

	public function validateBulkKeys(Request $request)
	{
		$table = $this->getPlaceholderModel()->getTable();
		$key = $this->getPlaceholderModel()->getKeyName();

		$request->validate([
			'ids' => 'required|array',
			'ids.*' => 'exists:' . $table . ',' . $key
		]);
	}
}

