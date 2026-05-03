<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Профиль | TaskAssist</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/profile.css', 'resources/js/profile.js'])
</head>

<body>
    <div class="profile-page">
        <div class="profile-header">
            <a href="{{ route('dashboard', ['project_id' => session('current_project_id', 1)]) }}" class="back-link">
                <i class="fas fa-arrow-left"></i> На главную
            </a>
            <button class="logout-btn" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Выйти
            </button>
        </div>

        <div class="profile-container">
            <div class="profile-avatar">
                <i class="fas fa-user-circle"></i>
                <h1>{{ $user->name }}</h1>
            </div>

            <div class="profile-card">
                <h2><i class="fas fa-user-edit"></i> Редактирование профиля</h2>

                <form id="profileForm">
                    <div class="form-group">
                        <label>Имя</label>
                        <input type="text" id="name" name="name" value="{{ $user->name }}" required>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="email" name="email" value="{{ $user->email }}" required>
                    </div>

                    <div class="form-group">
                        <label>Текущий пароль</label>
                        <input type="password" id="current_password" name="current_password">
                        <small>Необходимо заполнить для смены пароля</small>
                    </div>

                    <div class="form-group">
                        <label>Новый пароль</label>
                        <input type="password" id="password" name="password">
                    </div>

                    <div class="form-group">
                        <label>Подтверждение пароля</label>
                        <input type="password" id="password_confirmation" name="password_confirmation">
                    </div>

                    <div class="form-group readonly">
                        <label>Дата регистрации</label>
                        <input type="text" value="{{ $registrationDate }}" readonly disabled>
                    </div>

                    <div class="stats-row">
                        <div class="stat-block">
                            <div class="stat-number">{{ $projectsCount }}</div>
                            <div class="stat-label">Проектов</div>
                        </div>
                        <div class="stat-block">
                            <div class="stat-number">{{ $tasksCount }}</div>
                            <div class="stat-label">Активных задач</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" id="cancelBtn" class="btn-secondary">Отмена</button>
                        <button type="submit" class="btn-primary">Сохранить изменения</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>