<div class="bg-white rounded-lg shadow-md p-6" x-data="{ editing: false }">
    <!-- Profile header -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Dane Profilu</h2>
        <button 
            @click="editing = !editing" 
            class="px-4 py-2 rounded-md"
            :class="editing ? 'bg-gray-200 text-gray-600' : 'bg-blue-600 text-white hover:bg-blue-700'"
        >
            <span x-text="editing ? 'Anuluj' : 'Edytuj profil'"></span>
        </button>
    </div>

    <!-- View mode -->
    <div x-show="!editing" class="space-y-6">
        <div class="flex items-center space-x-6">
            <img src="<?= htmlspecialchars($profileData['avatar_url']) ?>" alt="Zdjęcie profilowe" class="w-24 h-24 rounded-full object-cover border-2 border-gray-200">
            <div>
                <h3 class="text-xl font-semibold"><?= htmlspecialchars($profileData['name']) ?></h3>
                <p class="text-gray-500">Dołączono: <?= $profileData['joined_date'] ?></p>
                <p class="text-gray-600 mt-1"><?= htmlspecialchars($profileData['email']) ?></p>
            </div>
        </div>

        <?php if(!empty($profileData['bio']) || !empty($profileData['location'])): ?>
        <div class="border-t pt-4 mt-4">
            <?php if(!empty($profileData['location'])): ?>
            <div class="flex items-center text-gray-600 mb-2">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                </svg>
                <span><?= htmlspecialchars($profileData['location']) ?></span>
            </div>
            <?php endif; ?>

            <?php if(!empty($profileData['bio'])): ?>
            <div class="text-gray-700 mt-2">
                <p><?= nl2br(htmlspecialchars($profileData['bio'])) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Edit mode -->
    <div x-show="editing" x-cloak>
        <form 
            id="profile-form"
            hx-post="/user/update-profile" 
            hx-trigger="submit"
            hx-indicator="#form-indicator"
            hx-swap="outerHTML"
        >
            <div class="space-y-4">
                <!-- Avatar section -->
                <div class="flex items-center space-x-4">
                    <img src="<?= htmlspecialchars($profileData['avatar_url']) ?>" alt="Zdjęcie profilowe" class="w-20 h-20 rounded-full object-cover border-2 border-gray-200">
                    <div>
                        <label for="avatar_url" class="block text-sm font-medium text-gray-700 mb-1">URL Zdjęcia</label>
                        <input type="url" id="avatar_url" name="avatar_url" value="<?= htmlspecialchars($profileData['avatar_url']) ?>" 
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    </div>
                </div>
                
                <!-- Name field -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Imię i nazwisko</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($profileData['name']) ?>" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Location field -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 mb-1">Lokalizacja</label>
                    <input type="text" id="location" name="location" value="<?= htmlspecialchars($profileData['location']) ?>"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                </div>
                
                <!-- Bio field -->
                <div>
                    <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">O mnie</label>
                    <textarea id="bio" name="bio" rows="4"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
                    ><?= htmlspecialchars($profileData['bio']) ?></textarea>
                </div>
                
                <!-- Submit button -->
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" @click="editing = false" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Anuluj
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Zapisz zmiany
                    </button>
                </div>
            </div>
            
            <div id="form-indicator" class="htmx-indicator flex items-center justify-center py-4">
                <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="ml-2">Zapisywanie zmian...</span>
            </div>
        </form>
    </div>
</div>

<?php
/**
 * User profile partial for dashboard
 */
?>

<div class="profile-container">
    <div class="flex items-center mb-4">
        <div class="w-16 h-16 rounded-full overflow-hidden mr-4 flex-shrink-0">
            <img src="<?= htmlspecialchars($profileData['avatar_url'] ?? '/images/default-avatar.png') ?>" alt="Avatar użytkownika" class="w-full h-full object-cover">
        </div>
        <div>
            <h3 class="text-lg font-medium text-gray-800"><?= htmlspecialchars($profileData['name'] ?? 'Użytkownik') ?></h3>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($profileData['email'] ?? '') ?></p>
        </div>
    </div>

    <div class="mb-4">
        <?php if (!empty($profileData['bio'])): ?>
            <p class="text-sm text-gray-600 mb-2"><?= htmlspecialchars($profileData['bio']) ?></p>
        <?php endif; ?>
        
        <?php if (!empty($profileData['location'])): ?>
            <div class="flex items-center text-sm text-gray-500 mb-1">
                <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                <?= htmlspecialchars($profileData['location']) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($profileData['phone'])): ?>
            <div class="flex items-center text-sm text-gray-500 mb-1">
                <i class="fas fa-phone mr-2 text-gray-400"></i>
                <?= htmlspecialchars($profileData['phone']) ?>
            </div>
        <?php endif; ?>
        
        <div class="flex items-center text-sm text-gray-500">
            <i class="fas fa-calendar mr-2 text-gray-400"></i>
            Konto od: <?= htmlspecialchars($profileData['joined_date'] ?? '') ?>
        </div>
    </div>

    <div class="mt-4">
        <a href="/profile" class="inline-flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
            <i class="fas fa-user-edit mr-1"></i> Zarządzaj profilem
        </a>
    </div>
</div>

<style>
[x-cloak] { display: none !important; }
.htmx-indicator { display: none; }
.htmx-request .htmx-indicator { display: flex; }
.htmx-request.htmx-indicator { display: flex; }
</style>
