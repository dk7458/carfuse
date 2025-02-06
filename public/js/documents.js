document.addEventListener('DOMContentLoaded', function () {
    initDocumentForm();
});

/**
 * Inicjalizuje obsługę formularzy dokumentów.
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
 * Obsługuje przesyłanie dokumentów.
 */
async function uploadDocument(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch('/api/documents/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'Authorization': 'Bearer ' + getAuthToken()
            }
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Dokument przesłany pomyślnie.');
            previewDocument(data.documentUrl);
        } else {
            showError('Błąd przesyłania dokumentu: ' + data.message);
        }
    } catch (error) {
        console.error('Błąd przesyłania dokumentu:', error);
        showError('Wystąpił problem podczas przesyłania dokumentu.');
    }
}

/**
 * Obsługuje wybór pliku i jego podgląd.
 */
function handleFileSelection(event) {
    const file = event.target.files[0];
    if (!file) return;

    previewContract(file);
}

/**
 * Wyświetla podgląd dokumentu przed podpisaniem.
 */
function previewDocument(documentUrl) {
    const previewFrame = document.getElementById('document-preview');
    if (previewFrame) {
        previewFrame.src = documentUrl;
        previewFrame.style.display = 'block';
    }
}

/**
 * Obsługuje kliknięcie przycisku podpisywania.
 */
function handleSignButtonClick() {
    const documentId = document.getElementById('document-id').value.trim();
    if (!documentId) {
        showError('Brak wybranego dokumentu do podpisania.');
        return;
    }

    signDocument(documentId);
}

/**
 * Wysyła żądanie podpisania dokumentu do API.
 */
async function signDocument(documentId) {
    try {
        const response = await fetch('/api/documents/sign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + getAuthToken()
            },
            body: JSON.stringify({ documentId })
        });
        const data = await response.json();
        
        if (data.success) {
            alert('Dokument został pomyślnie podpisany.');
        } else {
            showError('Błąd podpisywania dokumentu: ' + data.message);
        }
    } catch (error) {
        console.error('Błąd podpisywania dokumentu:', error);
        showError('Wystąpił problem podczas podpisywania dokumentu.');
    }
}

/**
 * Wyświetla podgląd dokumentu przed podpisaniem.
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

/**
 * Wyświetla komunikat o błędzie.
 */
function showError(message) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
        errorContainer.innerText = message;
        errorContainer.style.display = 'block';
    }
}

/**
 * Pobiera token autoryzacyjny użytkownika.
 */
function getAuthToken() {
    return localStorage.getItem('auth_token') || '';
}
