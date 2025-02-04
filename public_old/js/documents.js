// Function to handle document uploads securely
async function uploadDocument(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    try {
        const response = await fetch('/api/documents/upload', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Document uploaded successfully');
            previewDocument(data.documentUrl);
        } else {
            alert('Error uploading document: ' + data.message);
        }
    } catch (error) {
        console.error('Error uploading document:', error);
    }
}

// Function to enable previewing contracts before signing
function previewDocument(documentUrl) {
    const previewFrame = document.getElementById('document-preview');
    previewFrame.src = documentUrl;
    previewFrame.style.display = 'block';
}

// Function to integrate with SignatureService.php for digital signing
async function signDocument(documentId) {
    try {
        const response = await fetch('/api/documents/sign', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ documentId })
        });
        const data = await response.json();
        if (data.success) {
            alert('Document signed successfully');
        } else {
            alert('Error signing document: ' + data.message);
        }
    } catch (error) {
        console.error('Error signing document:', error);
    }
}

// Initialize document form
function initDocumentForm() {
    document.getElementById('document-upload-form').addEventListener('submit', uploadDocument);
    document.getElementById('sign-button').addEventListener('click', () => {
        const documentId = document.getElementById('document-id').value;
        signDocument(documentId);
    });
}

// Call initDocumentForm when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initDocumentForm);

// Function to handle document uploads securely
function handleDocumentUpload(file) {
    // Implement secure upload logic here
    // Example: Use FormData to send the file via AJAX
    const formData = new FormData();
    formData.append('document', file);

    fetch('/upload', {
        method: 'POST',
        body: formData,
        headers: {
            'Authorization': 'Bearer ' + getAuthToken() // Ensure secure upload
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('Upload successful:', data);
    })
    .catch(error => {
        console.error('Error uploading document:', error);
    });
}

// Function to enable previewing contracts before signing
function previewContract(file) {
    const reader = new FileReader();
    reader.onload = function(event) {
        const previewFrame = document.getElementById('contractPreview');
        previewFrame.src = event.target.result;
    };
    reader.readAsDataURL(file);
}

// Function to support digital signatures via API integration
function signDocument(documentId, signature) {
    fetch('/sign', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer ' + getAuthToken() // Ensure secure signing
        },
        body: JSON.stringify({
            documentId: documentId,
            signature: signature
        })
    })
    .then(response => response.json())
    .then(data => {
        console.log('Document signed successfully:', data);
    })
    .catch(error => {
        console.error('Error signing document:', error);
    });
}

// Helper function to get authentication token
function getAuthToken() {
    // Implement token retrieval logic here
    return 'your-auth-token';
}

// Example usage
document.getElementById('uploadButton').addEventListener('change', function(event) {
    const file = event.target.files[0];
    handleDocumentUpload(file);
    previewContract(file);
});

document.getElementById('signButton').addEventListener('click', function() {
    const documentId = 'example-document-id';
    const signature = 'example-signature';
    signDocument(documentId, signature);
});
