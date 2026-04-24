@extends('layouts.guest')

@section('title', 'Вход')
@section('card-class', 'login-card')

@section('content')
    <div class="logo">TaskAssist</div>
    <div class="logo-sub">Войдите, чтобы продолжить</div>

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <div class="form-group">
            <label class="form-label">Электронная почта</label>
            <input type="email" name="email" class="form-input" placeholder="Введите адрес электронной почты"
                value="{{ old('email') }}" required>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-input" placeholder="Введите пароль" required>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="checkbox-group">
            <input type="checkbox" name="remember" id="remember">
            <label for="remember">Запомнить меня</label>
        </div>

        <button type="submit" class="btn btn-primary">Войти</button>

        <div class="auth-links">
            <a href="{{ route('password.request') }}" class="auth-link">Забыли пароль?</a>
        </div>

        <div class="divider">
            <span>или</span>
        </div>

        <div class="auth-links">
            <span>Ещё нет аккаунта?</span>
            <a href="{{ route('register') }}" class="auth-link">Регистрация</a>
        </div>
    </form>
@endsection