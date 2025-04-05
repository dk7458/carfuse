<?php
/**
 * Admin User Management Page
 * Unified user management interface for CarFuse administrators
 */

// Set page title and meta description
$pageTitle = "CarFuse - Zarządzanie Użytkownikami";
$metaDescription = "Panel zarządzania użytkownikami systemu CarFuse";

// Extra styles
$extraStyles = <<<HTML
.fade-enter { opacity: 0; }
.fade-enter-active { transition: opacity 0.3s ease; }
.fade-enter-to { opacity: 1; }
HTML;

// Get CSRF token for API requests
$CSRF_TOKEN = $_SESSION['csrf_token'] ?? '';

// Start output buffering to capture the main content
ob_start();
?>

<div class="container mx-auto px-4 py-8" 
     x-data="Object.assign(userManagement(), authState())"
     x-init="$nextTick(() => {
        // Verify admin authorization
        checkAdminAuth().then(isAdmin => {
            if (!isAdmin) {
                window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
            } else {
                init();
            }
        });
     })">
    
    <template x-if="isAuthenticated && userRole === 'admin'">
        <!-- Page Header -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Zarządzanie Użytkownikami</h1>
                    <p class="text-gray-600">Przeglądaj, dodawaj i zarządzaj użytkownikami systemu.</p>
                </div>
                <div class="mt-4 md:mt-0">
                    <button @click="openNewUserModal()" 
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md flex items-center">
                        <i class="fas fa-user-plus mr-2"></i> Dodaj Użytkownika
                    </button>
                </div>
            </div>
        </div>

        <!-- Filters and Search -->
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
                    <!-- Role Filter -->
                    <div>
                        <label for="role-filter" class="block text-sm font-medium text-gray-700 mb-1">Filtruj po roli:</label>
                        <select id="role-filter" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                @change="filterRole = $event.target.value; currentPage = 1; loadUsers()">
                            <option value="all" selected>Wszystkie role</option>
                            <option value="user">Użytkownicy</option>
                            <option value="admin">Administratorzy</option>
                            <option value="manager">Menedżerowie</option>
                        </select>
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label for="status-filter" class="block text-sm font-medium text-gray-700 mb-1">Filtruj po statusie:</label>
                        <select id="status-filter" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                @change="filterStatus = $event.target.value; currentPage = 1; loadUsers()">
                            <option value="all" selected>Wszystkie statusy</option>
                            <option value="active">Aktywni</option>
                            <option value="inactive">Nieaktywni</option>
                        </select>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="relative w-full md:w-64">
                    <label for="user-search" class="block text-sm font-medium text-gray-700 mb-1">Szukaj:</label>
                    <div class="relative">
                        <input type="text" id="user-search" 
                               class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full pl-10 p-2.5"
                               placeholder="Szukaj użytkownika..."
                               x-model="searchQuery"
                               @keyup.enter="currentPage = 1; loadUsers()">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <button @click="currentPage = 1; loadUsers()" 
                                class="absolute inset-y-0 right-0 flex items-center pr-3 text-blue-600 hover:text-blue-800">
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <div class="p-4 border-b border-gray-200 font-medium text-gray-700 flex justify-between items-center">
                <span>Lista Użytkowników</span>
                <div class="flex items-center">
                    <span x-show="loading" class="inline-block animate-spin text-blue-500 mr-2">
                        <i class="fas fa-spinner"></i>
                    </span>
                    <button @click="reloadUserList()" 
                            class="text-sm font-medium text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-sync-alt mr-1"></i> Odśwież
                    </button>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Użytkownik
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Rola
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Data rejestracji
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Akcje
                            </th>
                        </tr>
                    </thead>
                    <tbody id="users-container" 
                           hx-get="/admin/api/users" 
                           hx-trigger="load" 
                           hx-vals="js:{page: currentPage, role: filterRole, status: filterStatus, search: searchQuery, csrf_token: csrfToken}"
                           hx-indicator="#users-loading"
                           hx-headers='js:{"Authorization": "Bearer " + window.AuthHelper.getToken(), "X-CSRF-TOKEN": csrfToken}'>
                        <!-- Users will be loaded here via HTMX -->
                        <tr id="users-loading">
                            <td colspan="6" class="px-6 py-8 text-center">
                                <div class="flex justify-center">
                                    <svg class="animate-spin h-8 w-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                                <p class="mt-2 text-gray-500">Ładowanie użytkowników...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Empty State -->
            <div id="no-users-message" class="hidden py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Brak użytkowników</h3>
                <p class="mt-1 text-sm text-gray-500">Nie znaleziono użytkowników pasujących do kryteriów wyszukiwania.</p>
            </div>
            
            <!-- Pagination -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Pokazuję <span class="font-medium" x-text="startItem">1</span>-<span class="font-medium" x-text="endItem">10</span> z 
                        <span class="font-medium" x-text="totalItems">100</span> użytkowników
                    </div>
                    <div class="flex space-x-2">
                        <button @click="prevPage()" 
                                :disabled="currentPage === 1"
                                :class="{'opacity-50 cursor-not-allowed': currentPage === 1}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-2"></i> Poprzednia
                        </button>
                        <button @click="nextPage()" 
                                :disabled="currentPage === totalPages"
                                :class="{'opacity-50 cursor-not-allowed': currentPage === totalPages}"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Następna <i class="fas fa-chevron-right ml-2"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add New User Modal -->
        <div x-show="showNewUserModal" 
             class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4"
             x-cloak>
            <div class="bg-white rounded-lg max-w-lg w-full mx-auto overflow-hidden shadow-xl transform transition-all"
                 @click.away="showNewUserModal = false"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Dodaj Nowego Użytkownika</h3>
                    <button @click="showNewUserModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="new-user-form" @submit.prevent="createUser()">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Imię:</label>
                                <input type="text" id="name" name="name" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="newUser.name">
                            </div>
                            <div>
                                <label for="surname" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko:</label>
                                <input type="text" id="surname" name="surname" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="newUser.surname">
                            </div>
                        </div>
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                            <input type="email" id="email" name="email" required
                                   class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                   x-model="newUser.email">
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Hasło:</label>
                            <div class="relative">
                                <input :type="showPassword ? 'text' : 'password'" id="password" name="password" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       minlength="8"
                                       x-model="newUser.password">
                                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 flex items-center pr-3">
                                    <i :class="showPassword ? 'fas fa-eye-slash' : 'fas fa-eye'" class="text-gray-400"></i>
                                </button>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 znaków.</p>
                        </div>
                        <div>
                            <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Rola:</label>
                            <select id="role" name="role" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    x-model="newUser.role">
                                <option value="user">Użytkownik</option>
                                <option value="admin">Administrator</option>
                                <option value="manager">Menedżer</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="showNewUserModal = false" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Anuluj
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500" 
                                :disabled="isSubmitting"
                                :class="{'opacity-75 cursor-wait': isSubmitting}">
                            <span x-show="!isSubmitting">Dodaj Użytkownika</span>
                            <span x-show="isSubmitting" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Przetwarzanie...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Edit User Modal -->
        <div x-show="showEditModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-lg max-w-lg w-full mx-auto overflow-hidden shadow-xl transform transition-all"
                 @click.away="showEditModal = false">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Edytuj Użytkownika</h3>
                    <button @click="showEditModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <form id="edit-user-form" @submit.prevent="updateUser()">
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">ID:</label>
                                <input type="text" readonly
                                       class="bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg block w-full p-2.5"
                                       x-model="editingUser.id">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email:</label>
                                <input type="email" readonly
                                       class="bg-gray-100 border border-gray-300 text-gray-600 text-sm rounded-lg block w-full p-2.5"
                                       x-model="editingUser.email">
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="edit-name" class="block text-sm font-medium text-gray-700 mb-1">Imię:</label>
                                <input type="text" id="edit-name" name="name" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="editingUser.name">
                            </div>
                            <div>
                                <label for="edit-surname" class="block text-sm font-medium text-gray-700 mb-1">Nazwisko:</label>
                                <input type="text" id="edit-surname" name="surname" required
                                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                       x-model="editingUser.surname">
                            </div>
                        </div>
                        <div>
                            <label for="edit-role" class="block text-sm font-medium text-gray-700 mb-1">Rola:</label>
                            <select id="edit-role" name="role" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    x-model="editingUser.role">
                                <option value="user">Użytkownik</option>
                                <option value="admin">Administrator</option>
                                <option value="manager">Menedżer</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit-status" class="block text-sm font-medium text-gray-700 mb-1">Status:</label>
                            <select id="edit-status" name="status" required
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
                                    x-model="editingUser.active">
                                <option :value="true">Aktywny</option>
                                <option :value="false">Nieaktywny</option>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="button" @click="showEditModal = false" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Anuluj
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Zapisz Zmiany
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Delete Confirmation Modal -->
        <div x-show="showDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 flex items-center justify-center p-4" x-cloak>
            <div class="bg-white rounded-lg max-w-md w-full mx-auto overflow-hidden shadow-xl transform transition-all"
                 @click.away="showDeleteModal = false">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-800">Potwierdź Usunięcie</h3>
                    <button @click="showDeleteModal = false" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-6">
                    <div class="flex items-center justify-center mb-4 text-red-500">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1zM4 7h16"></path>
                        </svg>
                    </div>
                    <p class="text-gray-700 text-center mb-4">Czy na pewno chcesz usunąć tego użytkownika?</p>
                    <p class="text-gray-700 text-center font-semibold mb-6" x-text="`${deletingUser.name} ${deletingUser.surname} (${deletingUser.email})`"></p>
                    <div class="text-sm text-gray-500 bg-gray-50 p-4 rounded-md mb-4">
                        <p><i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i> Tej operacji nie można cofnąć.</p>
                    </div>
                </div>
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button type="button" @click="showDeleteModal = false" class="mr-2 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Anuluj
                    </button>
                    <button type="button" 
                            @click="confirmDelete()" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                            :disabled="isDeleting"
                            :class="{'opacity-75 cursor-wait': isDeleting}">
                        <span x-show="!isDeleting">Usuń Użytkownika</span>
                        <span x-show="isDeleting" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Usuwanie...
                        </span>
                    </button>
                </div>
            </div>
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
                <div class="ml-3">
                    <p class="text-sm text-yellow-700">
                        Ta strona wymaga JavaScript do prawidłowego działania. Proszę włączyć JavaScript w przeglądarce.
                    </p>
                </div>
            </div>
        </div>

        <div id="noscript-auth-check">
            <div class="bg-gray-200 p-4 rounded">
                <p>Sprawdzanie uprawnień...</p>
            </div>
        </div>

        <script>
            // Server-side authentication fallback for no-JS browsers
            fetch('/auth/me')
            .then(response => response.json())
            .then(data => {
                const container = document.getElementById('noscript-auth-check');
                if (!data.isAuthenticated || data.role !== 'admin') {
                    container.innerHTML = '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">Dostęp tylko dla administratorów. Przekierowywanie...</p></div>';
                    window.location.href = '/auth/login?redirect=' + encodeURIComponent(window.location.pathname);
                } else {
                    container.innerHTML = '<div class="bg-white shadow-md rounded-lg p-6 mb-6">' +
                        '<h1 class="text-2xl font-bold text-gray-800">Zarządzanie Użytkownikami</h1>' +
                        '<p class="text-gray-600">Panel dostępny tylko z włączonym JavaScript.</p>' +
                        '<p class="mt-4"><a href="/admin/dashboard" class="text-blue-600 hover:underline">« Powrót do panelu administratora</a></p>' +
                        '</div>';
                }
            })
            .catch(() => {
                document.getElementById('noscript-auth-check').innerHTML = 
                    '<div class="bg-red-50 border-l-4 border-red-400 p-4"><p class="text-red-700">Błąd autoryzacji. <a href="/auth/login" class="font-bold underline">Zaloguj się</a>.</p></div>';
            });
        </script>
    </noscript>
