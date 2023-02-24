<?php

namespace IlBronza\CRUD\Traits;

use Illuminate\Support\Facades\DB;
use IlBronza\FormField\Facades\FormField;
use IlBronza\Form\Facades\Form;

trait CRUDDbFieldsTrait
{
	private function getFieldValidationPieces($field)
	{
		$validationPieces = [
			$this->getFieldValidationTypeFromDBField($field),
			($this->getFieldRequiredFromDBField($field))? 'required' : 'nullable',
		];

		$fieldType = $this->getFieldTypeFromDBField($field);

		if($max = $this->getFieldMaxFromDBField($field, $fieldType))
			$validationPieces[] = 'max:' . $max;

		//questa è di controllo per vedere cosa succede con i campi chiave, una volta capito è da rimuovere
		$this->getFieldKeyFromDBField($field);

		return [
			$fieldType => implode("|", $validationPieces)
		];
	}

	private function getValidationArrayByTypeFromDBByTypeByAllowed($type)
	{
		if(! $allowedFields = $this->getAllowedDBFieldsByType($type))
			return false;

		foreach($this->getDbFields() as $field)
		{
			if(! in_array($field->Field, $allowedFields))
				continue;

			$result[$field->Field] = $this->getFieldValidationPieces($field);
		}

		return $result;
	}

	private function getValidationArrayByTypeFromDBByTypeByGuarded($type)
	{
		if(! $guardedFields = $this->getGuardedDBFieldsByType($type))
			return false;

		$result = [];

		foreach($this->getDbFields() as $field)
		{
			if(in_array($field->Field, $guardedFields))
				continue;

			$result[$field->Field] = $this->getFieldValidationPieces($field);
		}

		return $result;
	}

	private function getValidationArrayByTypeFromDBByType(string $type) : array
	{
		if(! $validationArray = $this->getValidationArrayByTypeFromDBByTypeByGuarded($type))
			if(! $validationArray = $this->getValidationArrayByTypeFromDBByTypeByAllowed($type))
				throw new \Exception("non c'è validazione per " . $type);

		return [
			'default' => $validationArray
		];

	}

	private function getFieldMaxFromDBField(\stdClass $field, string $type)
	{
		if($type == 'boolean')
			return null;

		if($type == 'datetime')
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

			if(strpos($field->Type, 'int') !== false)
			{
				$digits = (int) ($pieces[1] ?? 11);

				if($digits == 11)
					return 2147483648;
			}
		}

		if($type == 'timestamp')
		{
			return '2038-01-19 03:14:07';
		}
		
		if($field->Type == 'bigint unsigned')
		{
			return 18446744073709551615;
		}

		if($type == 'integer')
		{
			if(strpos($field->Type, 'double') !== false)
			{
				$pieces = explode(",", $pieces[1]);
				$digits = (int) $pieces[0] - (int) $pieces[1];

				return pow(10, $digits) - 1;
			}
			elseif(strpos($field->Type, 'int') !== false)
			{
				$digits = (int) $pieces[1];

				if($digits == 11)
					return 2147483648;
			}
		}

		if($field->Type == 'json')
		{
			return ;
		}

		if(strpos($field->Type, 'varchar') !== null)
			return (int) filter_var($field->Type, FILTER_SANITIZE_NUMBER_INT) ?? null;

		throw new \Exception('gestire il max per il campo ' . $field->Type . ' in getFieldMaxFromDBField: ' . json_encode($field));
	}

	private function getFieldKeyFromDBField(\stdClass $field)
	{
		if($field->Key)
			throw new \Exception('gestire le key in getFieldKeyFromDBField: ' . json_encode($field));
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

		if(
			($field->Type == 'int(11)')
			||($field->Type == 'bigint unsigned')
			||($field->Type == 'int')
		)
			return 'number';

		if($field->Type == 'timestamp')
			return 'datetime';

		if($field->Type == 'json')
			return 'json';

		if(strpos($field->Type, 'enum') == 0)
			return 'select';

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

		if(strpos($field->Type, 'enum') == 0)
			return 'string';

		throw new \Exception(class_basename($this) . ': misisng ' . $field->Type . ' type declaration in getFieldValidationTypeFromDBField for ' . $field->Field);
	}

	private function getGuardedDBFieldsByType(string $type)
	{
		$guardedFieldsParameterName = 'guarded' . ucfirst($type) . 'DBFields';

		return $this->{$guardedFieldsParameterName} ?? false;
	}

	private function getAllowedDBFieldsByType(string $type)
	{
		$allowedFieldsParameterName = 'allowed' . ucfirst($type) . 'DBFields';

		return $this->{$allowedFieldsParameterName} ?? false;
	}

	private function getDbFields($model = null)
	{

		$tableName = (($model)? new $model : new ($this->getModelClass()))->getTable();

		return DB::select('describe ' . $tableName);
	}

	public function getDBFieldsByType(string $type, $model = null)
	{
		$guardedFields = $this->getGuardedDBFieldsByType($type);

		$result = [];

		foreach($this->getDbFields($model) as $field)
		{
			if(in_array($field->Field, $guardedFields))
				continue;

			$fieldParameters = [
				'name' => $field->Field,
				'label' => _('fields.' . $field->Field),
				'type' => ($type = $this->getFieldTypeFromDBField($field)),
				'required' => $this->getFieldRequiredFromDBField($field),
			];

			if($max = $this->getFieldMaxFromDBField($field, $type))
				$fieldParameters['max'] = $max;

			//questa è di controllo per vedere cosa succede con i campi chiave, una volta capito è da rimuovere
			// $this->getFieldKeyFromDBField($field);

			$result[] = $fieldParameters;
		}

		return $result;
	}
}