<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $projects = $user->projects;
        $tasks = $user->tasks;

        return view('dashboard', compact('user', 'projects', 'tasks'));
    }
}
