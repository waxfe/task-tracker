<div id="createProjectModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeCreateProjectModal()"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>Создание проекта</h3>
            <button class="modal-close" onclick="closeCreateProjectModal()">&times;</button>
        </div>
        <form id="createProjectForm" method="POST" action="{{ route('projects.store') }}">
            @csrf
            <div class="form-group">
                <label for="projectName">Название проекта <span class="required">*</span></label>
                <input type="text" id="projectName" name="name" class="form-input"
                    placeholder="Введите название проекта" required>
            </div>
            <div class="form-group">
                <label for="projectDescription">Описание</label>
                <textarea id="projectDescription" name="description" class="form-input" rows="3"
                    placeholder="Введите описание проекта (необязательно)"></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeCreateProjectModal()">Отмена</button>
                <button type="submit" class="btn-primary">Создать проект</button>
            </div>
        </form>
    </div>
</div>

<script>
    function closeCreateProjectModal() {
        document.getElementById('createProjectModal').classList.add('hidden');
        document.getElementById('createProjectForm').reset();
    }
</script>