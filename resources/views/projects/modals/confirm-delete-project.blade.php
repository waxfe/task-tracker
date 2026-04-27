<div id="confirmDeleteProjectModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeConfirmDeleteProjectModal()"></div>
    <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Подтверждение удаления</h3>
            <button class="modal-close" onclick="closeConfirmDeleteProjectModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Вы уверены, что хотите удалить этот проект?</p>
            <p style="font-size: 13px; color: #64748B; margin-top: 8px;">Это действие нельзя отменить. Все задачи и
                данные будут удалены.</p>
        </div>
        <div class="modal-footer" style="padding: 16px 20px;">
            <button type="button" class="btn-secondary" onclick="closeConfirmDeleteProjectModal()">Отмена</button>
            <button type="button" class="btn-danger" id="confirmDeleteProjectBtn">Удалить</button>
        </div>
    </div>
</div>