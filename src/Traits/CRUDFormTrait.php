<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Http\Request;
use ilBronza\CRUD\Traits\CRUDArrayFieldsTrait;
use ilBronza\CRUD\Traits\CRUDDbFieldsTrait;
use ilBronza\FormField\Facades\FormField;
use ilBronza\FormField\Fields\JsonFormField;
use ilBronza\Form\Facades\Form;


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
	use CRUDDbFieldsTrait;
	use CRUDArrayFieldsTrait;

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

	/**
	 * return fields array from fieldset parameters
	 *
	 * @param array $fieldsetParameters
	 * @return array
	 **/
	public function getFieldsetFields(array $fieldsetParameters)
	{
		if(isset($fieldsetParameters['fields']))
			return $fieldsetParameters['fields'];

		return $fieldsetParameters;
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

		$fieldsets = $this->getFormFieldsets($type);

		foreach($fieldsets as $name => $fieldsetParameters)
		{
			$fields = $this->getFieldsetFields($fieldsetParameters);
			$parameters = $this->getFieldsetParameters($fieldsetParameters);

			$this->form->addFormFieldset($name, $parameters);

			foreach($fields as $fieldName => $field)
			{
				$parameters = $this->getFieldParameters($fieldName, $field);

				if(isset($parameters['relation']))
					$this->relatedFields[$parameters['relation']] = $parameters['name'];

				$formField = FormField::createFromArray($parameters);

				$this->form->addFormFieldToFieldset(
					$formField,
					$name
				);

				if($parameters['type'] == 'json')
					foreach($parameters['fields'] as $fieldName => $field)
						$formField->addFormField(
							FormField::createFromArray(
								$this->getFieldParameters($fieldName, $field)
							)
						);

			}
		}

		if(count($fieldsets) == 1)
			$this->form->flattenFieldsets();		
	}

	private function setFormCardByType(string $type)
	{
		$this->form->hasCard(true);

		if($type == 'edit')
			return $this->form->addCardClasses(['uk-card-secondary']);

		if($type == 'create')
			return $this->form->addCardClasses(['uk-card-primary']);
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

	private function setFormTitleByType(string $type)
	{
		$translationFileName = $this->getModelTranslationFileName();

		if($type == 'edit')
			$this->form->setTitle(
				__($translationFileName . '.' . 'cardTitleEdit', ['element' => $this->modelInstance->getName()])
			);

		if($type == 'create')
			$this->form->setTitle(
				__($translationFileName . '.' . 'cardTitleCreate :element', ['element' => __('relations.' . class_basename(lcfirst($this->modelInstance)))])
			);
	}

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
		$action = $this->getModelActionByForm($type);
		$method = $this->getMethod($type);

		$this->form = Form::createFromArray([
			'action' => $action,
			'method' => $method
		]);

		$this->addFieldsetsByTypeToForm($type);

		$this->form->assignModel($this->modelInstance);

		$this->setFormCardByType($type);
		$this->setFormTitleByType($type);

		if(in_array('index', $this->allowedMethods))
			$this->form->setBackToListUrl(
				$this->getIndexUrl()
			);

		view()->share('form', $this->form);
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
	private function validateRequestByType(Request $request, string $type)
	{
		$validationArrayGetter = 'get' . ucfirst(($type)) . 'ValidationArray';

		$validationArray = $this->$validationArrayGetter();

		return $request->validate($validationArray);
	}

	private function getFormFieldType($fieldContent)
	{
		if(count($fieldContent) == 1)
			return array_key_first($fieldContent);

		mori('fieldcontent multiplo, risolvere: ' . json_encode($fieldContent));

		// return $this->addValidationArrayMultipleRow($validationArray, $fieldContent, $fieldName);
	}

	public function transformParametersByFieldsAndType(array $parameters, string $type)
	{
		$fieldsets = $this->getFormFieldsetsByType($type);

		foreach($fieldsets as $fieldset)
		{
			$fields = $this->getFieldsetFields($fieldset);

			foreach($fields as $fieldName => $fieldContent)
			{
				$_parameters = $this->getFieldParameters($fieldName, $fieldContent);
				$formField = FormField::createFromArray($_parameters);

				if(isset($parameters[$fieldName]))
					$parameters[$fieldName] = $formField->transformValueBeforeStore($parameters[$fieldName]);

				//RESET NULL JSON FORM FIELDS
				if($formField instanceof JsonFormField)
					if(! isset($parameters[$fieldName]))
						$parameters[$fieldName] = [];
			}
		}

		return $parameters;
	}
}