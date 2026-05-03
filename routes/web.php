<?php

use App\Http\Controllers\AiChatController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
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
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'delete'])->name('profile.delete');

    // Проект
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
    Route::delete('/projects/{project}', [ProjectController::class, 'delete'])->name('projects.delete');
    Route::post('/projects/{project}/leave', [ProjectController::class, 'leave'])->name('projects.leave');
    Route::get('/projects/{project}/users', [ProjectController::class, 'getUsers']);
    Route::post('/projects/{project}/ai-analyze', [ProjectController::class, 'aiAnalyze'])->name('projects.ai-analyze');

    // Участники проекта
    Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.add');
    Route::delete('/projects/{project}/members/{user}', [ProjectController::class, 'deleteMember'])->name('projects.members.delete');
    Route::patch('/projects/{project}/members/{user}/role', [ProjectController::class, 'changeRole'])->name('projects.members.role');

    // Задача
    Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::post('/tasks/{task}/ai-analyze', [TaskController::class, 'aiAnalyze'])->name('tasks.ai-analyze');

    // Комментарии
    Route::prefix('tasks/{task}/comments')->group(function () {
        Route::post('/', [CommentController::class, 'store'])->name('comments.store');
        Route::put('/{comment}', [CommentController::class, 'update'])->name('comments.update');
        Route::delete('/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    });

    // Чат с AI
    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat', [AiChatController::class, 'sendMessage'])->name('ai-chat.send');
    Route::delete('/ai-chat/history', [AiChatController::class, 'clearHistory'])->name('ai-chat.clear');

});
