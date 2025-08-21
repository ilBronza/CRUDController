<?php

namespace IlBronza\CRUD\Traits\IlBronzaPackages;

trait CRUDDisplayHtmlClassesTrait
{
	public array $htmlClasses = [];

	public function getHtmlClasses() : array
	{
		return $this->htmlClasses;
	}

	public function replaceHtmlClass(string $oldClass, string $newClass) : self
	{
		$this->removeHtmlClass($oldClass);

		return $this->addHtmlClass($newClass);
	}

	public function getHtmlClassesString() : string
	{
		return implode(' ', $this->htmlClasses);
	}

	public function setHtmlClasses(array $htmlClasses) : self
	{
		$this->htmlClasses = $htmlClasses;

		return $this;
	}

	public function addHtmlClass(string $htmlClass) : self
	{
		$pieces = explode(' ', $htmlClass);

		foreach($pieces as $piece)
			if (! in_array($piece, $this->htmlClasses))
				$this->htmlClasses[] = $piece;

		return $this;
	}

	public function removeHtmlClass(string $htmlClass) : self
	{
		$pieces = explode(' ', $htmlClass);

		foreach($pieces as $piece)
			if (($key = array_search($piece, $this->htmlClasses)) !== false)
				unset($this->htmlClasses[$key]);

		return $this;
	}
}