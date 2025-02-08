import ajax from './ajax';
import { showErrorToast, showSuccessToast } from './toasts';

document.addEventListener('DOMContentLoaded', function () {
    initProfileForm();
});

/**
 * Initializes profile form interactions.
 */
function initProfileForm() {
    const profileForm = document.getElementById('profileUpdateForm');
    const avatarUpload = document.getElementById('avatarUpload');

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
        const response = await fetch('/api/user/profile.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();
        if (result.success) {
            showSuccessToast('Profil zaktualizowany pomyślnie.');
        } else {
            showErrorToast(result.message || 'Błąd podczas aktualizacji profilu.');
        }
    } catch (error) {
        console.error('Błąd aktualizacji profilu:', error);
        showErrorToast('Nie udało się zaktualizować profilu.');
    }
}

/**
 * Handles avatar image upload and preview.
 */
async function handleAvatarUpload(event) {
    const fileInput = event.target;
    const file = fileInput.files[0];

    if (!validateImage(file)) return;

    const formData = new FormData();
    formData.append('avatar', file);

    try {
        const response = await fetch('/api/user/profile.php', {
            method: 'POST',
            body: formData,
        });
        const result = await response.json();
        if (result.success) {
            document.getElementById('profileAvatar').src = result.avatarUrl;
            showSuccessToast('Zdjęcie profilowe zaktualizowane pomyślnie.');
        } else {
            showErrorToast(result.message || 'Błąd podczas aktualizacji zdjęcia profilowego.');
        }
    } catch (error) {
        console.error('Błąd aktualizacji zdjęcia profilowego:', error);
        showErrorToast('Nie udało się zaktualizować zdjęcia profilowego.');
    }
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
