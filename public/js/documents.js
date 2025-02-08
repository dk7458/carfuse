import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    initDocumentForm();
});

/**
 * Initializes document-related form actions.
 */
function initDocumentForm() {
    const uploadForm = document.getElementById('document-upload-form');
    const signButton = document.getElementById('sign-button');
    const uploadInput = document.getElementById('uploadButton');

    if (uploadForm) uploadForm.addEventListener('submit', uploadDocument);
    if (signButton) signButton.addEventListener('click', handleSignButtonClick);
    if (uploadInput) uploadInput.addEventListener('change', handleFileSelection);
}

/**
 * Handles document upload process.
 */
async function uploadDocument(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    if (!validateFileUpload(formData)) {
        return;
    }

    try {
        const response = await fetch('/api/user/documents.php?action=upload', {
            method: 'POST',
            body: formData
        }).then(res => res.json());

        if (response.success) {
            showSuccessToast('Dokument przesłany pomyślnie.');
            previewDocument(response.documentUrl);
        } else {
            showErrorToast(response.message || 'Nie udało się przesłać dokumentu.');
        }
    } catch (error) {
        console.error('Błąd przesyłania dokumentu:', error);
        showErrorToast('Wystąpił problem podczas przesyłania dokumentu.');
    }
}

/**
 * Validates file upload before sending.
 */
function validateFileUpload(formData) {
    const file = formData.get('document');
    if (!file) {
        showErrorToast('Wybierz dokument do przesłania.');
        return false;
    }

    const allowedTypes = ['application/pdf', 'image/png', 'image/jpeg'];
    if (!allowedTypes.includes(file.type)) {
        showErrorToast('Nieobsługiwany format pliku. Dozwolone: PDF, PNG, JPG.');
        return false;
    }

    return true;
}

/**
 * Handles file selection and previews before upload.
 */
function handleFileSelection(event) {
    const file = event.target.files[0];
    if (!file) return;

    previewContract(file);
}

/**
 * Previews document before signing.
 */
function previewDocument(documentUrl) {
    const previewFrame = document.getElementById('document-preview');
    if (previewFrame) {
        previewFrame.src = documentUrl;
        previewFrame.style.display = 'block';
    }
}

/**
 * Handles document signing process.
 */
function handleSignButtonClick() {
    const documentId = document.getElementById('document-id').value.trim();
    if (!documentId) {
        showErrorToast('Wybierz dokument do podpisania.');
        return;
    }

    signDocument(documentId);
}

/**
 * Sends request to sign a document.
 */
async function signDocument(documentId) {
    try {
        const response = await fetch('/api/user/documents.php?action=sign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ documentId })
        }).then(res => res.json());

        if (response.success) {
            showSuccessToast('Dokument został pomyślnie podpisany.');
        } else {
            showErrorToast(response.message || 'Nie udało się podpisać dokumentu.');
        }
    } catch (error) {
        console.error('Błąd podpisywania dokumentu:', error);
        showErrorToast('Wystąpił problem podczas podpisywania dokumentu.');
    }
}

/**
 * Previews document for user review before submission.
 */
function previewContract(file) {
    const reader = new FileReader();
    reader.onload = function (event) {
        const previewFrame = document.getElementById('contractPreview');
        if (previewFrame) {
            previewFrame.src = event.target.result;
        }
    };
    reader.readAsDataURL(file);
}
