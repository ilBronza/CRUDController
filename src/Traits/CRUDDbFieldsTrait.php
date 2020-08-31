<?php

namespace ilBronza\CRUD\Traits;

use Illuminate\Support\Facades\DB;
use ilBronza\FormField\Facades\FormField;
use ilBronza\Form\Facades\Form;

trait CRUDDbFieldsTrait
{
	use CRUDDbFieldsTrait;

	private function getValidationArrayByTypeFromDBByType(string $type)
	{
		$guardedFields = $this->getGuardedDBFieldsByType($type);

		$result = [];

		foreach($this->getDbFields() as $field)
		{
			if(in_array($field->Field, $guardedFields))
				continue;

			$validationPieces = [
				$this->getFieldValidationTypeFromDBField($field),
				($this->getFieldRequiredFromDBField($field))? 'required' : 'nullable',
			];

			if($max = $this->getFieldMaxFromDBField($field, $this->getFieldTypeFromDBField($field)))
				$validationPieces[] = 'max:' . $max;

			//questa è di controllo per vedere cosa succede con i campi chiave, una volta capito è da rimuovere
			$this->getFieldKeyFromDBField($field);

			$result[$field->Field] = $validationPieces;
		}

		return $result;
	}

	private function getFieldMaxFromDBField(\stdClass $field, string $type)
	{
		if($type == 'boolean')
			return null;

		$pieces = explode("(", $field->Type);

		if($type == 'text')
			return (int) $pieces[1];

		if($type == 'number')
		{
			if(strpos($field->Type, 'double') !== false)
			{
				$pieces = explode(",", $pieces[1]);
				$digits = (int) $pieces[0] - (int) $pieces[1];

				return pow(10, $digits) - 1;
			}

		}

		throw new \Exception('gestire il max per il campo ' . $type . ' in getFieldMaxFromDBField: ' . json_encode($field));
	}

	private function getFieldKeyFromDBField(\stdClass $field)
	{
		if($field->Key)
			throw new \Exception('gestire le key in getFieldKeyFromDBField');
	}

	private function getFieldRequiredFromDBField(\stdClass $field)
	{
		return ! $this->getFieldNullableFromDBField($field);
	}

	private function getFieldNullableFromDBField(\stdClass $field)
	{
		return ($field->Null == 'YES');
	}

	private function getFieldTypeFromDBField(\stdClass $field)
	{
		if(strpos($field->Type, 'varchar') !== false)
		{
			$pieces = explode("(", $field->Type);

			if((int) $pieces[1] > 255)
				return 'textarea';

			return 'text';
		}

		if(strpos($field->Type, 'double') !== false)
			return 'number';

		if($field->Type == 'tinyint(1)')
			return 'boolean';

		throw new \Exception(class_basename($this) . ': misisng ' . $field->Type . ' type declaration in getFieldTypeFromDBField for ' . $field->Field);
	}

	private function getFieldValidationTypeFromDBField(\stdClass $field)
	{
		if(strpos($field->Type, 'varchar') !== false)
			return 'string';

		if($field->Type == 'tinyint(1)')
			return 'boolean';

		if($field->Type == 'double(8,2)')
			return 'numeric';

		throw new \Exception(class_basename($this) . ': misisng ' . $field->Type . ' type declaration in getFieldValidationTypeFromDBField for ' . $field->Field);
	}

	private function getGuardedDBFieldsByType(string $type)
	{
		$guardedFieldsParameterName = 'guarded' . ucfirst($type) . 'DBFields';
		
		if(empty($this->$guardedFieldsParameterName))
			throw new \Exception(class_basename($this) . ': declare $formFields parameters or $' . $guardedFieldsParameterName . ' if you want to get automatic fields retrieving from db');

		return $this->$guardedFieldsParameterName;
	}

	private function getDbFields()
	{
		$tableName = (new $this->modelClass)->getTable();

		return DB::select('describe ' . $tableName);
	}

	public function getDBFieldsByType(string $type)
	{
		$guardedFields = $this->getGuardedDBFieldsByType($type);

		$result = [];

		foreach($this->getDbFields() as $field)
		{
			if(in_array($field->Field, $guardedFields))
				continue;

			$fieldParameters = [
				'name' => $field->Field,
				'label' => _('formfields.field_' . $field->Field),
				'type' => ($type = $this->getFieldTypeFromDBField($field)),
				'required' => $this->getFieldRequiredFromDBField($field),
			];

			if($max = $this->getFieldMaxFromDBField($field, $type))
				$fieldParameters['max'] = $max;

			//questa è di controllo per vedere cosa succede con i campi chiave, una volta capito è da rimuovere
			$this->getFieldKeyFromDBField($field);

			$result[] = $fieldParameters;
		}

		return $result;
	}
}