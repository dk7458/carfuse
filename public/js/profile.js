// Function to handle profile updates
async function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    if (!validateProfileForm(formData)) {
        return;
    }

    try {
        const response = await fetch('/api/profile/update', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            alert('Profile updated successfully');
        } else {
            alert('Error updating profile: ' + data.message);
        }
    } catch (error) {
        console.error('Error updating profile:', error);
    }
}

// Function to allow avatar image uploads
function handleAvatarUpload(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('avatar-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
}

// Function to validate form inputs before saving
function validateProfileForm(formData) {
    const name = formData.get('name');
    const email = formData.get('email');
    const password = formData.get('password');

    if (!name || !email || (password && password.length < 6)) {
        alert('Please fill out all required fields and ensure password is at least 6 characters long.');
        return false;
    }
    return true;
}

// Initialize profile form
function initProfileForm() {
    document.getElementById('profile-form').addEventListener('submit', updateProfile);
    document.getElementById('avatar-upload').addEventListener('change', handleAvatarUpload);
}

// Call initProfileForm when the DOM is fully loaded
document.addEventListener('DOMContentLoaded', initProfileForm);
