<?php

namespace IlBronza\CRUD\Helpers\ModelManagers\Interfaces;

interface ClonableModelInterface
{
	/**
	 * return just the relations's names of the relations
	 * to be associated to the cloned model
	 * 
	 * @return array
	 * 
	 **/
	public function getClonableRelations() : array;


	/**
	 * return the list of the fields to be avoided
	 * when cloning a model
	 * 
	 * @return array
	 * 
	 **/
	public function getNotClonableFields() : array;
}