// ...existing code...

// Example AJAX request
fetch('/api/data')
    .then(response => response.json())
    .then(data => {
        // On success
        showSuccessToast('Data loaded successfully!');
        // ...existing code...
    })
    .catch(error => {
        // On error
        showErrorToast('Failed to load data.');
        // ...existing code...
    });
