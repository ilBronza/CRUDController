<?php

namespace IlBronza\CRUD\Traits\Model;

use Illuminate\Database\Eloquent\Model;
use ReflectionMethod;

trait CRUDExtendsCastsTrait
{
	/**
	 * Per istanza: durante parent::getCasts() non si fondono di nuovo i cast del trait
	 * (altrimenti verrebbero duplicati su più livelli che usano questo trait).
	 *
	 * @var int
	 */
	private $crudExtendsCastsTraitNativeDepth = 0;

	/**
	 * Unisce i cast nativi (parent::getCasts) con i getAdditionalCastsFromTrait()
	 * dichiarati su ogni livello della gerarchia (root → foglia), così ogni
	 * parent che usa il trait o ridefinisce il metodo contribuisce a cascata;
	 * i livelli più profondi vincono in caso di chiavi duplicate.
	 */
	public function getCasts() : array
	{
		if ($this->crudExtendsCastsTraitNativeDepth > 0)
			return parent::getCasts();

		$this->crudExtendsCastsTraitNativeDepth++;

		try
		{
			$baseCasts = parent::getCasts();

			return array_merge($baseCasts, $this->crudMergeAdditionalCastsFromTraitLineage());
		}
		finally
		{
			$this->crudExtendsCastsTraitNativeDepth--;
		}
	}

	/**
	 * Percorre root → foglia e unisce i contributi di getAdditionalCastsFromTrait.
	 *
	 * @return array<string, string>
	 */
	protected function crudMergeAdditionalCastsFromTraitLineage() : array
	{
		$merged = [];

		foreach ($this->crudExtendsCastsTraitModelLineageRootToLeaf() as $class)
		{
			if (! $this->crudExtendsCastsTraitShouldInvokeAdditionalCastsOn($class))
				continue;

			$method = new ReflectionMethod($class, 'getAdditionalCastsFromTrait');

			if ($method->isAbstract())
				continue;

			$method->setAccessible(true);

			$merged = array_merge($merged, $method->invoke($this));
		}

		return $merged;
	}

	/**
	 * @return list<class-string>
	 */
	protected function crudExtendsCastsTraitModelLineageRootToLeaf() : array
	{
		$parents = class_parents(static::class) ?: [];
		/** @var list<class-string> $ordered */
		$ordered = array_values(array_reverse($parents, true));
		$ordered[] = static::class;

		return $ordered;
	}

	protected function crudExtendsCastsTraitShouldInvokeAdditionalCastsOn(string $class) : bool
	{
		if ($class !== Model::class && ! is_subclass_of($class, Model::class))
			return false;

		if (! method_exists($class, 'getAdditionalCastsFromTrait'))
			return false;

		if (in_array(__TRAIT__, class_uses($class), true))
		{
			$method = new ReflectionMethod($class, 'getAdditionalCastsFromTrait');

			return ! $method->isAbstract();
		}

		$method = new ReflectionMethod($class, 'getAdditionalCastsFromTrait');

		return ! $method->isAbstract()
			&& $method->getDeclaringClass()->getName() === $class;
	}

	/**
	 * @return array<string, string>
	 */
	protected function getAdditionalCastsFromTrait() : array
	{
		return [];
	}
}
