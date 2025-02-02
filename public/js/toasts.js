// Function to create a toast
function createToast(type, message, autoDismiss = true, dismissTime = 3000) {
    const toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        console.error('Toast container not found');
        return;
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerText = message;

    toastContainer.appendChild(toast);

    if (autoDismiss) {
        setTimeout(() => {
            toast.remove();
        }, dismissTime);
    }
}

// Function to show success toast
function showSuccessToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('success', message, autoDismiss, dismissTime);
}

// Function to show warning toast
function showWarningToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('warning', message, autoDismiss, dismissTime);
}

// Function to show error toast
function showErrorToast(message, autoDismiss = true, dismissTime = 3000) {
    createToast('error', message, autoDismiss, dismissTime);
}

// Ensure toast container exists
document.addEventListener('DOMContentLoaded', () => {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        document.body.appendChild(toastContainer);
    }
});
