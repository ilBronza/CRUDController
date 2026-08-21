<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Tests\TestCase;

class BootTest extends TestCase
{
	public function test_l_applicazione_si_avvia()
	{
		$this->assertTrue($this->app->isBooted());
	}
}
