<aside class="sidebar">
    {{-- Логотип --}}
    <div class="sidebar-logo">
        <span class="logo-text">TaskAssist</span>
    </div>

    {{-- Пользователь --}}
    <div class="sidebar-section">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <div class="user-name">{{ auth()->user()->name }}</div>
                <div class="user-role">Администратор</div>
            </div>
            <a href="{{ route('profile.edit') }}" class="settings-icon">
                <i class="fas fa-cog"></i>
            </a>
        </div>
    </div>

    {{-- Проекты --}}
    <div class="sidebar-section">
        <div class="section-header">
            <h3>Проекты</h3>
            <button class="btn-create" onclick="openCreateProjectModal()">
                <i class="fas fa-plus"></i> Создать
            </button>
        </div>
        <ul class="project-list">
            @forelse($projects as $project)
                <li class="project-item {{ $selectedProject?->project_id == $project->project_id ? 'active' : '' }}">
                    <a href="{{ route('dashboard', ['project_id' => $project->project_id]) }}">
                        <i class="fas fa-folder"></i>
                        <span>{{ $project->name }}</span>
                    </a>
                </li>
            @empty
                <li class="empty-message">Нет проектов</li>
            @endforelse
        </ul>
    </div>

    {{-- Участники --}}
    @if($selectedProject)
        <div class="sidebar-section">
            <div class="section-header">
                <h3>Участники</h3>
            </div>
            <ul class="members-list">
                @foreach($selectedProject->users as $member)
                    <li class="member-item">
                        <i class="fas fa-user-circle"></i>
                        <div class="member-info">
                            <span class="member-name">{{ $member->name }}</span>
                            <span class="member-role">
                                @if($member->pivot->role_in_project == 'owner')
                                    Владелец
                                @else
                                    Участник
                                @endif
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- AI-ассистент --}}
    <div class="sidebar-section">
        <div class="section-header">
            <h3>AI-ассистент</h3>
        </div>
        <button class="btn-ai" onclick="openAiChat()">
            <i class="fas fa-robot"></i>
            Спросить AI
        </button>
    </div>
</aside>