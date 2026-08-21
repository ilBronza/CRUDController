<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

// Stub del controller dell'applicazione ospite.
// IlBronza\CRUD\CRUD lo estende, quindi senza questa classe il pacchetto
// non e' istanziabile fuori da un'app Laravel completa.
class Controller extends BaseController
{
	use AuthorizesRequests;
	use DispatchesJobs;
	use ValidatesRequests;
}
