<div id="addMemberModal" class="modal hidden">
    <div class="modal-overlay" onclick="closeAddMemberModal()"></div>
    <div class="modal-container">
        <div class="modal-header">
            <h3>Добавление участника</h3>
            <button class="modal-close" onclick="closeAddMemberModal()">&times;</button>
        </div>
        <form id="addMemberForm">
            @csrf
            <div class="form-group">
                <label for="memberEmail">Email пользователя <span class="required">*</span></label>
                <input type="email" id="memberEmail" name="email" class="form-input"
                    placeholder="Введите email пользователя" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeAddMemberModal()">Отмена</button>
                <button type="submit" class="btn-primary">Добавить</button>
            </div>
        </form>
    </div>
</div>