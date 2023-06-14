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

		$this->setUpdateFieldsetsProvider();


		//gestire la validazione del file come tipo file da array parameters?
		$fieldName = str_replace("[]", "", $request->fieldname);

		$field = $this->getFieldsetsProvider()->getFormFieldByName($fieldName);

		$field->setModel(
			$this->getModel()
		);

		// if(! $request->multiple)
		if(! $field->isMultiple())
			$this->getModel()->clearMediaCollection($fieldName);

		//gestire update or store :-/
		//gestire index
		//gestire uuid

		$file = $this->getModel()->addMediaFromRequest('file')
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

		if(! $field->isMultiple())
		{
			$this->getModel()->{$fieldName} = $file->getKey();
			$this->getModel()->save();			
		}

		return [
			'success' => true,
			'filename' => $file->name,
			'fileurl' => $file->getServeImageUrl(),
			'deleteurl' => $this->getModel()->getDeleteMediaUrlByKey($file->getKey()),
			'thumburl' => $thumbUrl
		];
	}
}