@extends('layouts.app')

@section('title', $project->name)

@section('content')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    window.projectId = {{ $project->project_id }};
    window.statsData = @json($stats);
    window.tasksData = @json($tasks);
    window.membersData = @json($members);
    window.isOwner = {{ $isOwner ? 'true' : 'false' }};
    window.currentUserId = {{ $currentUser->id }};
    window.lastAnalysis = @json($lastAnalysisOutput);
</script>
    <div class="project-page">
        {{-- Заголовок проекта --}}
        <div class="project-header">
{{-- стало --}}
<div class="project-title-edit-wrapper">
    <h1 class="project-title" id="projectTitleDisplay"
        @if($isOwner) onclick="editTitle()" title="Нажмите для редактирования" @endif>
        {{ $project->name }}
    </h1>
    @if($isOwner)
        <input type="text" id="projectTitleInput" class="project-title-input hidden"
            value="{{ $project->name }}" maxlength="255">
        <button id="saveTitleBtn" class="save-description-btn hidden" onclick="saveTitle()">
            Сохранить
        </button>
    @endif
</div>
            </div>

            {{-- Описание проекта с inline-редактированием --}}
            <div class="project-description-wrapper">
                <div id="projectDescriptionDisplay" class="project-description {{ $project->description ? '' : 'empty' }}"
                    @if($isOwner) onclick="editDescription()" @endif>
                    {{ $project->description ?? 'Добавить описание...' }}
                </div>

                @if($isOwner)
                    <textarea id="projectDescriptionInput" class="project-description-input hidden"
                        placeholder="Введите описание проекта...">{{ $project->description }}</textarea>
                    <button id="saveDescriptionBtn" class="save-description-btn hidden" onclick="saveDescription()">
                        Сохранить
                    </button>
                @endif
            </div>

            <hr class="section-divider">

            {{-- Состояние проекта --}}
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-value">{{ $stats['total'] }}</div>
                    <div class="stat-label">Всего задач</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $stats['todo'] }}</div>
                    <div class="stat-label">К выполнению</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $stats['in_progress'] }}</div>
                    <div class="stat-label">В работе</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">{{ $stats['done'] }}</div>
                    <div class="stat-label">Завершено</div>
                </div>
                <div class="stat-card overdue-card">
                    <div class="stat-value">{{ $stats['overdue'] }}</div>
                    <div class="stat-label">Просрочено</div>
                </div>
            </div>

            {{-- Графики --}}
            <div class="charts-grid">
                <div class="chart-card">
                    <h3>Статусы задач</h3>
                    <div class="donut-chart-container">
                        <canvas id="statusDonutChart" width="300" height="300"></canvas>
                    </div>
                    <div class="chart-legend">
                        <div><span class="legend-color todo"></span> К выполнению ({{ $stats['todo'] }})</div>
                        <div><span class="legend-color progress"></span> В работе ({{ $stats['in_progress'] }})</div>
                        <div><span class="legend-color done"></span> Завершено ({{ $stats['done'] }})</div>
                    </div>
                </div>
                <div class="chart-card">
                    <h3>Динамика выполнения задач</h3><canvas id="completionLineChart" width="400" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Нагрузка на участников</h3><canvas id="userLoadBarChart" width="400" height="200"></canvas>
                </div>
                <div class="chart-card">
                    <h3>Распределение по приоритетам</h3><canvas id="priorityBarChart" width="400" height="200"></canvas>
                </div>
            </div>

            <hr class="section-divider">

            {{-- AI-анализ --}}
            <div class="ai-analysis-section">
                <div class="ai-header">
                    <h2>🤖 AI-анализ проекта</h2>
                    <button class="refresh-ai-btn" onclick="refreshAIAnalysis()">
                        <i class="fas fa-sync-alt"></i> Обновить анализ
                    </button>
                </div>
                <div class="ai-subtitle">Анализ текущего состояния проекта и динамики задач</div>
                <div class="ai-insights-grid" id="aiAnalysisContainer">
                    <div class="loading-spinner">Загрузка рекомендаций...</div>
                </div>
            </div>

            <hr class="section-divider">

            {{-- Участники --}}
            <div class="members-section">
                <div class="members-header">
                    <h2>Участники</h2>
                    @if($isOwner)
                        <button class="add-member-btn" onclick="addMember()">
                            <i class="fas fa-plus"></i> Добавить
                        </button>
                    @endif
                </div>
                <div class="members-grid">
    @foreach($members as $member)
        <div class="member-card">
            <div class="member-avatar"><i class="fas fa-user-circle"></i></div>
            <div class="member-info">
                <div class="member-name">{{ $member->name }}</div>
                <select class="role-select" data-user-id="{{ $member->id }}" 
                    {{ !$isOwner || $member->id == $currentUser->id ? 'disabled' : '' }}
                    onchange="changeRole({{ $project->project_id }}, {{ $member->id }}, this)">
                    <option value="member" {{ $member->pivot->role_in_project == 'member' ? 'selected' : '' }}>Участник</option>
                    <option value="owner" {{ $member->pivot->role_in_project == 'owner' ? 'selected' : '' }}>Владелец</option>
                </select>
            </div>
            @if($isOwner && $member->pivot->role_in_project == 'member' && $member->id != $currentUser->id)
                <button class="remove-member-btn"
                    onclick="openConfirmDeleteMemberModal({{ $project->project_id }}, {{ $member->id }}, this)">
                    <i class="fas fa-times"></i>
                </button>
            @endif
        </div>
    @endforeach
</div>
            </div>
        </div>
    </div>
@endsection