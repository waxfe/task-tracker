<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;


class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        $projectsCount = $user->projects()->count();
        $tasksCount = $user->tasks()->count();
        $registrationDate = $user->registration_date?->format('d.m.Y');

        return view('profile.show', compact('user', 'projectsCount', 'tasksCount', 'registrationDate'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'current_password' => 'required_with:password',
            'password' => 'nullable|min:8|confirmed'
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Неверный текущий пароль',
                ], 422);
            }
            $user->password = Hash::make($request->password);
            $user->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Профиль обновлен'
        ]);
    }
}
