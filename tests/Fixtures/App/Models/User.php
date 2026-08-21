<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

// Stub dell'utente dell'applicazione ospite.
// IlBronza\AccountManager\Models\User lo estende, e i trait di CRUD
// type-hintano quella classe: senza questo stub le route protette non partono.
class User extends Authenticatable
{
	use HasRoles;

	protected $table = 'users';

	protected $guarded = [];
}
