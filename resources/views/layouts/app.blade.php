<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TaskAssist - @yield('title')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/dashboard.js',
        'resources/css/project.css',
        'resources/js/project.js',
        'resources/css/task.css',
        'resources/js/task.js'
    ])
</head>

<body>
    <div class="app-container">
        @include('partials.sidebar')

        <div class="main-content">
            @include('partials.header')
            <main class="content-wrapper">
                @yield('content')
            </main>
        </div>
    </div>
    @include('projects.modals.create-project')
    @include('projects.modals.add-member')
    @include('projects.modals.confirm-delete-member')
    @include('projects.modals.confirm-delete-project')
    @include('projects.modals.confirm-leave-project')
    @include('tasks.task-modal')
    @include('tasks.create-task-modal')
</body>

</html>