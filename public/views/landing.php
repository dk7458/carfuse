<?php
/**
 * CarFuse - Modern Dark Landing Page
 * High-conversion landing page design with premium, trustworthy aesthetics
 */

// Set layout variables for base template
$pageTitle = "CarFuse - Premium Car Rental Service";
$metaDescription = "Rent premium vehicles effortlessly with CarFuse. Safe, secure, and instant online reservations with the best rates guaranteed.";

// Add custom meta tags for SEO
$extraMetaTags = <<<HTML
<meta name="keywords" content="car rental, premium cars, vehicle hire, luxury cars, rent a car">
<meta property="og:title" content="CarFuse - Premium Car Rental Service">
<meta property="og:description" content="Rent premium vehicles effortlessly with CarFuse. Safe, secure, and instant online reservations.">
<meta property="og:image" content="/images/social-share.jpg">
<meta property="og:type" content="website">
<meta name="twitter:card" content="summary_large_image">
HTML;

// Add custom head content to include our landing page specific styles
$head = <<<HTML
<link href="/css/landing-dark.css" rel="stylesheet">
HTML;

// Start output buffering to capture content for the base template
ob_start();
?>

<!-- Hero Section with Search Box -->
<section class="hero-image relative min-h-screen flex items-center">
    <div class="hero-content container mx-auto px-4 py-24">
        <div class="max-w-3xl">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-6">
                Rent a Car, <span class="text-blue-400">Effortlessly</span>.
            </h1>
            <p class="text-xl md:text-2xl text-gray-200 mb-8">
                Premium vehicles. Transparent pricing. No hidden fees.
            </p>
            
            <!-- Search Box -->
            <div class="bg-gray-900 p-6 rounded-lg shadow-lg mb-8 border border-gray-800">
                <form 
                    x-data="searchForm()" 
                    @submit.prevent="searchVehicles"
                    class="space-y-4 md:space-y-0 md:grid md:grid-cols-3 md:gap-4"
                >
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2" for="location">
                            Pickup Location
                        </label>
                        <select 
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded-md px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            id="location" 
                            x-model="location"
                            required
                        >
                            <option value="">Select location</option>
                            <option value="warsaw">Warsaw</option>
                            <option value="krakow">Krakow</option>
                            <option value="gdansk">Gdansk</option>
                            <option value="wroclaw">Wroclaw</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 text-sm font-medium mb-2" for="date">
                            Pickup Date
                        </label>
                        <input 
                            type="date" 
                            class="w-full bg-gray-800 border border-gray-700 text-white rounded-md px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500" 
                            id="date" 
                            x-model="date"
                            :min="minDate"
                            required
                        >
                    </div>
                    
                    <div class="flex items-end">
                        <button 
                            type="submit" 
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-lg shadow-md transition-transform transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-900 cta-btn"
                        >
                            <span x-show="!loading">Find Your Car</span>
                            <span x-show="loading" class="flex justify-center items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Searching...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="flex items-center mb-6">
                <span class="mr-2">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </span>
                <span class="text-gray-300">No credit card required to reserve</span>
                
                <span class="mx-4 text-gray-600">|</span>
                
                <span class="mr-2">
                    <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </span>
                <span class="text-gray-300">Free cancellation</span>
            </div>
            
            <!-- Personalized CTA for logged-in users -->
            <div x-data="authState()" x-cloak>
                <div x-show="isAuthenticated" class="mt-8 inline-block">
                    <a href="/user/dashboard" class="text-blue-400 hover:text-blue-300 border border-blue-400 hover:border-blue-300 rounded-lg px-6 py-3 font-medium transition duration-300">
                        View Your Bookings
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- How It Works Section -->
<section class="section bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">How CarFuse <span class="text-blue-400">Works</span></h2>
            <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                Three simple steps to get you on the road in your dream car.
            </p>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <!-- Step 1 -->
            <div class="text-center p-6 bg-gray-800 rounded-lg shadow-lg">
                <div class="flex justify-center mb-6">
                    <div class="rounded-full bg-blue-900 p-4 inline-flex">
                        <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-semibold mb-3">Choose Your Car</h3>
                <p class="text-gray-400">Browse our premium selection of vehicles and find the perfect match for your needs.</p>
            </div>
            
            <!-- Step 2 -->
            <div class="text-center p-6 bg-gray-800 rounded-lg shadow-lg">
                <div class="flex justify-center mb-6">
                    <div class="rounded-full bg-blue-900 p-4 inline-flex">
                        <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-semibold mb-3">Book Instantly</h3>
                <p class="text-gray-400">Secure your reservation online in minutes. No hidden charges, transparent pricing.</p>
            </div>
            
            <!-- Step 3 -->
            <div class="text-center p-6 bg-gray-800 rounded-lg shadow-lg">
                <div class="flex justify-center mb-6">
                    <div class="rounded-full bg-blue-900 p-4 inline-flex">
                        <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h3 class="text-2xl font-semibold mb-3">Drive & Enjoy</h3>
                <p class="text-gray-400">Pickup at your chosen location or opt for convenient delivery. Hit the road and enjoy.</p>
            </div>
        </div>
    </div>
