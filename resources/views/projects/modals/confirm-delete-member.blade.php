<div id="confirmDeleteMemberModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeConfirmDeleteMemberModal()"></div>
    <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Подтверждение удаления</h3>
            <button class="modal-close" onclick="closeConfirmDeleteMemberModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Вы уверены, что хотите удалить этого участника из проекта?</p>
            <p style="font-size: 13px; color: #64748B; margin-top: 8px;">Это действие нельзя отменить.</p>
        </div>
        <div class="modal-footer" style="padding: 16px 20px;">
            <button type="button" class="btn-secondary" onclick="closeConfirmDeleteMemberModal()">Отмена</button>
            <button type="button" class="btn-danger" id="confirmDeleteMemberBtn">Удалить</button>
        </div>
    </div>
</div>