<?php
/**
 * Protected View Template
 * A template for protected views using client-side authentication
 */

// Set page metadata
$pageTitle = "Protected Page | CarFuse";
$metaDescription = "A protected page that requires authentication";

// Start output buffering
ob_start();
?>

<div class="container mx-auto px-4 py-8"
     x-data="authState()" 
     x-init="$nextTick(() => { 
        if(!isAuthenticated) {
            window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
        }
     })">
    
    <!-- Authentication Check with JS -->
    <template x-if="isAuthenticated">
        <!-- Protected Content -->
        <div class="bg-white shadow-md rounded-lg p-6">
            <h1 class="text-2xl font-bold text-gray-800">Protected Content</h1>
            <p class="text-gray-600">This content is only visible to authenticated users.</p>
            
            <!-- Content that requires authentication -->
        </div>
    </template>

    <!-- No JavaScript fallback -->
    <noscript>
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
            </div>
        </div>
    </noscript>
</div>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include base layout
include BASE_PATH . '/public/views/layouts/base.php';
?>
