@extends('layouts.guest')

@section('title', 'Регистрация')
@section('card-class', 'register-card')

@section('content')
    <div class="logo">TaskAssist</div>
    <div class="logo-sub">Регистрация</div>

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}">
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
            <label class="form-label">Имя пользователя</label>
            <input type="text" name="name" class="form-input" placeholder="Введите имя пользователя"
                value="{{ old('name') }}" required>
            @error('name')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" class="form-input" placeholder="Придумайте пароль" required>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Подтверждение пароля</label>
            <input type="password" name="password_confirmation" class="form-input" placeholder="Повторно введите пароль"
                required>
        </div>

        <button type="submit" class="btn btn-primary">Зарегистрироваться</button>

        <div class="auth-links">
            <span>Уже есть аккаунт?</span>
            <a href="{{ route('login') }}" class="auth-link">Войти</a>
        </div>
    </form>
@endsection