<?php

namespace IlBronza\CRUD\Traits;

trait CRUDArrayFieldsTrait
{

	/**
	 * get field parameters definition by $formFields parameter and call methods for array creation
	 *
	 * @param string $fieldName, array $parametersString
	 * @return array
	 */
	private function getFieldParameters(string $fieldName, array $parametersString)
	{
		if(count($parametersString) == 1)
			return $this->getFieldParametersSingleRow($fieldName, $parametersString);

		return $this->getFieldParametersKeyValueRow($fieldName, $parametersString);
	}


	/**
	 * check if field is required in given rules array
	 *
	 * @param array $rules
	 * @return boolean
	 */
	private function checkIfFieldIsRequired(array $rules)
	{
		return in_array('required', $rules);
	}

	/**
	 *
	 **/
	private function buildNameAndLabelArray(string $name)
	{
		return [
				'name' => $name,
				'label' => __('fields.' . $name),
			];
	}

	/**
	 * build an array with a key per each rule
	 *
	 * @param array $parameters, array $rules
	 * @return array
	 */
	private function buildParametersRules($parameters, $rules)
	{
		if(! isset($parameters['required']))
			$parameters['required'] = $this->checkIfFieldIsRequired($rules);

		$parameters['rules'] = [];

		foreach($rules as $key => $rule)
			if(strpos($rule, ":"))
			{
				$_rule = explode(":", $rule);

				$parameters['rules'][$_rule[0]] = $_rule[1];

				if($_rule[0] == 'max')
					$parameters['max'] = $_rule[1];
			}
			else
				$parameters['rules'][$rule] = true;

		return $parameters;		
	}

	/**
	 * crete parameters array to create a FromField instance from single row description
	 *
	 * @param string name, array $parametersString
	 * @return array
	 */
	private function getFieldParametersSingleRow(string $name, array $parametersString)
	{
		$type = array_key_first($parametersString);

		$parameters = $this->buildNameAndLabelArray($name);
		$parameters['type'] = $type;

		$rules = explode("|", $parametersString[$type]);

		return $this->buildParametersRules($parameters, $rules);
	}

	/**
	 * crete parameters array to create a FromField instance from multiple row description
	 *
	 * @param string name, array $parametersString
	 * @return array
	 */
	private function getFieldParametersKeyValueRow(string $name, array $parametersString)
	{
		if(! isset($parametersString['type']))
			throw new \Exception('Missing "type" array element in field ' . $name);

		if(! isset($parametersString['rules']))
			throw new \Exception('Missing "rules" array element in field ' . $name);

		if(($parametersString['type'] == 'select')&&(! isset($parametersString['relation'])))
			throw new \Exception('Missing "relation" array element in field ' . $name . ', necessary to retrieve select elements');

		$parameters = array_merge(
			$parametersString, 
			$this->buildNameAndLabelArray($name)
		);

		$rules = $parametersString['rules'];

		if(is_string($rules))
			$rules = explode("|", $parametersString['rules']);

		return $this->buildParametersRules($parameters, $rules);
	}

}