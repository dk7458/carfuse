/**
 * CarFuse Validation Rules
 * Provides reusable validation rules for form validation
 */
(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    if (!CarFuse.forms) {
        CarFuse.forms = {};
    }
    
    /**
     * Standard validation rules
     */
    const rules = {
        // Basic validations
        required: (value) => value !== undefined && value !== null && String(value).trim() !== '',
        email: (value) => !value || /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value),
        url: (value) => !value || /^(https?:\/\/)?([\da-z.-]+)\.([a-z.]{2,6})([/\w.-]*)*\/?$/.test(value),
        
        // Length validations
        min: (value, params) => !value || String(value).length >= Number(params),
        max: (value, params) => !value || String(value).length <= Number(params),
        length: (value, params) => !value || String(value).length === Number(params),
        between: (value, params) => {
            if (!value) return true;
            const [min, max] = params.split(',').map(Number);
            return String(value).length >= min && String(value).length <= max;
        },
        
        // Type validations
        numeric: (value) => !value || /^-?\d*\.?\d+$/.test(value),
        integer: (value) => !value || /^-?\d+$/.test(value),
        decimal: (value) => !value || /^-?\d*\.\d+$/.test(value),
        alpha: (value) => !value || /^[a-zA-Z]+$/.test(value),
        alphanumeric: (value) => !value || /^[a-zA-Z0-9]+$/.test(value),
        
        // Range validations
        min_value: (value, params) => !value || Number(value) >= Number(params),
        max_value: (value, params) => !value || Number(value) <= Number(params),
        between_values: (value, params) => {
            if (!value) return true;
            const [min, max] = params.split(',').map(Number);
            return Number(value) >= min && Number(value) <= max;
        },
        
        // Format validations
        regex: (value, params) => {
            if (!value) return true;
            try {
                const flags = params.includes('/') ? params.split('/').pop() : '';
                const pattern = params.includes('/')
                    ? params.substring(1, params.lastIndexOf('/'))
                    : params;
                
                return new RegExp(pattern, flags).test(value);
            } catch (e) {
                console.error('Invalid regex pattern', { pattern: params, error: e });
                return false;
            }
        },
        
        // Date validations
        date: (value) => {
            if (!value) return true;
            const date = new Date(value);
            return !isNaN(date.getTime());
        },
        before: (value, params) => {
            if (!value) return true;
            const date = new Date(value);
            const compareDate = params === 'today' ? new Date() : new Date(params);
            return !isNaN(date.getTime()) && !isNaN(compareDate.getTime()) && date < compareDate;
        },
        after: (value, params) => {
            if (!value) return true;
            const date = new Date(value);
            const compareDate = params === 'today' ? new Date() : new Date(params);
            return !isNaN(date.getTime()) && !isNaN(compareDate.getTime()) && date > compareDate;
        },
        
        // Special validations for PL
        pesel: (value) => {
            if (!value) return true;
            
            // Basic format check
            if (value.length !== 11 || !/^\d{11}$/.test(value)) {
                return false;
            }
            
            // Check control digit
            const weights = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3, 1];
            let sum = 0;
            
            for (let i = 0; i < 11; i++) {
                sum += parseInt(value.charAt(i), 10) * weights[i];
            }
            
            return sum % 10 === 0;
        },
        nip: (value) => {
            if (!value) return true;
            
            // Remove spaces and dashes
            value = value.replace(/[\s-]/g, '');
            
            // Basic format check
            if (value.length !== 10 || !/^\d{10}$/.test(value)) {
                return false;
            }
            
            // Check control digit
            const weights = [6, 5, 7, 2, 3, 4, 5, 6, 7];
            let sum = 0;
            
            for (let i = 0; i < 9; i++) {
                sum += parseInt(value.charAt(i), 10) * weights[i];
            }
            
            const checkDigit = sum % 11;
            
            // Check digit should never be 10, always 0-9
            if (checkDigit === 10) {
                return false;
            }
            
            return checkDigit === parseInt(value.charAt(9), 10);
        },
        postal_code: (value) => !value || /^\d{2}-\d{3}$/.test(value),
        phone: (value) => {
            if (!value) return true;
            value = value.replace(/[\s-]/g, '');
            return /^(?:\+?48)?[0-9]{9}$/.test(value);
        },
        
        // Comparison validations
        same: (value, params, form) => {
            if (!value) return true;
            const field = form.querySelector(`[name="${params}"]`);
            return field ? value === field.value : false;
        },
        different: (value, params, form) => {
            if (!value) return true;
            const field = form.querySelector(`[name="${params}"]`);
            return field ? value !== field.value : true;
        },
        
        // File validations
        file: (value) => !value || value instanceof FileList || value instanceof File,
        image: (value) => {
            if (!value || !(value instanceof FileList || value instanceof File)) return true;
            const files = value instanceof FileList ? value : [value];
            return Array.from(files).every(file => file.type.startsWith('image/'));
        },
        mimes: (value, params) => {
            if (!value || !(value instanceof FileList || value instanceof File)) return true;
            const allowedTypes = params.split(',');
            const files = value instanceof FileList ? value : [value];
            
            return Array.from(files).every(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                return allowedTypes.includes(ext);
            });
        },
        max_size: (value, params) => {
            if (!value || !(value instanceof FileList || value instanceof File)) return true;
            const maxSize = Number(params) * 1024; // Convert to bytes
            const files = value instanceof FileList ? value : [value];
            
            return Array.from(files).every(file => file.size <= maxSize);
        },
        
        // Password validations
        password_strength: (value, params = 'medium') => {
            if (!value) return true;
            
            // Password strength levels
            const patterns = {
                weak: /^.{6,}$/, // At least 6 characters
                medium: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/, // At least 8 chars with lowercase, uppercase, and digit
                strong: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*(),.?":{}|<>]).{10,}$/ // At least 10 chars with lowercase, uppercase, digit, and special char
            };
            
            return patterns[params] ? patterns[params].test(value) : true;
        },
        
        // Address validations
        zipcode: (value) => !value || /^\d{5}(-\d{4})?$/.test(value), // US zipcode
        
        // Boolean validations
        accepted: (value) => value === true || value === 'true' || value === 'yes' || value === '1' || value === 1,
        
        // Credit card validations
        credit_card: (value) => {
            if (!value) return true;
            
            // Remove spaces and dashes
            value = value.replace(/[\s-]/g, '');
            
            // Check if only contains digits
            if (!/^\d+$/.test(value)) return false;
            
            // Luhn algorithm (checksum) validation
            let sum = 0;
            let double = false;
            
            for (let i = value.length - 1; i >= 0; i--) {
                let digit = parseInt(value.charAt(i), 10);
                
                if (double) {
                    digit *= 2;
                    if (digit > 9) digit -= 9;
                }
                
                sum += digit;
                double = !double;
            }
            
            return sum % 10 === 0;
        },
        
        // IP validation
        ip: (value) => {
            if (!value) return true;
            
            const ipv4Pattern = /^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/;
            const ipv6Pattern = /^([0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}$/;
            
            // Check IPv4
            if (ipv4Pattern.test(value)) {
                const parts = value.split('.');
                return parts.every(part => parseInt(part, 10) <= 255);
            }
            
            // Check IPv6 (simplified)
            return ipv6Pattern.test(value);
        }
    };
    
    // Register with CarFuse
    CarFuse.forms.rules = rules;
})();
