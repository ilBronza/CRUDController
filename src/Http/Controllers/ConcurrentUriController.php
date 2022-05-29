<?php

namespace IlBronza\CRUD\Http\Controllers;


use Auth;
use IlBronza\CRUD\Providers\ConcurrentUriChecker;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ConcurrentUriController extends Controller
{
	public function __construct(protected ConcurrentUriChecker $concurrentUriChecker)
	{
	}

	public function tick(Request $request)
	{
		return $this->concurrentUriChecker->managePrevalentUserData(
			$request->input('url'),
			$request->input('pageKey')
		);
	}

	public function leavePage(Request $request)
	{
		return $this->concurrentUriChecker->managePageLeaveByData(
			$request->input('url'),
			$request->input('pageKey')
		);
	}

	// public function check(Request $request)
	// {
	// 	return json_encode(
	// 			$this->concurrentUriChecker->check(
	// 			$request->input('url'),
	// 			$request->input('pageKey')
	// 		)
	// 	);
	// }
}

