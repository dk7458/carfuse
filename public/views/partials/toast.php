<div x-data="toastSystem"
     @show-toast.window="showToast($event.detail.title, $event.detail.message, $event.detail.type, $event.detail.duration)"
     class="fixed bottom-4 right-4 z-50 flex flex-col items-end space-y-4">

    <template x-for="(toast, index) in toasts" :key="toast.id">
        <div x-show="toast.visible" 
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform translate-x-4"
             x-transition:enter-end="opacity-100 transform translate-x-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform translate-x-0"
             x-transition:leave-end="opacity-0 transform translate-x-4"
             @click="removeToast(toast.id)"
             :class="{
                'bg-green-50 text-green-800 border-green-400': toast.type === 'success',
                'bg-blue-50 text-blue-800 border-blue-400': toast.type === 'info',
                'bg-yellow-50 text-yellow-800 border-yellow-400': toast.type === 'warning',
                'bg-red-50 text-red-800 border-red-400': toast.type === 'error'
             }"
             class="flex items-start p-4 rounded-md shadow-lg border-l-4 cursor-pointer max-w-sm">
             
            <div class="flex-shrink-0 mr-3 mt-0.5">
                <svg x-show="toast.type === 'success'" class="w-5 h-5 text-green-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <svg x-show="toast.type === 'info'" class="w-5 h-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
                <svg x-show="toast.type === 'warning'" class="w-5 h-5 text-yellow-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <svg x-show="toast.type === 'error'" class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            
            <div class="flex-1">
                <div class="flex justify-between items-center mb-1">
                    <h3 class="text-sm font-medium" x-text="toast.title"></h3>
                    <button @click.stop="removeToast(toast.id)" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Zamknij</span>
                        <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <p class="text-sm" x-text="toast.message"></p>
            </div>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastSystem', () => ({
            toasts: [],
            nextId: 0,
            
            init() {
                // Listen for HTMX triggered toasts
                document.addEventListener('htmx:responseError', (event) => {
                    // Get error message from response if available
                    try {
                        const json = JSON.parse(event.detail.xhr.responseText);
                        if (json && json.message) {
                            this.showToast('Błąd', json.message, 'error');
                            return;
                        }
                    } catch (e) {}
                    
                    // Fallback to generic error
                    if (event.detail.xhr.status >= 400) {
                        this.showToast(
                            'Błąd', 
                            `Wystąpił błąd ${event.detail.xhr.status}: ${event.detail.xhr.statusText}`, 
                            'error'
                        );
                    }
                });
            },
            
            showToast(title, message, type = 'info', duration = 5000) {
                const id = this.nextId++;
                const toast = { id, title, message, type, visible: true };
                
                this.toasts.push(toast);
                
                if (duration > 0) {
                    setTimeout(() => {
                        this.removeToast(id);
                    }, duration);
                }
            },
            
            removeToast(id) {
                const index = this.toasts.findIndex(toast => toast.id === id);
                if (index !== -1) {
                    // First hide it with transition
                    this.toasts[index].visible = false;
                    
                    // Then remove it from array after transition completes
                    setTimeout(() => {
                        this.toasts = this.toasts.filter(toast => toast.id !== id);
                    }, 300);
                }
            }
        }));
    });
</script>
