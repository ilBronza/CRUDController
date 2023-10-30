<?php

namespace IlBronza\CRUD\Http\Controllers\Utilities\Media;

use App\Http\Controllers\Controller;

class MediaIntegrityCheckerController extends Controller
{
	public $quantity = 1000;

	public function checkRandom()
	{
		$media = Meia::inRandomOrder()->take(
			$this->getMediaQuantity()
		)->get();


		mori($media);
	}
}

