<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд | Task Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen">
        <nav class="bg-white shadow-md">
            <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
                <h1 class="text-xl font-bold text-gray-800">Task Tracker</h1>
                <div class="flex items-center gap-4">
                    <span>{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-500 hover:text-red-700">Выйти</button>
                    </form>
                </div>
            </div>
        </nav>

        <div class="max-w-7xl mx-auto px-4 py-8">
            <h2 class="text-2xl font-bold mb-6">Добро пожаловать, {{ $user->name }}!</h2>

            <div class="grid grid-cols-2 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-3">Мои проекты</h3>
                    <ul class="space-y-2">
                        @forelse($projects as $project)
                            <li>📁 {{ $project->name }}</li>
                        @empty
                            <li class="text-gray-500">Нет проектов</li>
                        @endforelse
                    </ul>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="font-bold text-lg mb-3">Мои задачи</h3>
                    <ul class="space-y-2">
                        @forelse($tasks as $task)
                            <li>✅ {{ $task->name }}</li>
                        @empty
                            <li class="text-gray-500">Нет задач</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>

</html>