</section>

<!-- Vehicle Showcase Section -->
<section class="section bg-gray-900 py-16">
    <div class="container mx-auto px-4">
        <div class="flex justify-between items-center mb-10">
            <h2 class="text-3xl font-bold">Featured <span class="text-blue-400">Vehicles</span></h2>
            <a href="/vehicles" class="text-blue-400 hover:text-blue-300 font-medium flex items-center">
                View All
                <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
        
        <!-- Vehicle cards with HTMX integration -->
        <div 
            class="relative" 
            x-data="{ scrollPosition: 0 }"
        >
            <!-- Left scroll button -->
            <button 
                @click="$refs.carContainer.scrollBy({left: -300, behavior: 'smooth'}); scrollPosition -= 300"
                class="absolute left-0 top-1/2 transform -translate-y-1/2 bg-gray-800 hover:bg-gray-700 text-white rounded-full p-3 z-10 shadow-lg border border-gray-700"
                x-show="scrollPosition > 0"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </button>
            
            <!-- Right scroll button -->
            <button 
                @click="$refs.carContainer.scrollBy({left: 300, behavior: 'smooth'}); scrollPosition += 300"
                class="absolute right-0 top-1/2 transform -translate-y-1/2 bg-gray-800 hover:bg-gray-700 text-white rounded-full p-3 z-10 shadow-lg border border-gray-700"
            >
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </button>
            
            <!-- Scrollable container -->
            <div 
                x-ref="carContainer"
                class="flex overflow-x-auto hide-scrollbar scroll-snap-x gap-6 pb-6"
                @scroll="scrollPosition = $event.target.scrollLeft"
                hx-get="/api/vehicles?featured=true&limit=8" 
                hx-trigger="load"
                hx-swap="innerHTML"
                style="scroll-behavior: smooth;"
            >
                <div class="w-full py-12 flex justify-center">
                    <div class="animate-pulse flex">
                        <svg class="w-8 h-8 text-blue-400 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="ml-3 text-lg">Loading vehicles...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust & Testimonials Section -->
<section class="section bg-gray-800">
    <div class="container mx-auto px-4">
        <div class="text-center mb-16">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Trusted by <span class="text-blue-400">Thousands</span></h2>
            <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                See what our customers have to say about their CarFuse experience.
            </p>
        </div>
        
        <!-- Trust badges -->
        <div class="flex flex-wrap justify-center gap-8 mb-16">
            <div class="flex items-center bg-gray-900 px-6 py-4 rounded-lg shadow-md">
                <svg class="w-10 h-10 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                </svg>
                <span class="ml-3 text-xl font-medium">Safe & Insured</span>
            </div>
            
            <div class="flex items-center bg-gray-900 px-6 py-4 rounded-lg shadow-md">
                <svg class="w-10 h-10 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                <span class="ml-3 text-xl font-medium">Instant Booking</span>
            </div>
            
            <div class="flex items-center bg-gray-900 px-6 py-4 rounded-lg shadow-md">
                <svg class="w-10 h-10 text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
                <span class="ml-3 text-xl font-medium">4.9/5 Customer Rating</span>
            </div>
            
            <div class="flex items-center bg-gray-900 px-6 py-4 rounded-lg shadow-md">
                <svg class="w-10 h-10 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="ml-3 text-xl font-medium">24/7 Support</span>
            </div>
        </div>
        
        <!-- Testimonials -->
        <div 
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mt-8"
            hx-get="/api/reviews?featured=true&limit=3" 
            hx-trigger="load"
            hx-swap="innerHTML"
        >
            <!-- Loading placeholders -->
            <?php for($i = 0; $i < 3; $i++): ?>
                <div class="bg-gray-800 p-6 rounded-lg shadow-md animate-pulse">
                    <div class="h-4 bg-gray-700 rounded w-3/4 mb-6"></div>
                    <div class="h-3 bg-gray-700 rounded w-full mb-2"></div>
                    <div class="h-3 bg-gray-700 rounded w-full mb-2"></div>
                    <div class="h-3 bg-gray-700 rounded w-4/5 mb-4"></div>
                    <div class="flex items-center mt-6">
                        <div class="rounded-full bg-gray-700 h-10 w-10"></div>
                        <div class="ml-3">
                            <div class="h-3 bg-gray-700 rounded w-24 mb-1"></div>
                            <div class="h-2 bg-gray-700 rounded w-16"></div>
                        </div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>
    </div>
</section>

