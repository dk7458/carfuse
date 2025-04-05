<?php
// Ensure admin access
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /auth/login');
    exit();
}
$pageTitle = "CarFuse - Raporty Systemowe";
$metaDescription = "Panel generowania raportów systemowych CarFuse - twórz i zarządzaj raportami.";
?>
<div class="container mx-auto px-4 py-8">
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Raporty Systemowe 📊</h1>
        <p class="text-gray-600">
            Generuj, przeglądaj i pobieraj raporty dotyczące działania systemu.
        </p>
    </div>

    <!-- Alpine.js data for form validation -->
    <div x-data="reportForm()" class="bg-white rounded-lg shadow-md p-6">
        <form hx-post="/admin/reports/generate"
              hx-indicator="#loadingIndicator"
              hx-target="#reportResult"
              @submit="validateForm($event)"
              class="space-y-4">

            <!-- Typ raportu -->
            <div>
                <label for="report_type" class="block text-sm font-medium text-gray-700 mb-1">Typ raportu</label>
                <select id="report_type" name="report_type" x-model="reportType"
                        class="form-select w-full" @change="showRelevantFilters()">
                    <option value="">Wybierz typ raportu</option>
                    <option value="bookings">Raport rezerwacji</option>
                    <option value="users">Raport użytkowników</option>
                    <option value="revenue">Raport przychodów</option>
                </select>
                <p class="text-red-500 text-sm" x-show="errors.reportType">
                    <span x-text="errors.reportType"></span>
                </p>
            </div>

            <!-- Zakres dat -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="start-date" class="block text-sm font-medium text-gray-700 mb-1">Data początkowa</label>
                    <input type="date" id="start-date" name="date_range[start]" x-model="startDate"
                           class="form-input w-full" max="2099-12-31" />
                    <p class="text-red-500 text-sm" x-show="errors.startDate">
                        <span x-text="errors.startDate"></span>
                    </p>
                </div>
                <div>
                    <label for="end-date" class="block text-sm font-medium text-gray-700 mb-1">Data końcowa</label>
                    <input type="date" id="end-date" name="date_range[end]" x-model="endDate"
                           class="form-input w-full" max="2099-12-31" />
                    <p class="text-red-500 text-sm" x-show="errors.endDate">
                        <span x-text="errors.endDate"></span>
                    </p>
                </div>
            </div>

            <!-- Filtry -->
            <div x-show="reportType !== ''">
                <label class="block text-sm font-medium text-gray-700 mb-1">Filtry</label>
                
                <!-- Filtry dla rezerwacji -->
                <div x-show="reportType === 'bookings'" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-3 border rounded-lg bg-gray-50">
                    <div>
                        <label for="status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status_filter" name="filters[status]" class="form-select w-full">
                            <option value="">Wszystkie statusy</option>
                            <option value="completed">Zakończone</option>
                            <option value="pending">Oczekujące</option>
                            <option value="cancelled">Anulowane</option>
                        </select>
                    </div>
                    <div>
                        <label for="car_type_filter" class="block text-sm font-medium text-gray-700 mb-1">Typ samochodu</label>
                        <select id="car_type_filter" name="filters[car_type]" class="form-select w-full">
                            <option value="">Wszystkie typy</option>
                            <option value="economy">Ekonomiczny</option>
                            <option value="standard">Standardowy</option>
                            <option value="premium">Premium</option>
                        </select>
                    </div>
                </div>
                
                <!-- Filtry dla użytkowników -->
                <div x-show="reportType === 'users'" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-3 border rounded-lg bg-gray-50">
                    <div>
                        <label for="user_status_filter" class="block text-sm font-medium text-gray-700 mb-1">Status użytkownika</label>
                        <select id="user_status_filter" name="filters[user_status]" class="form-select w-full">
                            <option value="">Wszyscy użytkownicy</option>
                            <option value="active">Aktywni</option>
                            <option value="inactive">Nieaktywni</option>
                        </select>
                    </div>
                    <div>
                        <label for="role_filter" class="block text-sm font-medium text-gray-700 mb-1">Rola</label>
                        <select id="role_filter" name="filters[role]" class="form-select w-full">
                            <option value="">Wszystkie role</option>
                            <option value="admin">Administrator</option>
                            <option value="user">Użytkownik</option>
                        </select>
                    </div>
                </div>
                
                <!-- Filtry dla przychodów -->
                <div x-show="reportType === 'revenue'" class="grid grid-cols-1 md:grid-cols-2 gap-4 p-3 border rounded-lg bg-gray-50">
                    <div>
                        <label for="revenue_source" class="block text-sm font-medium text-gray-700 mb-1">Źródło przychodu</label>
                        <select id="revenue_source" name="filters[source]" class="form-select w-full">
                            <option value="">Wszystkie źródła</option>
                            <option value="bookings">Rezerwacje</option>
                            <option value="extras">Dodatki</option>
                            <option value="penalties">Kary</option>
                        </select>
                    </div>
                    <div>
                        <label for="min_amount" class="block text-sm font-medium text-gray-700 mb-1">Minimalna kwota</label>
                        <input type="number" id="min_amount" name="filters[min_amount]" class="form-input w-full" min="0" step="0.01" placeholder="0.00">
                    </div>
                </div>
            </div>

            <!-- Format eksportu -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Format eksportu</label>
                <div class="flex space-x-4">
                    <label class="cursor-pointer flex flex-col items-center">
                        <input type="radio" name="format" value="pdf" x-model="format" class="hidden" />
                        <i class="fas fa-file-pdf text-2xl mb-1"
                           :class="format === 'pdf' ? 'text-red-500' : 'text-gray-400'"></i>
                        <span :class="format === 'pdf' ? 'font-bold' : ''">PDF</span>
                    </label>
                    <label class="cursor-pointer flex flex-col items-center">
                        <input type="radio" name="format" value="csv" x-model="format" class="hidden" />
                        <i class="fas fa-file-csv text-2xl mb-1"
                           :class="format === 'csv' ? 'text-green-500' : 'text-gray-400'"></i>
                        <span :class="format === 'csv' ? 'font-bold' : ''">CSV</span>
                    </label>
                    <label class="cursor-pointer flex flex-col items-center">
                        <input type="radio" name="format" value="excel" x-model="format" class="hidden" />
                        <i class="fas fa-file-excel text-2xl mb-1"
                           :class="format === 'excel' ? 'text-blue-500' : 'text-gray-400'"></i>
                        <span :class="format === 'excel' ? 'font-bold' : ''">Excel</span>
                    </label>
                </div>
                <p class="text-red-500 text-sm" x-show="errors.format">
                    <span x-text="errors.format"></span>
                </p>
            </div>

            <!-- Submit -->
            <div class="flex items-center space-x-2">
                <div id="loadingIndicator" class="htmx-indicator">
                    <i class="fas fa-spinner fa-spin"></i> Generowanie...
                </div>
                <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                    Generuj Raport
                </button>
            </div>
        </form>

        <!-- Wynik raportu -->
        <div id="reportResult" class="mt-4"></div>
    </div>

    <!-- Sekcja poprzednich raportów -->
    <div class="bg-white rounded-lg shadow-md p-6 mt-6">
        <h2 class="text-lg font-bold mb-3">Poprzednie Raporty</h2>
        <div id="recent-reports" hx-get="/admin/reports/recent" hx-trigger="load">
            <!-- Wyświetlanie poprzednich raportów po załadowaniu -->
            <div class="animate-pulse">
                <div class="h-6 bg-gray-200 mb-2 rounded"></div>
                <div class="h-6 bg-gray-200 mb-2 rounded"></div>
            </div>
        </div>
    </div>
