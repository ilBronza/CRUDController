<?php

namespace IlBronza\CRUD\Helpers\ModelManagers;

use IlBronza\Form\FormFieldset;
use IlBronza\Form\Helpers\FieldsetsProvider\FieldsetParametersFile;
use IlBronza\FormField\FormField;
use IlBronza\FormField\Helpers\FormFieldsProvider\FormFieldsProvider;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Collection;

use function array_merge;
use function collect;
use function dd;

class CrudModelBulkEditor extends CrudModelEditor
{
	public array $targetKeys;

	public function createNullingField(FormField $field) : FormField
	{
		$name = 'bulk_empty_value_' . $field->getName();

		return FormFieldsProvider::createByNameParameters(
			$name,
			[
				'label' => trans('crud::fields.emptyValueLabel', [
					'field' => $field->getLabel()
				]),
				'type' => 'boolean',
				'value' => false,
				'rules' => 'boolean|required'
			]
		);
	}

	protected function _addNullingFunctionFieldsToFieldset(Collection $fields) : Collection
	{
		$result = collect();

		foreach($fields as $field)
		{
			$result->push($field);

			$nullingField = $this->createNullingField($field);

			$result->push($nullingField);
		}

		return $result;
	}

	public function addNullingFunctionFieldsToFieldset(FormFieldset $fieldset)
	{
		$fieldset->setFields(
			$this->_addNullingFunctionFieldsToFieldset(
				$fieldset->getFields()
			)
		);

		foreach($fieldset->getFieldsets() as $fieldset)
			$this->addNullingFunctionFieldsToFieldset($fieldset);
	}

	public function addNullingFunctionFields()
	{
		foreach($this->getForm()->getFieldsets() as $fieldset)
			$this->addNullingFunctionFieldsToFieldset($fieldset);

		$this->getForm()->fields = $this->_addNullingFunctionFieldsToFieldset(
				$this->getForm()->getFields()
		);
	}

	public function setTargetKeys(array $keys) : void
	{
		$this->targetKeys = $keys;

		$fieldBaseParameters = [
					'type' => 'text',
					'value' => true,
					'rules' => 'boolean|required'
			];

		$fieldset = FormFieldset::createByNameAndParameters( 'hiddenIds',  []);

		$fieldset->addClass('uk-hidden');

		foreach($this->targetKeys as $index => $key)
		{
			$fieldBaseParameters['name'] = 'ids[' . $index . ']';
			$fieldBaseParameters['value'] = $key;
			$fieldBaseParameters['translatedName'] = $key;

			$parameters = array_merge(
				$fieldset->getFieldsDefaultParameters(),
				$fieldBaseParameters
			);

			$fieldset->addFormField(
				FormFieldsProvider::createByNameParameters(
					$key,
					$parameters
				)
			);
		}

		$this->getForm()->addFieldset($fieldset);
	}

	static function buildBulkForm(
		Model $model,
		array $keys,
		FieldsetParametersFile $parametersFile,
		string $action,
		array $formOptions = []
	) : static
	{
		$helper = parent::buildForm($model, $parametersFile, $action, $formOptions);

		$helper->addNullingFunctionFields();

		$helper->setTargetKeys($keys);

		$helper->getForm()->setTitle(
			trans('crud::crud.bulkEditTitle')
		);

		return $helper;
	}

}