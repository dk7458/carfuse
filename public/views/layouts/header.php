<?php
/**
 * Unified Header Component
 * Combines navigation bar and header with dynamic greeting
 * Uses Alpine.js for state management and conditional rendering
 */

// Minimal session start only if needed for server-side rendering fallback
if (!isset($_SESSION)) {
    session_start();
}

// Server-side fallback variables (used when JS is disabled or loading)
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$username = $isLoggedIn ? ($_SESSION['user_name'] ?? $_SESSION['username'] ?? 'Użytkowniku') : "Gość";

// Fetch a random car model for the greeting
$availableCars = ["Toyota Corolla", "Ford Mustang", "BMW X5", "Audi A6", "Mercedes C-Class"];
$randomCar = $availableCars[array_rand($availableCars)];

// List of dynamic greetings
$greetings = [
    "Hej, {username}! {car} już czeka na przygodę! 🚗💨",
    "Witaj, {username}! Może dziś przejażdżka {car}? 🌍",
    "{username}, świat stoi przed Tobą otworem! {car} już grzeje silnik! 🏎️",
    "Gotowy na podróż, {username}? {car} nie chce stać w miejscu! 📅",
    "{username}, może czas na spontaniczny wypad? {car} jest gotowy! 🎒",
    "Nie czekaj, {username} – {car} znika szybciej niż hot dogi na stacji! ⏳",
    "Witaj, {username}! Może dziś coś sportowego? {car} czeka na rozgrzanie! 🏁",
    "Nie musisz mieć własnego auta, {username}! {car} już czeka, by Ci służyć! 🚗",
    "Hej, {username}! {car} to klucz do niezapomnianej podróży! 💰"
];

$randomGreeting = $greetings[array_rand($greetings)];
$greeting = $isLoggedIn ? 
    str_replace(['{username}', '{car}'], [$username, $randomCar], $randomGreeting) : 
    "Witaj w CarFuse! Wynajmij auto i ruszaj w drogę!";

// Prevent duplicate layout loading
if (!empty($_SESSION['layout_loaded'])) {
    return;
}
$_SESSION['layout_loaded'] = true;
?>

