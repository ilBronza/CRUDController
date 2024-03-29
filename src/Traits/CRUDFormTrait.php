<?php

namespace IlBronza\CRUD\Traits;

use Auth;
use IlBronza\CRUD\Traits\CRUDArrayFieldsTrait;
use IlBronza\CRUD\Traits\CRUDDbFieldsTrait;
use IlBronza\CRUD\Traits\CRUDFormFieldsTrait;
use IlBronza\CRUD\Traits\CRUDUploadFileTrait;
use IlBronza\FormField\DatabaseField;
use IlBronza\FormField\Facades\FormField;
use IlBronza\FormField\Fields\JsonFormField;
use IlBronza\Form\Form;
use IlBronza\Form\Helpers\FieldsetsProvider\EditFieldsetsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * come compilare il fieldset con opzioni e senza opzioni

    static $formFields = [
        'edit' => [
            'externalSizes' => [
                'external_length' => ['number' => 'integer|required'],
                'external_width' => ['number' => 'integer|required'],
                'external_height' => ['number' => 'integer|required'],
            ],
            'internalSizes' => [
                'fields' => [
                    'internal_length' => ['number' => 'integer|required'],
                    'internal_width' => ['number' => 'integer|required'],
                    'internal_height' => ['number' => 'integer|required'],
                ],
                'width' => 3,
                'fieldsWidth' => 3
            ],
            'workingSizes' => [
                'working_length' => ['number' => 'integer|required'],
                'working_width' => ['number' => 'integer|required'],
                'working_height' => ['number' => 'integer|required'],
            ],

 *
**/

trait CRUDFormTrait
{
	use CRUDUploadFileTrait;
	use CRUDDbFieldsTrait;
	use CRUDArrayFieldsTrait;
	use CRUDFormFieldsTrait;

	/**
	 * return form fieldsets based on form type (edit or create)
	 *
	 * check if exists editOnly or createOnly key and return it, otherwise return merged common and dedicated fieldsets
	 *
	 * @param string $type (edit or create)
	 * @return array
	 */
	public function getFormFieldsets(string $type)
	{
		if(($result = $this::$formFields[$type . 'Only']?? null) === null)
			$result = array_merge_recursive(
				$this::$formFields['common'] ?? [],
				$this::$formFields[$type] ?? [],
			);

		if($type == 'editor')
			$result = array_merge_recursive(
				$this::$formFields['edit'] ?? [],
				$result
			);

		return $result;
	}

	/**
	 * get store or update default route name
	 *
	 * @param string $type (create or edit)
	 * @return string => [entity].store or [entity].update
	 */
	public function getModelActionByForm(string $type)
	{
		$actions = [
			'create' => 'Store',
			'edit' => 'Update'
		];

		$getterMethod = 'get' . $actions[$type] . 'ModelAction';

		//return $this->getStoreModelAction()
		return $this->$getterMethod();
	}

	/**
	 * get form method for edit
	 *
	 * @return string
	 */
	public function getMethodEdit()
	{
		return 'PUT';
	}

	/**
	 * get form method for create
	 *
	 * @return string
	 */
	public function getMethodCreate()
	{
		return 'POST';
	}

	/**
	 * get form method by type
	 *
	 * @param string $type
	 * @return string
	 */
	public function getMethod(string $type)
	{
		$getMethodMethod = 'getMethod' . ucfirst($type);
		return $this->$getMethodMethod();
	}

	public function addFieldsFromDBByType(string $type)
	{
		foreach($this->getDBFieldsByType($type) as $field)
			$this->form->addFormField(
				FormField::createFromArray($field)
			);
	}

	public function getAllFieldsetFields(array $fieldsetParameters) : array
	{
		if(isset($fieldsetParameters['fields']))
			return $fieldsetParameters['fields'];

		return $fieldsetParameters;
	}

	/**
	 * return fields array from fieldset parameters
	 *
	 * @param array $fieldsetParameters
	 * @return array
	 **/
	public function getFieldsetFields(array $fieldsetParameters) : array
	{
		$fieldsetParameters = $this->getAllFieldsetFields($fieldsetParameters);

		return $this->filterByRolesAndPermissions($fieldsetParameters);
	}

	public function getFieldsetFieldsets(array $fieldsetParameters) : array
	{
		if(isset($fieldsetParameters['fieldsets']))
			return $fieldsetParameters['fieldsets'];

		return [];
	}

	public function filterByRolesAndPermissions(array $fields) : array
	{
		$fields = $this->filterByRoles($fields);
		$fields = $this->filterByPermissions($fields);

		return $fields;
	}

	public function filterByRoles(array $fields) : array
	{
		if(($user = Auth::user())&&($user->hasRole('superadmin')))
			return $fields;

		foreach($fields as $key => $field)
		{
			if(! isset($field['roles']))
				continue;

			if(($user)&&($user->hasRole($field['roles'])))
				continue;

			unset($fields[$key]);
		}

		return $fields;
	}

	public function filterByPermissions(array $fields) : array
	{
		if(($user = Auth::user())&&($user->hasRole('superadmin')))
			return $fields;

		foreach($fields as $key => $field)
		{
			if(! isset($field['permissions']))
				continue;

			if(($user)&&($user->hasAnyPermission($field['permissions'])))
				continue;

			unset($fields[$key]);
		}

		return $fields;
	}

	/**
	 * return fieldset parameters excluding fields
	 *
	 * if fields array doesn't exist, parameters don't exist so return null
	 * else return parameters cleaned from fields
	 *
	 * @param array $fieldsetParameters
	 * @return array
	 **/
	public function getFieldsetParameters(array $fieldsetParameters)
	{
		if(! isset($fieldsetParameters['fields']))
			return [];
		
		unset($fieldsetParameters['fields']);

		return $fieldsetParameters;
	}

	public function userCanSeeFieldsetByRoles(array $fieldsetParameters) : bool
	{
		if(! ($fieldsetParameters['roles'] ?? false))
			return true;

		return Auth::user()->hasAnyRole($fieldsetParameters['roles']);
	}

	public function addFieldset($target, string $name, array $fieldsetParameters)
	{
		if(! $this->userCanSeeFieldsetByRoles($fieldsetParameters))
			return ;

		$fields = $this->getFieldsetFields($fieldsetParameters);
		$fieldsets = $this->getFieldsetFieldsets($fieldsetParameters);
		$parameters = $this->getFieldsetParameters($fieldsetParameters);

		$fieldset = $target->addFormFieldset($name, $parameters);

		foreach($fields as $fieldName => $field)
		{
			$parameters = $this->getFieldParameters($fieldName, $field);

			if(isset($parameters['relation']))
				$this->relatedFields[$parameters['relation']] = $parameters['name'];

			$formField = FormField::createFromArray($parameters);

			$target->addFormFieldToFieldset(
				$formField,
				$name
			);

			$formField->executeBeforeRenderingOperations();
		}

		foreach($fieldsets as $name => $fieldsetParameters)
			$this->addFieldset($fieldset, $name, $fieldsetParameters);
	}

	/**
	 * add fieldsets to form
	 *
	 * get fieldsets by edit or create type and add them to current form 
	 *
	 * @param string $type
	 **/
	public function addFieldsetsByTypeToForm(string $type)
	{
		if(! property_exists($this, 'formFields'))
			return $this->addFieldsFromDBByType($type);

		// $fieldsets = EditFieldsetsProvider::getFieldsetsArray();

		// dd($fieldsets);

		$fieldsets = $this->getFormFieldsets($type);

		foreach($fieldsets as $name => $fieldsetParameters)
			$this->addFieldset($this->form, $name, $fieldsetParameters);

		if(count($fieldsets) == 1)
			$this->form->flattenFieldsets();		
	}

	private function setFormCardByType(string $type)
	{
		$this->form->hasCard(true);

		if($type == 'edit')
			return $this->form->addCardClasses([config('crud.editFormCardClass', '')]);

		if($type == 'create')
			return $this->form->addCardClasses([config('crud.createFormCardClass', '')]);
	}

	public function setEditFormParameters()
	{
	}

	public function setCreateFormParameters()
	{
	}

	public function setFormParametersByType(string $type)
	{
		$methodName = "set" . ucfirst($type) . "FormParameters";

		return $this->$methodName();
	}

	private function setEditFormTitle()
	{
		$translationFileName = $this->getModelTranslationFileName();

		if(trans()->has($translationFileName . '.' . 'cardTitleEdit'))
			return $this->form->setTitle(
				__($translationFileName . '.' . 'cardTitleEdit', ['element' => $this->modelInstance->getName()])
			);

		return $this->form->setTitle(
			trans('crud::crud.cardTitleEdit', ['element' => $this->modelInstance->getName()])
		);
	}

	private function setCreateFormTitle()
	{
		$translationFileName = $this->getModelTranslationFileName();

		if(trans()->has($translationFileName . '.' . 'cardTitleCreate'))
			return $this->form->setTitle(
				__($translationFileName . '.' . 'cardTitleCreate', ['element' => __('relations.' . lcfirst(class_basename($this->modelInstance)))])
			);

		return $this->form->setTitle(
			trans('crud::crud.cardTitleCreate', ['element' => __('relations.' . lcfirst(class_basename($this->modelInstance)))])
		);
	}

	private function setFormTitleByType(string $type)
	{
		if($type == 'edit')
			return $this->setEditFormTitle();

		if($type == 'create')
			return $this->setCreateFormTitle();
	}

	public function hasFormDivider(string $formType)
	{
		$parameterName = $formType . 'FormDivider';

		return $this->$parameterName;
	}

	public function assignDatabaseFieldsParameters()
	{
		$databaseFields = [];

		$fields = DB::select('describe ' . $this->getModel()->getTable());

		foreach($fields as $dbField)
			$databaseFields[$dbField->Field] = new DatabaseField($dbField);

		$this->form->setAllDatabaseFields($databaseFields);
	}

	public function getCustomSubmitButtonText() : ? string
	{
		return null;
	}

	public function manageSubmitButton()
	{
		if($text = $this->getCustomEditSubmitButtonText())
			$this->form->setSubmitButtonText($text);
	}

	// public function instantiateForm(string $type)
	// {
	// 	$action = $this->getModelActionByForm($type);
	// 	$method = $this->getMethod($type);

	// 	$this->form = Form::createFromArray([
	// 		'action' => $action,
	// 		'method' => $method
	// 	]);

	// 	view()->share('form', $this->form);
	// }

	/**
	 * share form parameters for defualt view
	 *
	 * based on given type
	 * retrieve declared fields and put them in the form
	 * create action
	 * get method type
	 * share form to view
	 *
	 * @param string $type
	 */
	public function shareDefaultFormParameters(string $type)
	{
		// $action = $this->getModelActionByForm($type);
		// $method = $this->getMethod($type);

		// $this->form = Form::createFromArray([
		// 	'action' => $action,
		// 	'method' => $method
		// ]);

		$this->form->setDivider(
			$this->hasFormDivider($type)
		);

		$this->form->setModel($this->modelInstance);
		$this->assignDatabaseFieldsParameters();

		// if($type == 'create')
		$this->addFieldsetsByTypeToForm($type);

		$this->setFormCardByType($type);
		$this->setFormTitleByType($type);

		if(in_array('index', $this->allowedMethods)&&(! $this->avoidBackToList()))
			$this->form->setBackToListUrl(
				$this->getIndexUrl()
			);

		if($this->showFormIntroByType($type))
			$this->form->setIntro(
				$this->getFormIntroByType($type)
			);

		$this->manageSubmitButton();

		if($this->hasSaveAndNew())
			$this->form->addSaveAndNewButton();

		if($this->hasSaveAndRefresh())
			$this->form->addSaveAndRefreshButton();

		view()->share('form', $this->form);
	}

	public function hasSaveAndNew()
	{
		return $this->saveAndNew ?? config('crud.saveAndNew');
	}

	public function hasSaveAndRefresh()
	{
		return $this->saveAndRefresh ?? config('crud.saveAndRefresh');		
	}

	/**
	 * return validation array by given tipe
	 *
	 * get caller (getUpdateValidationArray or getStoreValidationArray), call it and retrieve validation array
	 * validate request by given array and return result
	 *
	 * @param Request $request, string $type
	 * @return array
	 **/
	public function validateRequestByType(Request $request, string $type)
	{
		$validationArrayGetter = 'get' . ucfirst(($type)) . 'ValidationArray';

		$validationArray = $this->$validationArrayGetter();

		return $request->validate($validationArray);
	}

	private function getFormFieldType($fieldContent)
	{
		if(count($fieldContent) == 1)
			return array_key_first($fieldContent);

		dd('fieldcontent multiplo, risolvere: ' . json_encode($fieldContent));

		// return $this->addValidationArrayMultipleRow($validationArray, $fieldContent, $fieldName);
	}

	public function transformParametersByFieldsAndType(array $parameters, string $type)
	{
		// $fieldsets = $this->getFormFieldsetsByType($type);

		$fieldsets = $this->fieldsetsProvider->getParametersArray();

		$confirmations = [];

		foreach($fieldsets as $fieldset)
		{
			$fields = $this->getFieldsetFields($fieldset);

			foreach($fields as $fieldName => $fieldContent)
			{
				$_parameters = $this->getFieldParameters($fieldName, $fieldContent);

				// if(isset($_parameters['rules']['']))
				// if($_parameters['name'] == 'password_confirmation')

				if(isset($_parameters['rules']['confirmed']))
					$confirmations[$_parameters['name'] . '_confirmation'] = true;

				$formField = FormField::createFromArray($_parameters);

				if(isset($parameters[$fieldName]))
					$parameters[$fieldName] = $formField->transformValueBeforeStore($parameters[$fieldName]);

				//RESET NULL JSON FORM FIELDS
				if($formField instanceof JsonFormField)
					if(! isset($parameters[$fieldName]))
						$parameters[$fieldName] = [];
			}
		}

		return array_diff_key($parameters, $confirmations);
	}

	public function showFormIntroByType(string $type)
	{
		if(isset($this->{'showFormIntro' . $type}))
			return $this->{'showFormIntro' . $type};

		return $this->showFormIntro;
	}

	public function getFormIntroByType(string $type)
	{
		$translationFileName = $this->getModelTranslationFileName();

		if($type == 'edit')
			return __($translationFileName . '.' . 'cardIntroEdit', ['element' => $this->modelInstance->getName()]);

		if($type == 'create')
			return
				__($translationFileName . '.' . 'cardIntroCreate :element', ['element' => __('relations.' . lcfirst(class_basename($this->modelInstance)))]);
	}
}