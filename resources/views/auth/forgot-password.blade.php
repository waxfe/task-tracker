@extends('layouts.guest')

@section('title', 'Восстановление пароля')
@section('card-class', 'forgot-card')

@section('content')
    <div class="logo">TaskAssist</div>
    <div class="logo-sub">Восстановление пароля</div>

    <p class="forgot-description">
        Введите ваш email, и мы отправим ссылку для сброса пароля
    </p>

    @if (session('status'))
        <div class="success-message">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="error-message">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="form-group">
            <label class="form-label">Электронная почта</label>
            <input type="email" name="email" class="form-input" placeholder="Введите адрес электронной почты"
                value="{{ old('email') }}" required>
            @error('email')
                <div class="field-error">{{ $message }}</div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">Отправить ссылку</button>

        <div class="auth-links">
            <a href="{{ route('login') }}" class="auth-link">Вернуться ко входу</a>
        </div>
    </form>
@endsection