<!-- Unified Header with Navigation -->
<header
    x-data="{ 
        mobileMenuOpen: false, 
        notificationsOpen: false, 
        userMenuOpen: false, 
        unreadCount: 0,
        authState: {
            isAuthenticated: false,
            username: 'Gość',
            userInitial: 'G',
            userRole: '',
            userId: null,
            userEmail: '',
            hasRole(role) { return this.userRole === role; }
        },
        greeting: '<?= htmlspecialchars($greeting) ?>',
        async initAuth() {
            if (window.AuthHelper) {
                this.authState = window.AuthHelper.getAuthState();
                this.fetchUnreadCount();
                
                // Listen for auth state changes
                window.addEventListener('auth-state-changed', () => {
                    this.authState = window.AuthHelper.getAuthState();
                });
            }
        },
        async fetchUnreadCount() {
            if (this.authState.isAuthenticated) {
                try {
                    const response = await fetch('/api/notifications/unread-count', {
                        headers: {
                            'Authorization': 'Bearer ' + window.AuthHelper.getToken(),
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await response.json();
                    if (data.status === 'success') {
                        this.unreadCount = data.data.count || 0;
                    }
                } catch (error) {
                    console.error('Error fetching notifications:', error);
                }
            }
        },
        logout() {
            if (window.AuthHelper) {
                window.AuthHelper.logout()
                    .then(() => {
                        window.location.href = '/auth/login';
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                    });
            }
        }
    }"
    x-init="initAuth()"
    class="bg-white"
>
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-md">
        <div class="max-w-7xl mx-auto px-2 sm:px-4 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex px-2 lg:px-0">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="/" class="flex items-center">
                            <span class="text-2xl font-bold text-blue-600">🚗 CarFuse</span>
                        </a>
                    </div>
                    
                    <!-- Desktop Navigation Links -->
                    <div class="hidden lg:ml-6 lg:flex lg:space-x-4">
                        <a href="/" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                            Strona Główna
                        </a>
                        
                        <!-- Authenticated User Links (Alpine.js) -->
                        <template x-if="authState.isAuthenticated">
                            <div class="flex space-x-4">
                                <template x-if="authState.hasRole('admin')">
                                    <div class="flex space-x-4">
                                        <a href="/admin/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Panel Administratora
                                        </a>
                                        <a href="/admin/vehicles" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Zarządzanie Pojazdami
                                        </a>
                                        <a href="/admin/bookings" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Rezerwacje
                                        </a>
                                        <a href="/admin/users" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Użytkownicy
                                        </a>
                                    </div>
                                </template>
                                
                                <template x-if="!authState.hasRole('admin')">
                                    <div class="flex space-x-4">
                                        <a href="/dashboard" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Panel Główny
                                        </a>
                                        <a href="/bookings" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Moje Rezerwacje
                                        </a>
                                        <a href="/vehicles" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                            Oferta Pojazdów
                                        </a>
                                    </div>
                                </template>
                            </div>
                        </template>
                        
                        <!-- Guest Links (Alpine.js) -->
                        <template x-if="!authState.isAuthenticated">
                            <div class="flex space-x-4">
                                <a href="/vehicles" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                    Oferta Pojazdów
                                </a>
                                <a href="/pricing" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                    Cennik
                                </a>
                                <a href="/contact" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                    Kontakt
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
                
                <!-- Right Side Navigation Items -->
                <div class="flex items-center lg:hidden">
                    <!-- Mobile menu button -->
                    <button 
                        @click="mobileMenuOpen = !mobileMenuOpen" 
                        type="button" 
                        class="inline-flex items-center justify-center p-2 rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-50 focus:outline-none" 
                        aria-expanded="false"
                    >
                        <span class="sr-only">Otwórz menu główne</span>
                        <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" x-show="!mobileMenuOpen"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" x-show="mobileMenuOpen"></path>
                        </svg>
                    </button>
                </div>
                
                <!-- Desktop Right Side Items -->
                <div class="hidden lg:ml-4 lg:flex lg:items-center">
                    <!-- Authenticated User Options (Alpine.js) -->
                    <template x-if="authState.isAuthenticated">
                        <!-- Notifications Dropdown -->
                        <div class="ml-4 relative flex-shrink-0">
                            <button @click="notificationsOpen = !notificationsOpen" class="relative p-1 rounded-full text-gray-600 hover:text-gray-900 focus:outline-none">
                                <span class="sr-only">Zobacz powiadomienia</span>
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                <span x-show="unreadCount > 0" 
                                    class="absolute top-0 right-0 flex h-5 w-5 rounded-full bg-red-500 text-xs text-white font-bold items-center justify-center transform -translate-y-1 translate-x-1" 
                                    x-text="unreadCount"></span>
                            </button>
                            
                            <!-- Notifications Panel -->
                            <div x-show="notificationsOpen" 
                                @click.away="notificationsOpen = false" 
                                x-transition:enter="transition ease-out duration-100" 
                                x-transition:enter-start="transform opacity-0 scale-95" 
                                x-transition:enter-end="transform opacity-100 scale-100" 
                                x-transition:leave="transition ease-in duration-75" 
                                x-transition:leave-start="transform opacity-100 scale-100" 
                                x-transition:leave-end="transform opacity-0 scale-95" 
                                class="origin-top-right absolute right-0 mt-2 w-96 rounded-md shadow-lg z-50"
                                x-cloak>
                                <div id="notifications-dropdown-container" 
                                     hx-get="/user/notifications" 
                                     hx-trigger="load"
                                     hx-swap="innerHTML">
                                    <!-- Loading state -->
                                    <div class="bg-white rounded-md shadow-lg py-2 px-4">
                                        <div class="animate-pulse flex space-x-4 py-2">
                                            <div class="rounded-full bg-gray-200 h-10 w-10"></div>
                                            <div class="flex-1 space-y-2">
                                                <div class="h-4 bg-gray-200 rounded w-3/4"></div>
                                                <div class="h-4 bg-gray-200 rounded w-1/2"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="ml-4 relative flex-shrink-0">
                            <div>
                                <button @click="userMenuOpen = !userMenuOpen" type="button" class="rounded-full flex text-sm focus:outline-none items-center text-gray-700 hover:text-gray-900" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Otwórz menu użytkownika</span>
                                    <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center mr-2">
                                        <span class="text-blue-700 font-medium" x-text="authState.userInitial"></span>
                                    </div>
                                    <span class="hidden md:block font-medium" x-text="authState.username"></span>
                                    <svg class="ml-1 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- User Dropdown Menu -->
                            <div x-show="userMenuOpen" 
                                @click.away="userMenuOpen = false" 
                                x-transition:enter="transition ease-out duration-100" 
                                x-transition:enter-start="transform opacity-0 scale-95" 
                                x-transition:enter-end="transform opacity-100 scale-100" 
                                x-transition:leave="transition ease-in duration-75" 
                                x-transition:leave-start="transform opacity-100 scale-100" 
                                x-transition:leave-end="transform opacity-0 scale-95" 
                                class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg py-1 bg-white ring-1 ring-black ring-opacity-5 focus:outline-none z-50" 
                                role="menu" 
                                aria-orientation="vertical" 
                                aria-labelledby="user-menu-button" 
                                tabindex="-1"
                                x-cloak>
                                
                                <a href="/profile" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-user mr-2"></i> Mój profil
                                </a>
                                
                                <template x-if="authState.hasRole('admin')">
                                    <a href="/admin/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        <i class="fas fa-cog mr-2"></i> Ustawienia
                                    </a>
                                </template>
                                
                                <template x-if="!authState.hasRole('admin')">
                                    <a href="/settings" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                        <i class="fas fa-cog mr-2"></i> Ustawienia
                                    </a>
                                </template>
                                
                                <hr class="my-1">
                                
                                <a href="#" @click.prevent="logout()" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Wyloguj się
                                </a>
                            </div>
                        </div>
                    </template>
                    
                    <!-- Guest Options (Alpine.js) -->
                    <template x-if="!authState.isAuthenticated">
                        <div class="flex items-center">
                            <a href="/auth/login" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none">
                                <i class="fas fa-sign-in-alt mr-2"></i> Zaloguj się
                            </a>
                            <a href="/auth/register" class="ml-3 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                                <i class="fas fa-user-plus mr-2"></i> Zarejestruj się
                            </a>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Mobile menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 transform -translate-y-2" 
             x-transition:enter-end="opacity-100 transform translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 transform translate-y-0" 
             x-transition:leave-end="opacity-0 transform -translate-y-2" 
             class="lg:hidden"
             x-cloak>
            <div class="pt-2 pb-3 space-y-1">
                <a href="/" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Strona Główna</a>
                
                <!-- Authenticated User Mobile Links (Alpine.js) -->
                <template x-if="authState.isAuthenticated">
                    <div>
                        <template x-if="authState.hasRole('admin')">
                            <div>
                                <a href="/admin/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Panel Administratora</a>
                                <a href="/admin/vehicles" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Zarządzanie Pojazdami</a>
                                <a href="/admin/bookings" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Rezerwacje</a>
                                <a href="/admin/users" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Użytkownicy</a>
                            </div>
                        </template>
                        <template x-if="!authState.hasRole('admin')">
                            <div>
                                <a href="/dashboard" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Panel Główny</a>
                                <a href="/bookings" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Moje Rezerwacje</a>
                                <a href="/vehicles" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Oferta Pojazdów</a>
                            </div>
                        </template>
                    </div>
                </template>
                
                <!-- Guest Mobile Links (Alpine.js) -->
                <template x-if="!authState.isAuthenticated">
                    <div>
                        <a href="/vehicles" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Oferta Pojazdów</a>
                        <a href="/pricing" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Cennik</a>
                        <a href="/contact" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">Kontakt</a>
                    </div>
                </template>
            </div>
            
            <!-- Mobile menu user info -->
            <template x-if="authState.isAuthenticated">
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="flex items-center px-4">
                        <div class="flex-shrink-0">
                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-700 font-medium" x-text="authState.userInitial"></span>
                            </div>
                        </div>
                        <div class="ml-3">
                            <div class="text-base font-medium text-gray-800" x-text="authState.username"></div>
                            <div class="text-sm font-medium text-gray-500" x-text="authState.userEmail"></div>
                        </div>
                        <button class="ml-auto flex-shrink-0 p-1 rounded-full text-gray-600 hover:text-gray-900 focus:outline-none"
                            hx-get="/user/notifications" 
                            hx-target="#mobile-notifications-container"
                            hx-swap="innerHTML">
                            <span class="sr-only">Zobacz powiadomienia</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </button>
                    </div>
                    
                    <div id="mobile-notifications-container" class="mt-3 px-2 space-y-1">
                        <!-- Mobile notifications will be loaded here via HTMX -->
                    </div>
                    
                    <div class="mt-3 space-y-1">
                        <a href="/profile" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                            <i class="fas fa-user mr-2"></i> Mój profil
                        </a>
                        
                        <template x-if="authState.hasRole('admin')">
                            <a href="/admin/settings" class="block px-4 py-2 text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i> Ustawienia
                            </a>
                        </template>
                        
                        <template x-if="!authState.hasRole('admin')">
                            <a href="/settings" class="block px-4 py-2 text-base font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-cog mr-2"></i> Ustawienia
                            </a>
                        </template>
                        
                        <a href="#" @click.prevent="logout()" class="block px-4 py-2 text-base font-medium text-red-600 hover:text-red-900 hover:bg-gray-50">
                            <i class="fas fa-sign-out-alt mr-2"></i> Wyloguj się
                        </a>
                    </div>
                </div>
            </template>
            
            <!-- Mobile menu guest options -->
            <template x-if="!authState.isAuthenticated">
                <div class="pt-4 pb-3 border-t border-gray-200">
                    <div class="space-y-1 px-3">
                        <a href="/auth/login" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50">
                            <i class="fas fa-sign-in-alt mr-2"></i> Zaloguj się
                        </a>
                        <a href="/auth/register" class="block px-3 py-2 rounded-md text-base font-medium text-blue-600 hover:text-blue-900 hover:bg-gray-50">
                            <i class="fas fa-user-plus mr-2"></i> Zarejestruj się
                        </a>
                    </div>
                </div>
            </template>
        </div>
    </nav>

    <!-- Greeting Banner -->
    <div class="bg-blue-50 py-3">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center text-blue-700 font-medium" x-text="greeting">
                <?= $greeting ?>
            </div>
        </div>
    </div>
    
    <!-- Server-side fallback for when JavaScript is disabled -->
    <noscript>
        <!-- Fallback navigation for authenticated users -->
        <?php if ($isLoggedIn): ?>
            <div class="bg-yellow-100 p-2 text-center text-yellow-700 text-sm">
                <p>Niektóre funkcje są ograniczone, gdy JavaScript jest wyłączony.</p>
            </div>
            
            <!-- Add this logout form with CSRF token -->
            <div class="p-2 text-center">
                <form action="/auth/logout" method="POST">
                    <input type="hidden" name="_csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
                    <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-sign-out-alt mr-2"></i> Wyloguj się (bez JavaScript)
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </noscript>
</header>