</div>

<!-- Update userManagement function to include token-based auth and CSRF tokens -->
<script>
function userManagement() {
    const csrfToken = "<?php echo $CSRF_TOKEN; ?>";
    
    return {
        filterRole: 'all',
        filterStatus: 'all',
        searchQuery: '',
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        startItem: 1,
        endItem: 10,
        users: [],
        loading: false,
        showNewUserModal: false,
        showEditModal: false,
        showDeleteModal: false,
        showPassword: false,
        isSubmitting: false,
        isDeleting: false,
        newUser: {
            name: '',
            surname: '',
            email: '',
            password: '',
            role: 'user'
        },
        editingUser: {},
        deletingUser: {},
        
        // Check admin authentication
        checkAdminAuth() {
            return fetch('/auth/me')
                .then(response => response.json())
                .then(data => {
                    return data.isAuthenticated && data.role === 'admin';
                })
                .catch(() => false);
        },
        
        init() {
            this.loadUsers();
        },
        
        loadUsers() {
            this.loading = true;
            const usersContainer = document.getElementById('users-container');
            
            usersContainer.setAttribute('hx-vals', 
                JSON.stringify({
                    page: this.currentPage, 
                    role: this.filterRole, 
                    status: this.filterStatus, 
                    search: this.searchQuery,
                    csrf_token: csrfToken
                })
            );
            
            // Add auth token and CSRF token to HTMX request
            usersContainer.setAttribute('hx-headers', 
                JSON.stringify({
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                })
            );
            
            htmx.trigger(usersContainer, 'load');
            
            // Update pagination info via authenticated API call
            fetch(`/admin/api/users/count?role=${this.filterRole}&status=${this.filterStatus}&search=${encodeURIComponent(this.searchQuery)}`, {
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(response => response.json())
                .then(data => {
                    this.totalItems = data.total || 0;
                    this.totalPages = data.pages || 1;
                    
                    // Calculate start and end items
                    const perPage = data.perPage || 10;
                    this.startItem = (this.currentPage - 1) * perPage + 1;
                    this.endItem = Math.min(this.startItem + perPage - 1, this.totalItems);
                    
                    // Show/hide empty state
                    if (this.totalItems === 0) {
                        document.getElementById('no-users-message').classList.remove('hidden');
                    } else {
                        document.getElementById('no-users-message').classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error fetching user count:', error);
                })
                .finally(() => {
                    this.loading = false;
                });
        },
        
        // More user management functions...
        
        reloadUserList() {
            this.loadUsers();
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadUsers();
            }
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadUsers();
            }
        },
        
        createUser() {
            this.isSubmitting = true;
            fetch('/admin/api/users', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(this.newUser)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.showNewUserModal = false;
                    this.newUser = { name: '', surname: '', email: '', password: '', role: 'user' };
                    this.reloadUserList();
                } else {
                    console.error('Error creating user:', data.message);
                }
            })
            .catch(error => console.error('Error creating user:', error))
            .finally(() => {
                this.isSubmitting = false;
            });
        },
        
        updateUser() {
            this.isSubmitting = true;
            fetch(`/admin/api/users/${this.editingUser.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify(this.editingUser)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.showEditModal = false;
                    this.reloadUserList();
                } else {
                    console.error('Error updating user:', data.message);
                }
            })
            .catch(error => console.error('Error updating user:', error))
            .finally(() => {
                this.isSubmitting = false;
            });
        },
        
        confirmDelete() {
            this.isDeleting = true;
            fetch(`/admin/api/users/${this.deletingUser.id}`, {
                method: 'DELETE',
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.showDeleteModal = false;
                    this.reloadUserList();
                } else {
                    console.error('Error deleting user:', data.message);
                }
            })
            .catch(error => console.error('Error deleting user:', error))
            .finally(() => {
                this.isDeleting = false;
            });
        },
        
        openEditModal(userId) {
            fetch(`/admin/api/users/${userId}`, {
                headers: {
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.editingUser = data.data;
                    this.showEditModal = true;
                }
            })
            .catch(error => console.error('Error fetching user details:', error));
        },
        
        toggleUserStatus(userId, currentStatus) {
            fetch(`/admin/api/users/${userId}/status`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ active: !currentStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.reloadUserList();
                } else {
                    console.error('Error toggling user status:', data.message);
                }
            })
            .catch(error => console.error('Error toggling user status:', error));
        },
        
        updateUserRole(userId, newRole) {
            fetch(`/admin/api/users/${userId}/role`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.reloadUserList();
                } else {
                    console.error('Error updating user role:', data.message);
                }
            })
            .catch(error => console.error('Error updating user role:', error));
        },
        
        openDeleteModal(userId, name, surname, email) {
            this.deletingUser = { id: userId, name, surname, email };
            this.showDeleteModal = true;
        }
    };
}
</script>

<!-- Template for HTMX user row responses -->
<template id="user-row-template">
  <tr class="booking-item">
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
      #{{id}}
    </td>
    <td class="px-6 py-4 whitespace-nowrap">
      <div class="flex items-center">
        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center text-gray-700">
          {{initials}}
        </div>
        <div class="ml-4">
          <div class="text-sm font-medium text-gray-900">{{name}} {{surname}}</div>
          <div class="text-sm text-gray-500">{{email}}</div>
        </div>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap" id="user-role-{{id}}">
      <div class="inline-flex items-center">
        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{roleColorClass}}">
          {{roleLabel}}
        </span>
        <div class="relative ml-2" x-data="{ roleDropdownOpen: false }">
          <button @click="roleDropdownOpen = !roleDropdownOpen" 
                  type="button" 
                  class="text-gray-400 hover:text-gray-600">
            <i class="fas fa-edit text-xs"></i>
          </button>
          <div x-show="roleDropdownOpen" 
               @click.away="roleDropdownOpen = false"
               class="origin-top-right absolute right-0 mt-2 w-36 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-20">
            <div class="py-1">
              <a href="#" @click.prevent="updateUserRole({{id}}, 'user'); roleDropdownOpen = false" 
                 class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                 Użytkownik
              </a>
              <a href="#" @click.prevent="updateUserRole({{id}}, 'manager'); roleDropdownOpen = false" 
                 class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                 Menedżer
              </a>
              <a href="#" @click.prevent="updateUserRole({{id}}, 'admin'); roleDropdownOpen = false" 
                 class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                 Administrator
              </a>
            </div>
          </div>
        </div>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap" id="user-status-{{id}}">
      <div class="inline-flex items-center">
        <button @click="toggleUserStatus({{id}}, {{active}})" 
                class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 {{statusBgClass}}">
          <span class="{{active ? 'translate-x-5' : 'translate-x-0'}} inline-block h-5 w-5 rounded-full bg-white shadow transform transition ease-in-out duration-200"></span>
        </button>
        <span class="ml-2 text-sm {{statusTextClass}}">{{statusLabel}}</span>
      </div>
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
      {{created_at}}
    </td>
    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
      <button @click="openEditModal({{id}})" class="text-blue-600 hover:text-blue-900 mr-3">
        <i class="fas fa-edit"></i> Edytuj
      </button>
      <button @click="openDeleteModal({{id}}, '{{name}}', '{{surname}}', '{{email}}')" class="text-red-600 hover:text-red-900">
        <i class="fas fa-trash-alt"></i> Usuń
      </button>
    </td>
  </tr>
</template>

<?php 
// Get content and store it in the variable
$content = ob_get_clean();

// Include base template with the content and extras
include BASE_PATH . '/public/views/layouts/base.php';
?>
