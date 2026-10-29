<?php

namespace IlBronza\CRUD\Tests\Unit;

use Exception;
use IlBronza\CRUD\Tests\TestCase;
use Illuminate\Support\Facades\Auth;

class HelpersTest extends TestCase
{
	public function test_throw_if_auth_id_is_one_throws_the_given_message() : void
	{
		Auth::shouldReceive('id')
			->once()
			->andReturn(1);

		$this->expectException(Exception::class);
		$this->expectExceptionMessage('messaggio diagnostico');

		throwIfAuthIdIsOne('messaggio diagnostico');
	}

	public function test_throw_if_auth_id_is_one_does_nothing_for_other_users() : void
	{
		Auth::shouldReceive('id')
			->once()
			->andReturn(2);

		throwIfAuthIdIsOne('messaggio diagnostico');

		$this->assertTrue(true);
	}
}
