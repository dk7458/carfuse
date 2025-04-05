<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-16">
        <!-- Logo -->
        <div class="flex-shrink-0">
            <a href="/">
                <img class="h-8 w-auto" src="/path/to/logo.png" alt="Logo">
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="hidden sm:flex sm:space-x-4">
            <?php // PHP loop for navigation items ?>
            <a 
                href="/home" 
                class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-200" 
                aria-current="<?php echo ($activeMenu === 'home') ? 'page' : 'false'; ?>"
                hx-get="/navbar/highlight?menu=home"
            >
                Home
            </a>
            <!-- ...other menu items... -->
        </div>

        <!-- Mobile Menu Button -->
        <div class="-mr-2 flex sm:hidden">
            <button 
                @click="open = !open" 
                @keydown.escape="open = false"
                aria-label="Toggle menu" 
                class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100"
            >
                <svg class="h-6 w-6" fill="none" stroke="currentColor">
                    <!-- ...icon paths... -->
                </svg>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div 
        class="sm:hidden" 
        x-show="open" 
        x-transition 
        @click.away="open = false"
    >
        <div class="px-2 pt-2 pb-3 space-y-1">
            <?php // Duplicate the navigation items for mobile ?>
            <a 
                href="/home" 
                class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:bg-gray-100"
                aria-current="<?php echo ($activeMenu === 'home') ? 'page' : 'false'; ?>"
            >
                Home
            </a>
            <!-- ...other menu items... -->
        </div>
    </div>
</nav>
