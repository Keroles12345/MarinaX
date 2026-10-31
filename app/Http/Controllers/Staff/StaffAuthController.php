<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\departments;
use Illuminate\Http\Request;

class StaffAuthController extends Controller
{
    public function showLogin()
    {
  

        return view('staff.auth.login');
    }

}
