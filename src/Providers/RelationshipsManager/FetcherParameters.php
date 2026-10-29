<?php

namespace IlBronza\CRUD\Providers\RelationshipsManager;

use IlBronza\UikitTemplate\Fetcher;
use InvalidArgumentException;

/** Adapts a UikitTemplate fetcher to a standalone manager section. */
class FetcherParameters implements ManagerSection
{
	protected string $name;
	protected array $parameters;
	private ?Fetcher $fetcher = null;

	public function __construct(string $name, array $parameters)
	{
		if (! is_string($parameters['url'] ?? null) || trim($parameters['url']) === '')
			throw new InvalidArgumentException(sprintf('Fetcher section "%s" requires a non-empty URL.', $name));

		$this->name = $name;
		unset($parameters['type']);
		$parameters['title'] = $parameters['title'] ?? $name;
		$this->parameters = $parameters;
	}

	public function getName() : string
	{
		return $this->name;
	}

	public function getCardTitle()
	{
		return $this->getFetcher()->getTitle();
	}

	public function getToggleId()
	{
		return 'section-' . $this->getFetcher()->getId();
	}

	public function getFetcher() : Fetcher
	{
		return $this->fetcher ??= new Fetcher($this->parameters);
	}

	public function prepare() : void
	{
		$this->getFetcher();
	}

	public function render()
	{
		return $this->getFetcher()->render();
	}
}
