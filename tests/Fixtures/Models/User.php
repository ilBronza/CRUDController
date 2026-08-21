<?php

namespace IlBronza\CRUD\Tests\Fixtures\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

// Utente finto: serve solo a superare i middleware di permesso di CRUD,
// che chiamano hasRole() sull'utente autenticato.
class User extends Authenticatable
{
	use HasRoles;

	protected $table = 'users';

	protected $fillable = ['name', 'email', 'password'];
}
