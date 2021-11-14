<?php

namespace IlBronza\CRUD\Traits;

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
		
		if(! $request->multiple)
			$this->modelInstance->clearMediaCollection($fieldName);

		//gestire update or store :-/
		//gestire index
		//gestire uuid

		$file = $this->modelInstance->addMediaFromRequest('file')
			->toMediaCollection($fieldName);

		//TODO TODO TODO TODO TODO
		$thumbUrl = ($isImage ?? false)? $file->getTemporaryUrl(Carbon::now()->addMinutes(5), 'thumb') : null;

		$this->modelInstance->{$fieldName} = $file->getKey();
		$this->modelInstance->save();

		return [
			'success' => true,
			'filename' => $file->name,
			'fileurl' => $file->getUrl(),
			'deleteurl' => $file->getDeleteUrl(),
			'thumburl' => $thumbUrl
		];
	}
}