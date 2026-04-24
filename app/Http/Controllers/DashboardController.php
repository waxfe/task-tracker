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

        $selectedProjectId = request()->input('project_id', $projects->first()?->project_id);
        $selectedProject = $projects->find($selectedProjectId);

        $tasks = $selectedProject ? $selectedProject->tasks()->with('users', 'aiInteractions')->get() : collect();

        $viewMode = request()->input('view', 'list');

        return view('dashboard.index', compact('user', 'projects', 'selectedProject', 'tasks', 'viewMode'));
    }
}
