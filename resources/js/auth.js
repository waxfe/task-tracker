// resources/js/auth.js

// Автоматическое исчезновение сообщений об ошибках
document.addEventListener('DOMContentLoaded', function () {
    const errorMessages = document.querySelectorAll('.error-message');
    const successMessages = document.querySelectorAll('.success-message');

    errorMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 500);
        }, 5000);
    });

    successMessages.forEach(message => {
        setTimeout(() => {
            message.style.transition = 'opacity 0.5s';
            message.style.opacity = '0';
            setTimeout(() => message.remove(), 5000);
        }, 3000);
    });
});