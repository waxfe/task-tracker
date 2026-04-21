<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация | Task Tracker</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md w-96">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Регистрация</h1>
                <p class="text-gray-500 text-sm mt-1">Создайте новый аккаунт</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul class="list-disc list-inside">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="mb-4">
                    <label for="name" class="block text-gray-700 text-sm font-bold mb-2">Имя</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="email" class="block text-gray-700 text-sm font-bold mb-2">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-4">
                    <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Пароль</label>
                    <input type="password" name="password" id="password" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <div class="mb-6">
                    <label for="password_confirmation" class="block text-gray-700 text-sm font-bold mb-2">Подтверждение
                        пароля</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                </div>

                <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded-lg transition duration-200">
                    Зарегистрироваться
                </button>
            </form>

            <div class="text-center mt-6">
                <p class="text-gray-600 text-sm">
                    Уже есть аккаунт?
                    <a href="{{ route('login') }}" class="text-blue-500 hover:underline">Войти</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>