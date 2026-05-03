// Функция для добавления иконки показа/скрытия пароля
function addPasswordToggle(inputElement) {
    // Проверяем, не добавлена ли уже иконка
    if (inputElement.parentElement.classList.contains('password-wrapper')) {
        return;
    }

    // Создаем контейнер для относительного позиционирования
    const wrapper = document.createElement('div');
    wrapper.className = 'password-wrapper';
    wrapper.style.position = 'relative';
    wrapper.style.width = '100%';

    // Вставляем wrapper перед input
    inputElement.parentNode.insertBefore(wrapper, inputElement);
    wrapper.appendChild(inputElement);

    // Создаем иконку
    const toggleIcon = document.createElement('i');
    toggleIcon.className = 'fas fa-eye-slash password-toggle-icon';
    toggleIcon.style.position = 'absolute';
    toggleIcon.style.right = '12px';
    toggleIcon.style.top = '50%';
    toggleIcon.style.transform = 'translateY(-50%)';
    toggleIcon.style.cursor = 'pointer';
    toggleIcon.style.color = '#64748B';
    toggleIcon.style.zIndex = '1';
    toggleIcon.style.fontSize = '16px';
    toggleIcon.style.display = 'none';

    wrapper.appendChild(toggleIcon);

    // Функция для проверки и обновления видимости иконки
    function updateIconVisibility() {
        if (inputElement.value.length > 0) {
            toggleIcon.style.display = 'block';
        } else {
            toggleIcon.style.display = 'none';
            if (inputElement.type === 'text') {
                inputElement.type = 'password';
                toggleIcon.className = 'fas fa-eye-slash password-toggle-icon';
            }
        }
    }

    inputElement.style.paddingRight = '35px';

    // Обработчики событий для показа/скрытия иконки
    inputElement.addEventListener('input', updateIconVisibility);
    inputElement.addEventListener('focus', updateIconVisibility);
    inputElement.addEventListener('blur', updateIconVisibility);

    // Обработчик клика по иконке
    let isPasswordVisible = false;
    toggleIcon.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (inputElement.value.length === 0) return;

        isPasswordVisible = !isPasswordVisible;

        if (isPasswordVisible) {
            inputElement.type = 'text';
            toggleIcon.className = 'fas fa-eye password-toggle-icon';
        } else {
            inputElement.type = 'password';
            toggleIcon.className = 'fas fa-eye-slash password-toggle-icon';
        }
    });

    updateIconVisibility();

    return { wrapper, toggleIcon };
}

function disableNativePasswordIcons() {
    const style = document.createElement('style');
    style.textContent = `
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear,
        input[type="password"]::-webkit-contacts-auto-fill-button,
        input[type="password"]::-webkit-credentials-auto-fill-button {
            display: none !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
    `;
    document.head.appendChild(style);
}

// Инициализация всех полей пароля при загрузке страницы
document.addEventListener('DOMContentLoaded', function () {
    disableNativePasswordIcons();

    const passwordFields = [
        'current_password',
        'password',
        'password_confirmation'
    ];

    passwordFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (field) {
            if (field.parentElement.classList && field.parentElement.classList.contains('password-wrapper')) {
                const parent = field.parentElement;
                const grandParent = parent.parentElement;
                grandParent.insertBefore(field, parent);
                grandParent.removeChild(parent);
            }
            addPasswordToggle(field);
        }
    });
});

function translateErrorMessage(message) {
    const translations = {
        'The current password field is required when password is present.': 'Укажите текущий пароль для смены пароля',
        'The password field confirmation does not match.': 'Новый пароль и подтверждение не совпадают',
        'The password field must be at least 8 characters.': 'Пароль должен содержать минимум 8 символов',
        'The name field is required.': 'Имя обязательно для заполнения',
        'The email field is required.': 'Email обязателен для заполнения',
        'The email has already been taken.': 'Этот email уже используется',
        'The email must be a valid email address.': 'Введите корректный email адрес'
    };

    return translations[message] || message;
}

document.getElementById('profileForm')?.addEventListener('submit', function (e) {
    e.preventDefault();

    const submitBtn = document.querySelector('.btn-primary');
    const originalText = submitBtn?.textContent;
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Сохранение...';
    }

    const formData = {
        name: document.getElementById('name').value,
        email: document.getElementById('email').value,
        current_password: document.getElementById('current_password').value,
        password: document.getElementById('password').value,
        password_confirmation: document.getElementById('password_confirmation').value,
    };

    fetch('/profile', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        },
        body: JSON.stringify(formData)
    })
        .then(async res => {
            let data;
            const contentType = res.headers.get('content-type');

            if (contentType && contentType.includes('application/json')) {
                data = await res.json();
            } else {
                throw new Error('Сервер вернул неверный ответ');
            }

            if (!res.ok) {
                if (data.message) {
                    throw new Error(translateErrorMessage(data.message));
                }
                throw new Error('Ошибка при сохранении');
            }

            return data;
        })
        .then(data => {
            if (data.success) {
                showMessage(data.message, false);
                setTimeout(() => location.reload(), 1500);
            } else {
                showMessage(data.message || 'Произошла ошибка', true);
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            }
        })
        .catch(err => {
            console.error('Fetch error:', err);
            showMessage(err.message, true);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        });
});

document.getElementById('cancelBtn')?.addEventListener('click', () => {
    location.reload();
});

window.logout = function () {
    fetch('/logout', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    }).then(() => {
        window.location.href = '/';
    });
};

function showMessage(message, isError = false) {
    const oldToasts = document.querySelectorAll('.toast-notification');
    oldToasts.forEach(toast => toast.remove());

    const toast = document.createElement('div');
    toast.className = `toast-notification ${isError ? 'error' : 'success'}`;

    const icon = document.createElement('i');
    icon.className = isError ? 'fas fa-exclamation-circle' : 'fas fa-check-circle';

    const text = document.createElement('span');
    text.textContent = message;

    toast.appendChild(icon);
    toast.appendChild(text);
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}