</div>

<script>
function reportForm() {
    return {
        reportType: '',
        startDate: '',
        endDate: '',
        format: '',
        errors: {},
        
        showRelevantFilters() {
            // Additional logic can be added here if needed
        },
        
        validateForm(e) {
            this.errors = {};
            
            // Validate required fields
            if (!this.reportType) {
                this.errors.reportType = 'Wybierz typ raportu.';
            }
            if (!this.startDate) {
                this.errors.startDate = 'Wybierz datę początkową.';
            }
            if (!this.endDate) {
                this.errors.endDate = 'Wybierz datę końcową.';
            }
            if (!this.format) {
                this.errors.format = 'Wybierz format eksportu.';
            }
            
            // Validate date range
            if (this.startDate && this.endDate) {
                const start = new Date(this.startDate);
                const end = new Date(this.endDate);
                
                if (start > end) {
                    this.errors.endDate = 'Data końcowa musi być późniejsza niż data początkowa.';
                }
            }
            
            // Prevent form submission if there are errors
            if (Object.keys(this.errors).length) {
                e.preventDefault();
                return false;
            }
            
            // Display loading state
            document.getElementById('loadingIndicator').classList.remove('hidden');
        }
    };
}

// Event handler for successful report generation
document.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'reportResult') {
        const response = event.detail.xhr.response;
        try {
            // Check if response is JSON
            const jsonResponse = JSON.parse(response);
            if (jsonResponse.status === 'error') {
                document.getElementById('reportResult').innerHTML = 
                    `<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mt-4">
                        <p>${jsonResponse.message}</p>
                    </div>`;
            }
        } catch (e) {
            // If not JSON, it's likely the file download response
            console.log('Report generated successfully');
        }
    }
});
</script>
