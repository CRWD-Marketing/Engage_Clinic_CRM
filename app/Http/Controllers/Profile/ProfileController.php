<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /**
     * Display the user's profile.
     */
    public function index()
    {
        return view('profile.index');
    }
}