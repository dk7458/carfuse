document.addEventListener('DOMContentLoaded', function () {
    ensureToastContainer();
});

/**
 * Ensures the toast container exists on the page.
 */
function ensureToastContainer() {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
}

/**
 * Creates a toast notification.
 */
function createToast(type, message, autoDismiss = true, dismissTime = 3000) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <span>${message}</span>
        <button class="toast-close">&times;</button>
    `;

    toastContainer.appendChild(toast);

    // Add event listener for manual close
    toast.querySelector('.toast-close').addEventListener('click', function () {
        fadeOutToast(toast);
    });

    if (autoDismiss) {
        setTimeout(() => fadeOutToast(toast), dismissTime);
    }
}

/**
 * Smoothly fades out and removes a toast notification.
 */
function fadeOutToast(toast) {
    toast.style.opacity = '0';
    setTimeout(() => toast.remove(), 300);
}

/**
 * Displays a success toast.
 */
function showSuccessToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('success', message, autoDismiss, dismissTime);
}

/**
 * Displays a warning toast.
 */
function showWarningToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('warning', message, autoDismiss, dismissTime);
}

/**
 * Displays an error toast.
 */
function showErrorToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('error', message, autoDismiss, dismissTime);
}
