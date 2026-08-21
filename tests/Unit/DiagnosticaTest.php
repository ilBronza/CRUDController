<?php

namespace IlBronza\CRUD\Tests\Unit;

use IlBronza\CRUD\Tests\Fixtures\Models\Gadget;
use IlBronza\CRUD\Tests\TestCase;

class DiagnosticaTest extends TestCase
{
	public function test_schema_e_modello()
	{
		Gadget::create(['name' => 'Cacciavite']);

		$this->assertDatabaseHas('gadgets', ['name' => 'Cacciavite']);
	}

	// il database e' in memoria: ogni test riparte da zero senza RefreshDatabase
	public function test_il_database_riparte_pulito()
	{
		$this->assertDatabaseCount('gadgets', 0);
	}
}
