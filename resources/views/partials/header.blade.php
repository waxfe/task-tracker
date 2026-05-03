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
            <i class="fas fa-chart-line"></i>
            <span id="userTasksCount">У вас {{ $activeTasksCount }} активных задач</span>
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