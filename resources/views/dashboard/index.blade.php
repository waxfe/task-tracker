@extends('layouts.app')

@section('title', 'Дашборд')

@section('content')
    <div class="page-header">
    <div class="view-toggle">
        <button class="view-btn {{ $viewMode == 'list' ? 'active' : '' }}" onclick="setView('list')">
            <i class="fas fa-list"></i> Список
        </button>
        <button class="view-btn {{ $viewMode == 'kanban' ? 'active' : '' }}" onclick="setView('kanban')">
            <i class="fas fa-columns"></i> Канбан
        </button>
    </div>
    
    <div class="project-info-header">
        <h2 class="current-project-name">{{ $selectedProject->name ?? 'Выберите проект' }}</h2>
        <button class="project-settings-btn" onclick="openProjectSettings({{ $selectedProject->project_id ?? 'null' }})" @if(!$selectedProject) disabled @endif>
            <i class="fas fa-cog"></i>
        </button>
    </div>

    <button class="btn-create-task" onclick="openCreateTaskModal()">
        <i class="fas fa-plus"></i> Создать задачу
    </button>
</div>

    @if($viewMode == 'list')
        {{-- РЕЖИМ СПИСКА --}}
        <div class="tasks-table-container">
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th>Задача</th>
                        <th>Исполнитель</th>
                        <th>Статус</th>
                        <th>Приоритет</th>
                        <th>Срок выполнения</th>
                        <th>Последнее обновление</th>
                        <th>Рекомендации от AI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                    <tr onclick="openTaskCard({{ $task->task_id }})" style="cursor: pointer;">
                        <td class="task-name">{{ $task->name }}</td>
                        <td>
                            @foreach($task->users as $user)
                                <span class="assignee">{{ $user->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            <span class="status-badge status-{{ $task->status }}">
                                @switch($task->status)
                                    @case('todo') К выполнению @break
                                    @case('in_progress') В работе @break
                                    @case('done') Завершено @break
                                @endswitch
                            </span>
                        </td>
                        <td>
                            <span class="priority-badge priority-{{ $task->priority }}">
                                @switch($task->priority)
                                    @case('low') Низкий @break
                                    @case('medium') Средний @break
                                    @case('high') Высокий @break
                                @endswitch
                            </span>
                        </td>
                        <td class="{{ $task->due_date && $task->due_date < now() ? 'overdue' : '' }}">
                            {{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}
                        </td>
                        <td>{{ $task->updated_at->format('d.m.Y') }}</td>
                        <td>
                            @if($task->aiInteractions->count() > 0)
                                <button class="ai-recommend-btn" onclick="getAiRecommendation({{ $task->task_id }}, event)">
                                    <i class="fas fa-robot"></i> Посмотреть
                                </button>
                            @else
                                <span class="no-ai">Нет</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-table">Нет задач в этом проекте</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
    {{-- РЕЖИМ КАНБАН --}}
    <div class="kanban-board">
        @php
            $statuses = [
                'todo' => ['title' => 'К выполнению', 'color' => '#FEF3C7'],
                'in_progress' => ['title' => 'В работе', 'color' => '#DBEAFE'],
                'done' => ['title' => 'Завершено', 'color' => '#D1FAE5'],
            ];
            $tasksByStatus = [
                'todo' => $tasks->where('status', 'todo'),
                'in_progress' => $tasks->where('status', 'in_progress'),
                'done' => $tasks->where('status', 'done'),
            ];
        @endphp

        @foreach($statuses as $statusKey => $statusInfo)
            <div class="kanban-column" data-status="{{ $statusKey }}">
                <div class="kanban-header">
                    <span>{{ $statusInfo['title'] }}</span>
                    <span class="kanban-count">{{ $tasksByStatus[$statusKey]->count() }}</span>
                </div>
                
                <div class="kanban-tasks" id="kanban-{{ $statusKey }}">
                    @foreach($tasksByStatus[$statusKey] as $task)
                        <div class="kanban-card" onclick="openTaskCard({{ $task->task_id }})">
                            <div class="kanban-card-header">
                                <span class="card-title">{{ $task->name }}</span>
                                @if($task->aiInteractions->count() > 0)
                                    <i class="fas fa-brain ai-brain-icon" title="Есть AI-рекомендации"></i>
                                @endif
                            </div>
                            <div class="card-footer">
                                <span class="priority-{{ $task->priority }}">{{ $task->priority }}</span>
                                <span class="due-date">{{ $task->due_date ? $task->due_date->format('d.m.Y') : '—' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <button class="kanban-add-task" onclick="openCreateTaskModalWithStatus('{{ $statusKey }}')">
                    <i class="fas fa-plus"></i> Добавить задачу
                </button>
            </div>
        @endforeach
    </div>
@endif
@endsection