<!-- Additional Benefits Section -->
<section class="section bg-gray-900">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Benefit 1 -->
            <div class="p-6 border border-gray-800 rounded-lg">
                <div class="text-blue-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Transparent Pricing</h3>
                <p class="text-gray-400">No hidden fees, no surprises. What you see is exactly what you pay.</p>
            </div>
            
            <!-- Benefit 2 -->
            <div class="p-6 border border-gray-800 rounded-lg">
                <div class="text-blue-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Fully Insured</h3>
                <p class="text-gray-400">All rentals include comprehensive insurance coverage for your peace of mind.</p>
            </div>
            
            <!-- Benefit 3 -->
            <div class="p-6 border border-gray-800 rounded-lg">
                <div class="text-blue-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Contactless Pickup</h3>
                <p class="text-gray-400">Easy, safe, and contact-free vehicle pickup with our smart lock technology.</p>
            </div>
            
            <!-- Benefit 4 -->
            <div class="p-6 border border-gray-800 rounded-lg">
                <div class="text-blue-400 mb-4">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-2">Secure Payments</h3>
                <p class="text-gray-400">Multiple payment options with encrypted, secure processing for total protection.</p>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section bg-blue-900">
    <div class="container mx-auto px-4 py-16">
        <div class="text-center">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">Ready to Start Your Journey?</h2>
            <p class="text-xl text-gray-200 max-w-3xl mx-auto mb-8">
                Join thousands of satisfied customers who chose CarFuse for their travel needs.
            </p>
            <a 
                href="/vehicles" 
                class="bg-white text-blue-900 hover:bg-gray-100 font-bold py-3 px-8 rounded-lg shadow-md transition-transform transform hover:scale-105 text-lg inline-block"
            >
                Browse Available Cars
            </a>
        </div>
    </div>
</section>

<!-- Footer Section -->
<footer class="bg-gray-900 text-gray-400 py-12">
    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12">
            <!-- Company Info -->
            <div>
                <h3 class="text-2xl font-bold text-white mb-4">CarFuse</h3>
                <p class="mb-4">Premium car rental service with transparent pricing and exceptional customer care.</p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M22 12c0-5.523-4.477-10-10-10S2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.878v-6.987h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.988C18.343 21.128 22 16.991 22 12z"></path>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M8.29 20.251c7.547 0 11.675-6.253 11.675-11.675 0-.178 0-.355-.012-.53A8.348 8.348 0 0022 5.92a8.19 8.19 0 01-2.357.646 4.118 4.118 0 001.804-2.27 8.224 8.224 0 01-2.605.996 4.107 4.107 0 00-6.993 3.743 11.65 11.65 0 01-8.457-4.287 4.106 4.106 0 001.27 5.477A4.072 4.072 0 012.8 9.713v.052a4.105 4.105 0 003.292 4.022 4.095 4.095 0 01-1.853.07 4.108 4.108 0 003.834 2.85A8.233 8.233 0 012 18.407a11.616 11.616 0 006.29 1.84"></path>
                        </svg>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12.315 2c2.43 0 2.784.013 3.808.06 1.064.049 1.791.218 2.427.465a4.902 4.902 0 011.772 1.153 4.902 4.902 0 011.153 1.772c.247.636.416 1.363.465 2.427.048 1.067.06 1.407.06 4.123v.08c0 2.643-.012 2.987-.06 4.043-.049 1.064-.218 1.791-.465 2.427a4.902 4.902 0 01-1.153 1.772 4.902 4.902 0 01-1.772 1.153c-.636.247-1.363.416-2.427.465-1.067.048-1.407.06-4.123.06h-.08c-2.643 0-2.987-.012-4.043-.06-1.064-.049-1.791-.218-2.427-.465a4.902 4.902 0 01-1.772-1.153 4.902 4.902 0 01-1.153-1.772c-.247-.636-.416-1.363-.465-2.427-.047-1.024-.06-1.379-.06-3.808v-.63c0-2.43.013-2.784.06-3.808.049-1.064.218-1.791.465-2.427a4.902 4.902 0 011.153-1.772A4.902 4.902 0 015.45 2.525c.636-.247 1.363-.416 2.427-.465C8.901 2.013 9.256 2 11.685 2h.63zm-.081 1.802h-.468c-2.456 0-2.784.011-3.807.058-.975.045-1.504.207-1.857.344-.467.182-.8.398-1.15.748-.35.35-.566.683-.748 1.15-.137.353-.3.882-.344 1.857-.047 1.023-.058 1.351-.058 3.807v.468c0 2.456.011 2.784.058 3.807.045.975.207 1.504.344 1.857.182.466.399.8.748 1.15.35.35.683.566 1.15.748.353.137.882.3 1.857.344 1.054.048 1.37.058 4.041.058h.08c2.597 0 2.917-.01 3.96-.058.976-.045 1.505-.207 1.858-.344.466-.182.8-.398 1.15-.748.35-.35.566-.683.748-1.15.137-.353.3-.882.344-1.857.048-1.055.058-1.37.058-4.041v-.08c0-2.597-.01-2.917-.058-3.96-.045-.976-.207-1.505-.344-1.858a3.097 3.097 0 00-.748-1.15 3.098 3.098 0 00-1.15-.748c-.353-.137-.882-.3-1.857-.344-1.023-.047-1.351-.058-3.807-.058zM12 6.865a5.135 5.135 0 110 10.27 5.135 5.135 0 010-10.27zm0 1.802a3.333 3.333 0 100 6.666 3.333 3.333 0 000-6.666zm5.338-3.205a1.2 1.2 0 110 2.4 1.2 1.2 0 010-2.4z"></path>
                        </svg>
                    </a>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div>
                <h3 class="text-xl font-semibold text-white mb-4">Quick Links</h3>
                <ul class="space-y-3">
                    <li><a href="/vehicles" class="hover:text-white transition-colors">Browse Cars</a></li>
                    <li><a href="/locations" class="hover:text-white transition-colors">Locations</a></li>
                    <li><a href="/how-it-works" class="hover:text-white transition-colors">How It Works</a></li>
                    <li><a href="/pricing" class="hover:text-white transition-colors">Pricing</a></li>
                    <li><a href="/faq" class="hover:text-white transition-colors">FAQ</a></li>
                </ul>
            </div>
            
            <!-- Legal -->
            <div>
                <h3 class="text-xl font-semibold text-white mb-4">Legal</h3>
                <ul class="space-y-3">
                    <li><a href="/terms" class="hover:text-white transition-colors">Terms of Service</a></li>
                    <li><a href="/privacy" class="hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="/cookies" class="hover:text-white transition-colors">Cookie Policy</a></li>
                    <li><a href="/refunds" class="hover:text-white transition-colors">Refund Policy</a></li>
                </ul>
            </div>
            
            <!-- Contact -->
            <div>
                <h3 class="text-xl font-semibold text-white mb-4">Contact Us</h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-1 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span>123 Main Street, Warsaw, Poland</span>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                        </svg>
                        <span>+48 123 456 789</span>
                    </li>
                    <li class="flex items-center">
                        <svg class="w-5 h-5 text-blue-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                        <span>support@carfuse.com</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center">
            <p>&copy; <?= date('Y') ?> CarFuse. All rights reserved.</p>
            <div class="mt-4 md:mt-0">
                <img src="/images/payment-methods.png" alt="Payment Methods" class="h-6">
            </div>
        </div>
    </div>
