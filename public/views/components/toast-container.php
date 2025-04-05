<?php
/**
 * Toast Notification Container
 * Reusable toast notification system for CarFuse
 */
?>
<div x-data="toastSystem" class="fixed top-4 right-4 z-50 flex flex-col space-y-4">
    <template x-for="toast in toasts" :key="toast.id">
        <div 
            x-show="true" 
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform translate-y-2"
            :class="toast.bgColor" 
            class="max-w-sm rounded-lg shadow-lg text-white p-4 flex items-start">
            <div class="flex-shrink-0" x-html="toast.icon"></div>
            <div class="ml-3 flex-1">
                <h3 class="font-medium" x-text="toast.title"></h3>
                <div class="mt-1 text-sm opacity-90" x-text="toast.message"></div>
            </div>
            <button @click="removeToast(toast.id)" class="ml-4 text-white opacity-70 hover:opacity-100">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
    </template>
</div>
