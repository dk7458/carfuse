import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    initProfileForm();
});

/**
 * Initializes profile form interactions.
 */
function initProfileForm() {
    const profileForm = document.getElementById('profile-form');
    const avatarUpload = document.getElementById('avatar-upload');

    if (profileForm) profileForm.addEventListener('submit', updateProfile);
    if (avatarUpload) avatarUpload.addEventListener('change', handleAvatarUpload);
}

/**
 * Handles profile update submission.
 */
async function updateProfile(event) {
    event.preventDefault();
    const formData = new FormData(event.target);

    if (!validateProfileForm(formData)) return;

    try {
        const response = await ajax.post('/profile/update', formData);
        if (response.success) {
            showSuccessToast('Profil zaktualizowany pomyślnie.');
        } else {
            showErrorToast(response.message || 'Błąd podczas aktualizacji profilu.');
        }
    } catch (error) {
        console.error('Błąd aktualizacji profilu:', error);
        showErrorToast('Nie udało się zaktualizować profilu.');
    }
}

/**
 * Handles avatar image upload and preview.
 */
function handleAvatarUpload(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];

    if (!validateImage(file)) return;

    const reader = new FileReader();
    reader.onload = function (e) {
        document.getElementById('avatar-preview').src = e.target.result;
    };
    reader.readAsDataURL(file);
}

/**
 * Validates profile form inputs.
 */
function validateProfileForm(formData) {
    const name = formData.get('name').trim();
    const email = formData.get('email').trim();
    const password = formData.get('password')?.trim();

    if (!name) {
        showErrorToast('Imię i nazwisko jest wymagane.');
        return false;
    }

    if (!isValidEmail(email)) {
        showErrorToast('Wprowadź poprawny adres e-mail.');
        return false;
    }

    if (password && password.length < 6) {
        showErrorToast('Hasło musi mieć co najmniej 6 znaków.');
        return false;
    }

    return true;
}

/**
 * Validates uploaded image format.
 */
function validateImage(file) {
    if (!file) {
        showErrorToast('Wybierz obraz do przesłania.');
        return false;
    }

    const allowedTypes = ['image/png', 'image/jpeg', 'image/webp'];
    if (!allowedTypes.includes(file.type)) {
        showErrorToast('Nieobsługiwany format obrazu. Dozwolone: PNG, JPG, WEBP.');
        return false;
    }

    return true;
}

/**
 * Checks if an email is valid.
 */
function isValidEmail(email) {
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    return emailPattern.test(email);
}
