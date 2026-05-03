@extends('layouts.guest')

@section('title', 'Сброс пароля')
@section('card-class', 'reset-card')

@section('content')
    <div class="logo">TaskAssist</div>
    <div class="logo-sub">Создание нового пароля</div>

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="form-group">
            <label class="form-label">Новый пароль</label>
            <input type="password" name="password" class="form-input" placeholder="Придумайте новый пароль" required>
            @error('password')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">Подтверждение пароля</label>
            <input type="password" name="password_confirmation" class="form-input"
                placeholder="Повторно введите новый пароль" required>
        </div>

        <button type="submit" class="btn btn-primary">Сбросить пароль</button>

        <div class="auth-links">
            <a href="{{ route('login') }}" class="auth-link">Вернуться ко входу</a>
        </div>
    </form>
@endsection