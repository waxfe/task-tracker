<header class="app-header">
    <div class="header-left">
    </div>

    <div class="header-center">
        <div class="search-bar">
            <i class="fas fa-search"></i>
            <input type="text" placeholder="Поиск задач, проектов..." id="globalSearch">
        </div>
    </div>

    <div class="header-right">
        <div class="ai-recommendation">
            <i class="fas fa-robot"></i>
            <span id="aiTip">У вас 3 задачи в работе</span>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="logout-form">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Выход</span>
            </button>
        </form>
    </div>
</header>