</footer>

<!-- Alpine.js components for the page -->
<script>
    function searchForm() {
        return {
            location: '',
            date: '',
            loading: false,
            minDate: new Date().toISOString().split('T')[0], // Today's date in YYYY-MM-DD format
            
            searchVehicles() {
                if (!this.location || !this.date) {
                    return;
                }
                
                this.loading = true;
                
                // Redirect to vehicles page with search parameters
                window.location.href = `/vehicles?location=${encodeURIComponent(this.location)}&date=${encodeURIComponent(this.date)}`;
            }
        };
    }
    
    function authState() {
        return {
            isAuthenticated: false,
            userRole: '',
            userData: null,
            
            init() {
                if (window.AuthHelper && typeof window.AuthHelper.isAuthenticated === 'function') {
                    this.isAuthenticated = window.AuthHelper.isAuthenticated();
                    
                    if (this.isAuthenticated) {
                        this.userRole = window.AuthHelper.getUserRole();
                        this.userData = window.AuthHelper.getUserData();
                    }
                }
                
                // Listen for auth state changes
                document.addEventListener('auth:stateChanged', () => {
                    if (window.AuthHelper && typeof window.AuthHelper.isAuthenticated === 'function') {
                        this.isAuthenticated = window.AuthHelper.isAuthenticated();
                        
                        if (this.isAuthenticated) {
                            this.userRole = window.AuthHelper.getUserRole();
                            this.userData = window.AuthHelper.getUserData();
                        } else {
                            this.userRole = '';
                            this.userData = null;
                        }
                    }
                });
            }
        };
    }
</script>

<!-- Hide scrollbars but allow scrolling -->
<style>
    .hide-scrollbar::-webkit-scrollbar {
        display: none; /* Chrome, Safari, Opera */
    }
    
    .hide-scrollbar {
        -ms-overflow-style: none; /* IE, Edge */
        scrollbar-width: none; /* Firefox */
    }
</style>

<?php
// Get the buffered content
$content = ob_get_clean();

// Include the base template which will use $pageTitle, $metaDescription, $head, etc.
include BASE_PATH . '/public/views/layouts/base.php';
?>
