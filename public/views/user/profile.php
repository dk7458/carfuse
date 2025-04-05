<?php
/**
 * User profile management view
 * 
 * This view allows users to update their profile information
 */

// Standard authentication check - use this instead of direct $_SESSION access
$required_role = null; // Any authenticated user can view their profile
$return_url = true;
$show_messages = true;
include_once BASE_PATH . '/public/views/components/auth-check.php';

$pageTitle = "Mój profil | CarFuse";
require_once BASE_PATH . '/public/views/layout/base.php';
?>

<?php startSection('content'); ?>

<div class="container mx-auto px-4 py-8"
     x-data="Object.assign(formValidation(), authState())"
     x-init="$nextTick(() => { 
        // Fetch user data when page loads
        if(isAuthenticated) {
            fetchUserData();
        }
     })">
     
    <!-- Success/error messages -->
    <div x-data="{ showSuccess: false, showError: false, message: '' }">
        <div 
            x-show="showSuccess" 
            x-transition
            class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded" 
            role="alert">
            <p x-text="message">Profil został zaktualizowany pomyślnie!</p>
        </div>
        
        <div 
            x-show="showError" 
            x-transition
            class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded" 
            role="alert">
            <p x-text="message">Wystąpił błąd podczas aktualizacji profilu.</p>
        </div>
    </div>
    
    <!-- Main profile form -->
    <div class="bg-white rounded-lg shadow-md p-6">
            
        <form 
            id="profileForm"
            @submit.prevent="handleSubmit($el, 
                function(data) { 
                    // Success handler
                    document.querySelector('[x-data=\"{ showSuccess: false, showError: false, message: \'\' }\"]').__x.$data.showSuccess = true;
                    document.querySelector('[x-data=\"{ showSuccess: false, showError: false, message: \'\' }\"]').__x.$data.message = 'Profil został zaktualizowany pomyślnie!';
                }, 4000);
                })"
        >
            <!-- Form fields will be dynamically added here -->
        </form>
    </div>
</div>

<?php endSection(); ?>
