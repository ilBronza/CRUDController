<?php

namespace IlBronza\CRUD\Traits;

use Carbon\Carbon;
use Illuminate\Http\Request;

use function dd;

trait CRUDUploadFileTrait
{
	//TODO deprecated
	//sostituire con CrudRequestHelper::isFileUploadRequest($request);
//	public function hasFileUploadRequest(Request $request)
//	{
//
//		return $request->input('ib-fileupload', false);
//	}

	public function _uploadFile(Request $request, string $type = 'update', string $attributeName = null)
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

		$provider = $this->getFieldsetsProvider();

		$provider->setModel(
			$this->getModel()
		);

		$field = $provider->getFormFieldByName($fieldName);

		$collectionName = method_exists($field, 'getMediaCollection')
			? $field->getMediaCollection()
			: $fieldName;

		// if(! $request->multiple)
		if(! $field->isMultiple())
			$this->getModel()->clearMediaCollection($collectionName);

		//gestire update or store :-/
		//gestire index
		//gestire uuid

		$file = $this->getModel()->addMediaFromRequest('file')
			->toMediaCollection(
				$collectionName,
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
			$shouldPersist = method_exists($field, 'shouldPersistToModelAttribute')
				? $field->shouldPersistToModelAttribute()
				: true;

			if(! $shouldPersist)
				return [
					'success' => true,
					'filename' => $file->name,
					'fileurl' => $file->getServeImageUrl(),
					'deleteurl' => $this->getModel()->getDeleteMediaUrlByKey($file->getKey()),
					'thumburl' => $thumbUrl
				];

			if(! $attributeName)
				$attributeName = $fieldName;

			$this->getModel()->{$attributeName} = $file->getKey();
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