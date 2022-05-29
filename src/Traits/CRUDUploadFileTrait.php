<?php

namespace IlBronza\CRUD\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

trait CRUDUploadFileTrait
{
	public function hasFileUploadRequest(Request $request)
	{
		return $request->input('ib-fileupload', false);		
	}

	public function _uploadFile(Request $request, string $type = 'update')
	{
		$request->validate([
			'file' => 'required|file',
			'fieldname' => 'string|required',
			'index' => 'nullable|numeric',
			'uuid' => 'string|nullable',
			'multiple' => 'boolean|nullable'
		]);

		//gestire la validazione del file come tipo file da array parameters?
		$fieldName = str_replace("[]", "", $request->fieldname);

		$field = $this->getFormFieldByTypeAndName('update', $fieldName);
		$field->assignModel($this->modelInstance);

		// if(! $request->multiple)
		if(! $field->isMultiple())
			$this->modelInstance->clearMediaCollection($fieldName);

		//gestire update or store :-/
		//gestire index
		//gestire uuid

		$file = $this->modelInstance->addMediaFromRequest('file')
			->toMediaCollection(
				$fieldName,
				$field->getDisk()
			);

		//TODO TODO TODO TODO TODO
		try
		{
			$thumbUrl = ($isImage ?? false)? $file->getTemporaryUrl(Carbon::now()->addMinutes(5), 'thumb') : null;
		}
		catch(\Exception $e)
		{
			$thumbUrl = ($isImage ?? false)? $file->getUrl() : null;			
		}

		$this->modelInstance->{$fieldName} = $file->getKey();
		$this->modelInstance->save();

		return [
			'success' => true,
			'filename' => $file->name,
			'fileurl' => $file->getServeImageUrl(),
			'deleteurl' => $file->getDeleteUrl(),
			'thumburl' => $thumbUrl
		];
	}
}