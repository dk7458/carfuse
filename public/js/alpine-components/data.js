/**
 * CarFuse Alpine.js Data Patterns
 * Reusable data patterns for bookings, users, and payments
 */

document.addEventListener('alpine:init', () => {
    // User Management Component
    Alpine.data('userManagement', () => ({
        users: [],
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        itemsPerPage: 10,
        filterRole: 'all',
        filterStatus: 'all',
        searchQuery: '',
        loading: false,
        showNewUserModal: false,
        showPassword: false,
        newUser: {
            name: '',
            surname: '',
            email: '',
            password: '',
            role: 'user'
        },
        isSubmitting: false,
        
        init() {
            this.loadUsers();
        },
        
        get startItem() {
            return Math.min((this.currentPage - 1) * this.itemsPerPage + 1, this.totalItems);
        },
        
        get endItem() {
            return Math.min(this.currentPage * this.itemsPerPage, this.totalItems);
        },
        
        loadUsers() {
            this.loading = true;
            
            // Prepare URL with query parameters
            const params = new URLSearchParams({
                page: this.currentPage,
                role: this.filterRole,
                status: this.filterStatus,
                search: this.searchQuery
            });
            
            // Use HTMX to load users
            htmx.ajax('GET', `/admin/api/users?${params.toString()}`, {
                target: '#users-container',
                swap: 'innerHTML',
                headers: {
                    'HX-Request': 'true'
                }
            }).then((response) => {
                this.loading = false;
                
                // Check response headers for pagination data
                const paginationData = JSON.parse(response.headers.get('X-Pagination') || '{}');
                
                if (paginationData) {
                    this.totalPages = paginationData.totalPages || 1;
                    this.totalItems = paginationData.totalItems || 0;
                }
                
                // Show or hide "no users" message
                const hasUsers = document.querySelectorAll('#users-container tr').length > 0;
                document.getElementById('no-users-message')?.classList.toggle('hidden', hasUsers);
            }).catch(() => {
                this.loading = false;
                // Dispatch toast event
                window.dispatchEvent(new CustomEvent('show-toast', {
                    detail: { title: 'Błąd', message: 'Wystąpił błąd podczas ładowania listy użytkowników.', type: 'error' }
                }));
            });
        },
        
        reloadUserList() {
            this.loadUsers();
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadUsers();
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadUsers();
            }
        },
    }));
    
    // Booking Management Component
    Alpine.data('bookingManagement', () => ({
        bookings: [],
        currentPage: 1,
        totalPages: 1,
        itemsPerPage: 10,
        filterStatus: 'all',
        searchQuery: '',
        fromDate: '',
        toDate: '',
        loading: false,
        showDetails: {},
        
        init() {
            this.loadBookings();
        },
        
        loadBookings() {
            this.loading = true;
            
            // Prepare URL with query parameters
            const params = new URLSearchParams({
                page: this.currentPage,
                status: this.filterStatus,
                search: this.searchQuery,
                from_date: this.fromDate,
                to_date: this.toDate
            });
            
            fetch(`/api/bookings?${params.toString()}`)
                .then(response => {
                    // Get pagination info from headers
                    const totalPages = parseInt(response.headers.get('X-Total-Pages') || '1');
                    
                    return response.json().then(data => {
                        this.totalPages = totalPages;
                        return data;
                    });
                })
                .then(data => {
                    this.bookings = data.bookings;
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error loading bookings:', error);
                    this.loading = false;
                    // Dispatch toast event
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { title: 'Błąd', message: 'Wystąpił błąd podczas ładowania rezerwacji.', type: 'error' }
                    }));
                });
        },
        
        toggleDetails(id) {
            this.showDetails = {
                ...this.showDetails,
                [id]: !this.showDetails[id]
            };
        },
        
        nextPage() {
            if (this.currentPage < this.totalPages) {
                this.currentPage++;
                this.loadBookings();
            }
        },
        
        prevPage() {
            if (this.currentPage > 1) {
                this.currentPage--;
                this.loadBookings();
            }
        },
        
        getStatusClass(status) {
            const statusClasses = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'confirmed': 'bg-blue-100 text-blue-800',
                'active': 'bg-green-100 text-green-800',
                'completed': 'bg-purple-100 text-purple-800',
                'canceled': 'bg-red-100 text-red-800'
            };
            
            return statusClasses[status] || 'bg-gray-100 text-gray-800';
        },
        
        formatDate(dateString) {
            const date = new Date(dateString);
            return new Intl.DateTimeFormat('pl-PL', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).format(date);
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('pl-PL', {
                style: 'currency',
                currency: 'PLN'
            }).format(amount);
        }
    }));
    
    // Payment Processing Component
    Alpine.data('paymentSystem', () => ({
        currentPage: 1,
        limit: 10, 
        type: 'all', 
        sortBy: 'date', 
        sortDir: 'desc',
        loading: false,
        showDetails: false,
        selectedTransaction: null,
        
        fetchTransactions() {
            this.loading = true;
            
            const url = '/payment/transactions';
            const params = new URLSearchParams({
                page: this.currentPage,
                limit: this.limit,
                type: this.type,
                sort_by: this.sortBy,
                sort_dir: this.sortDir
            });
            
            htmx.ajax('GET', `${url}?${params.toString()}`, {
                target: '#transactions-list',
                swap: 'innerHTML',
                headers: {
                    'HX-Request': 'true'
                }
            }).then(() => {
                this.loading = false;
                
                // Show no transactions message if needed
                const transactionItems = document.querySelectorAll('#transactions-list .transaction-row');
                if (transactionItems.length === 0) {
                    document.getElementById('no-transactions')?.classList.remove('hidden');
                } else {
                    document.getElementById('no-transactions')?.classList.add('hidden');
                }
            });
        },
        
        viewTransactionDetails(transactionId) {
            this.loading = true;
            
            fetch(`/payment/${transactionId}/details`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        this.selectedTransaction = data.data.details;
                        this.showDetails = true;
                    } else {
                        // Dispatch toast event
                        window.dispatchEvent(new CustomEvent('show-toast', {
                            detail: { title: 'Błąd', message: 'Nie udało się pobrać szczegółów transakcji: ' + data.message, type: 'error' }
                        }));
                    }
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error fetching transaction details:', error);
                    // Dispatch toast event
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: { title: 'Błąd', message: 'Wystąpił błąd podczas pobierania szczegółów transakcji.', type: 'error' }
                    }));
                    this.loading = false;
                });
        }
    }));
});
