<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ilBronza\Form\Facades\Form;

trait CRUDUpdateEditorTrait
{
	use CRUDValidateTrait;

	/**
	 * validate request and return requested values for update
	 **/
	private function validateUpdateEditorRequest(Request $request)
	{
		$validationArray = $this->getUpdateValidationArray();

		mori($request->all());

		// $validationArray = array_intersect(array1, array2)

		mori($validationArray);



		return $request->validate($validationArray);

		return $this->validateRequestByType($request, 'update');
	}


	/**
	 * validate request and update model
	 *
	 * @param Request $request, Model $modelInstance
	 * @return Response redirect
	 **/
	public function _updateEditor(Request $request, $modelInstance)
	{
		$this->modelInstance = $modelInstance;

		$this->checkIfUserCanUpdate();

		$parameters = $this->validateUpdateEditorRequest($request);

		mori($parameters);

		$this->updateModelInstance($parameters);

		if(method_exists($this, 'associateRelationshipsByType'))
			$this->associateRelationshipsByType($parameters, 'update');

		$this->sendUpdateSuccessMessage();

		return redirect()->to(
			$this->getAfterUpdatedRedirectUrl()
		);
	}
}