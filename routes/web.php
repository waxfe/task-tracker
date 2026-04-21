<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// ====================== КОРНЕВОЙ МАРШРУТ
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return redirect()->route('login');
});

// ==================== ГОСТЕВЫЕ МАРШРУТЫ (НЕАВТОРИЗОВАННЫЕ ПОЛЬЗОВАТЕЛИ) ============================
Route::middleware('guest')->group(function () {
    //Регистрация
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    //Авторизация
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Восстановление пароля
    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('password.update');
});

// =================== ЗАЩИЩЕННЫЕ МАРШРУТЫ (АВТОРИЗОВАННЫЕ ПОЛЬЗОВАТЕЛИ) ===============================
Route::middleware('auth')->group(function () {
    // Выход
    Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

    // Дашборд
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Профиль
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'delete'])->name('profile.delete');
});
