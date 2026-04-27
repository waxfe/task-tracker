<div id="confirmLeaveProjectModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeConfirmLeaveProjectModal()"></div>
    <div class="modal-container" style="max-width: 400px;">
        <div class="modal-header">
            <h3>Подтверждение выхода</h3>
            <button class="modal-close" onclick="closeConfirmLeaveProjectModal()">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px;">
            <p>Вы уверены, что хотите выйти из этого проекта?</p>
            <p style="font-size: 13px; color: #64748B; margin-top: 8px;">Вы потеряете доступ к задачам проекта.</p>
        </div>
        <div class="modal-footer" style="padding: 16px 20px;">
            <button type="button" class="btn-secondary" onclick="closeConfirmLeaveProjectModal()">Отмена</button>
            <button type="button" class="btn-primary" id="confirmLeaveProjectBtn">Выйти</button>
        </div>
    </div>
</div>