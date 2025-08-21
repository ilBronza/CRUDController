<?php

namespace IlBronza\CRUD\Helpers;

use Illuminate\Http\Request;

class CrudRequestHelper
{
	static function isSaveAndCopy(Request $request) : bool
	{
		return !! $request->input('save_and_copy', false);
	}

	static function isFileUploadRequest(Request $request) : bool
	{
		return $request->input('ib-fileupload', false);
	}

	static function isEditorUpdateRequest(Request $request) : bool
	{
		return $request->input('ib-editor', false);
	}

	static function isEditorReadRequest(Request $request) : bool
	{
		return $request->input('ib-editor-read', false);
	}
}