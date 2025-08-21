<?php

namespace IlBronza\CRUD\Interfaces;

interface CrudReorderableModelInterface
{
	public function getStoreMassReorderUrl(array $data = []) : string;
	public function setSortingIndex(int $value, bool $save = true) : void;
	public function getSortingIndexField() : string;
}