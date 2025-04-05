/**
 * CarFuse Date Utilities Component
 * Provides comprehensive date handling functionality with Polish locale support
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        console.error('CarFuse global object is not defined.');
        return;
    }
    
    const CarFuse = window.CarFuse;
    
    // Check if Date Utils is already initialized
    if (CarFuse.dateUtils) {
        console.warn('CarFuse Date Utils component already initialized.');
        return;
    }
    
    // Polish language configuration
    const POLISH_CONFIG = {
        locale: 'pl-PL',
        monthNames: [
            'Styczeń', 'Luty', 'Marzec', 'Kwiecień', 'Maj', 'Czerwiec', 
            'Lipiec', 'Sierpień', 'Wrzesień', 'Październik', 'Listopad', 'Grudzień'
        ],
        monthNamesShort: [
            'Sty', 'Lut', 'Mar', 'Kwi', 'Maj', 'Cze', 
            'Lip', 'Sie', 'Wrz', 'Paź', 'Lis', 'Gru'
        ],
        dayNames: [
            'Niedziela', 'Poniedziałek', 'Wtorek', 'Środa', 
            'Czwartek', 'Piątek', 'Sobota'
        ],
        dayNamesShort: ['Nd', 'Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob'],
        firstDay: 1, // Monday is the first day of the week in Poland
        dateFormat: 'DD.MM.YYYY',
        timeFormat: 'HH:mm',
        dateTimeFormat: 'DD.MM.YYYY HH:mm',
        relativeTime: {
            future: 'za %s',
            past: '%s temu',
            s: 'kilka sekund',
            ss: '%d sekund',
            m: 'minutę',
            mm: '%d minut',
            h: 'godzinę',
            hh: '%d godzin',
            d: 'dzień',
            dd: '%d dni',
            w: 'tydzień',
            ww: '%d tygodni',
            M: 'miesiąc',
            MM: '%d miesięcy',
            y: 'rok',
            yy: '%d lat'
        }
    };
    
    CarFuse.dateUtils = {
        /**
         * Initialize Date Utils functionalities
         */
        init: function() {
            this.log('Initializing Date Utils component');
            this.setupInputMasking();
            this.log('Date Utils component initialized');
        },
        
        /**
         * Log a message to the console if debug mode is enabled
         * @param {string} message - Message to log
         * @param {*} [data] - Optional data to include in log
         */
        log: function(message, data) {
            if (CarFuse.config.debug) {
                console.log(`[CarFuse Date Utils] ${message}`, data || '');
            }
        },
        
        /**
         * Parse a date string into a JavaScript Date object
         * @param {string} dateStr - Date string to parse
         * @param {string} [format] - Expected format of the date string
         * @returns {Date|null} Parsed date or null if invalid
         */
        parseDate: function(dateStr, format) {
            if (!dateStr) return null;
            
            // Try to parse as ISO format first
            const isoDate = new Date(dateStr);
            if (!isNaN(isoDate)) return isoDate;
            
            // Handle Polish format DD.MM.YYYY
            const polishPattern = /^(\d{1,2})\.(\d{1,2})\.(\d{4})$/;
            if (polishPattern.test(dateStr)) {
                const matches = dateStr.match(polishPattern);
                return new Date(matches[3], matches[2] - 1, matches[1]);
            }
            
            // Handle other formats with separators
            const pattern = /^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/;
            if (pattern.test(dateStr)) {
                const matches = dateStr.match(pattern);
                
                // Check if it's MM/DD/YYYY or DD/MM/YYYY based on format param
                if (format === 'MM/DD/YYYY') {
                    return new Date(matches[3], matches[1] - 1, matches[2]);
                } else {
                    return new Date(matches[3], matches[2] - 1, matches[1]);
                }
            }
            
            return null;
        },
        
        /**
         * Format a date according to specified format or Polish locale
         * @param {Date|string} date - Date to format
         * @param {string} [format] - Format pattern
         * @returns {string} Formatted date string
         */
        formatDate: function(date, format) {
            if (!date) return '';
            
            const dateObj = date instanceof Date ? date : this.parseDate(date);
            if (!dateObj || isNaN(dateObj)) return '';
            
            // Use Intl.DateTimeFormat for standard formatting
            if (!format) {
                return new Intl.DateTimeFormat(POLISH_CONFIG.locale, { 
                    year: 'numeric', 
                    month: '2-digit', 
                    day: '2-digit' 
                }).format(dateObj);
            }
            
            // Custom format implementation for specific patterns
            return format.replace(/Y{4}|Y{2}|M{2}|D{2}|H{2}|m{2}|s{2}/g, (match) => {
                switch (match) {
                    case 'YYYY': return dateObj.getFullYear();
                    case 'YY': return dateObj.getFullYear().toString().slice(-2);
                    case 'MM': return String(dateObj.getMonth() + 1).padStart(2, '0');
                    case 'DD': return String(dateObj.getDate()).padStart(2, '0');
                    case 'HH': return String(dateObj.getHours()).padStart(2, '0');
                    case 'mm': return String(dateObj.getMinutes()).padStart(2, '0');
                    case 'ss': return String(dateObj.getSeconds()).padStart(2, '0');
                    default: return match;
                }
            });
        },
        
        /**
         * Format a date and time
         * @param {Date|string} date - Date to format
         * @returns {string} Formatted date and time
         */
        formatDateTime: function(date) {
            if (!date) return '';
            
            const dateObj = date instanceof Date ? date : this.parseDate(date);
            if (!dateObj || isNaN(dateObj)) return '';
            
            return new Intl.DateTimeFormat(POLISH_CONFIG.locale, { 
                year: 'numeric', 
                month: '2-digit', 
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit'
            }).format(dateObj);
        },
        
        /**
         * Format a relative time (e.g., "5 minut temu")
         * @param {Date|string} date - Date to format relatively
         * @returns {string} Relative time string
         */
        formatRelativeTime: function(date) {
            if (!date) return '';
            
            const dateObj = date instanceof Date ? date : this.parseDate(date);
            if (!dateObj || isNaN(dateObj)) return '';
            
            const now = new Date();
            const diffMs = now - dateObj;
            const diffSecs = Math.round(diffMs / 1000);
            const diffMins = Math.round(diffSecs / 60);
            const diffHours = Math.round(diffMins / 60);
            const diffDays = Math.round(diffHours / 24);
            const diffMonths = Math.round(diffDays / 30);
            const diffYears = Math.round(diffDays / 365);
            
            // Handle past dates
            if (diffMs > 0) {
                if (diffSecs < 60) {
                    return POLISH_CONFIG.relativeTime.past.replace('%s', POLISH_CONFIG.relativeTime.s);
                } else if (diffMins < 60) {
                    return this._formatRelativeValue(diffMins, 'm', 'mm');
                } else if (diffHours < 24) {
                    return this._formatRelativeValue(diffHours, 'h', 'hh');
                } else if (diffDays === 1) {
                    return 'wczoraj';
                } else if (diffDays < 7) {
                    return this._formatRelativeValue(diffDays, 'd', 'dd');
                } else if (diffDays < 30) {
                    return this._formatRelativeValue(Math.floor(diffDays / 7), 'w', 'ww');
                } else if (diffMonths < 12) {
                    return this._formatRelativeValue(diffMonths, 'M', 'MM');
                } else {
                    return this._formatRelativeValue(diffYears, 'y', 'yy');
                }
            }
            
            // Handle future dates
            const absDiffMs = Math.abs(diffMs);
            const absDiffSecs = Math.round(absDiffMs / 1000);
            const absDiffMins = Math.round(absDiffSecs / 60);
            const absDiffHours = Math.round(absDiffMins / 60);
            const absDiffDays = Math.round(absDiffHours / 24);
            const absDiffMonths = Math.round(absDiffDays / 30);
            const absDiffYears = Math.round(absDiffDays / 365);
            
            if (absDiffSecs < 60) {
                return POLISH_CONFIG.relativeTime.future.replace('%s', POLISH_CONFIG.relativeTime.s);
            } else if (absDiffMins < 60) {
                return this._formatRelativeValueFuture(absDiffMins, 'm', 'mm');
            } else if (absDiffHours < 24) {
                return this._formatRelativeValueFuture(absDiffHours, 'h', 'hh');
            } else if (absDiffDays === 1) {
                return 'jutro';
            } else if (absDiffDays < 7) {
                return this._formatRelativeValueFuture(absDiffDays, 'd', 'dd');
            } else if (absDiffDays < 30) {
                return this._formatRelativeValueFuture(Math.floor(absDiffDays / 7), 'w', 'ww');
            } else if (absDiffMonths < 12) {
                return this._formatRelativeValueFuture(absDiffMonths, 'M', 'MM');
            } else {
                return this._formatRelativeValueFuture(absDiffYears, 'y', 'yy');
            }
        },
        
        /**
         * Helper for formatting relative time values (past)
         * @private
         */
        _formatRelativeValue: function(value, singleKey, pluralKey) {
            const format = value === 1 ? POLISH_CONFIG.relativeTime[singleKey] : 
                                        POLISH_CONFIG.relativeTime[pluralKey].replace('%d', value);
            return POLISH_CONFIG.relativeTime.past.replace('%s', format);
        },
        
        /**
         * Helper for formatting relative time values (future)
         * @private
         */
        _formatRelativeValueFuture: function(value, singleKey, pluralKey) {
            const format = value === 1 ? POLISH_CONFIG.relativeTime[singleKey] : 
                                        POLISH_CONFIG.relativeTime[pluralKey].replace('%d', value);
            return POLISH_CONFIG.relativeTime.future.replace('%s', format);
        },
        
        /**
         * Add specified number of days to a date
         * @param {Date|string} date - Original date
         * @param {number} days - Number of days to add (negative to subtract)
         * @returns {Date} New date object
         */
        addDays: function(date, days) {
            const dateObj = date instanceof Date ? new Date(date) : this.parseDate(date);
            if (!dateObj || isNaN(dateObj)) return null;
            
            dateObj.setDate(dateObj.getDate() + days);
            return dateObj;
        },
        
        /**
         * Add specified number of weeks to a date
         * @param {Date|string} date - Original date
         * @param {number} weeks - Number of weeks to add (negative to subtract)
         * @returns {Date} New date object
         */
        addWeeks: function(date, weeks) {
            return this.addDays(date, weeks * 7);
        },
        
        /**
         * Add specified number of months to a date
         * @param {Date|string} date - Original date
         * @param {number} months - Number of months to add (negative to subtract)
         * @returns {Date} New date object
         */
        addMonths: function(date, months) {
            const dateObj = date instanceof Date ? new Date(date) : this.parseDate(date);
            if (!dateObj || isNaN(dateObj)) return null;
            
            const currentDate = dateObj.getDate();
            dateObj.setMonth(dateObj.getMonth() + months);
            
            // Handle month length differences (e.g., Jan 31 + 1 month)
            if (dateObj.getDate() !== currentDate) {
                dateObj.setDate(0); // Go to last day of previous month
            }
            
            return dateObj;
        },
        
        /**
         * Compare dates to check if they represent the same day
         * @param {Date|string} dateA - First date
         * @param {Date|string} dateB - Second date
         * @returns {boolean} True if dates are the same day
         */
        isSameDay: function(dateA, dateB) {
            const a = dateA instanceof Date ? dateA : this.parseDate(dateA);
            const b = dateB instanceof Date ? dateB : this.parseDate(dateB);
            
            if (!a || !b || isNaN(a) || isNaN(b)) return false;
            
            return a.getFullYear() === b.getFullYear() && 
                   a.getMonth() === b.getMonth() && 
                   a.getDate() === b.getDate();
        },
        
        /**
         * Check if date A is before date B
         * @param {Date|string} dateA - First date
         * @param {Date|string} dateB - Second date
         * @returns {boolean} True if dateA is before dateB
         */
        isBefore: function(dateA, dateB) {
            const a = dateA instanceof Date ? dateA : this.parseDate(dateA);
            const b = dateB instanceof Date ? dateB : this.parseDate(dateB);
            
            if (!a || !b || isNaN(a) || isNaN(b)) return false;
            
            return a < b;
        },
        
        /**
         * Check if date is between start and end dates
         * @param {Date|string} date - Date to check
         * @param {Date|string} start - Start date
         * @param {Date|string} end - End date
         * @returns {boolean} True if date is between start and end
         */
        isBetween: function(date, start, end) {
            const d = date instanceof Date ? date : this.parseDate(date);
            const s = start instanceof Date ? start : this.parseDate(start);
            const e = end instanceof Date ? end : this.parseDate(end);
            
            if (!d || !s || !e || isNaN(d) || isNaN(s) || isNaN(e)) return false;
            
            return d >= s && d <= e;
        },
        
        /**
         * Calculate the difference in days between two dates
         * @param {Date|string} dateA - First date
         * @param {Date|string} dateB - Second date
         * @returns {number} Number of days between the dates
         */
        getDaysDifference: function(dateA, dateB) {
            const a = dateA instanceof Date ? dateA : this.parseDate(dateA);
            const b = dateB instanceof Date ? dateB : this.parseDate(dateB);
            
            if (!a || !b || isNaN(a) || isNaN(b)) return 0;
            
            // Convert to UTC to avoid DST issues
            const utcA = Date.UTC(a.getFullYear(), a.getMonth(), a.getDate());
            const utcB = Date.UTC(b.getFullYear(), b.getMonth(), b.getDate());
            
            return Math.floor((utcB - utcA) / (1000 * 60 * 60 * 24));
        },
        
        /**
         * Get the name of the month in Polish
         * @param {number|Date} month - Month number (0-11) or Date object
         * @param {boolean} [short=false] - Whether to return short name
         * @returns {string} Month name in Polish
         */
        getMonthName: function(month, short = false) {
            let monthIndex;
            
            if (month instanceof Date) {
                monthIndex = month.getMonth();
            } else {
                monthIndex = parseInt(month, 10);
            }
            
            if (isNaN(monthIndex) || monthIndex < 0 || monthIndex > 11) {
                return '';
            }
            
            return short ? 
                POLISH_CONFIG.monthNamesShort[monthIndex] : 
                POLISH_CONFIG.monthNames[monthIndex];
        },
        
        /**
         * Get the name of the day of the week in Polish
         * @param {number|Date} day - Day number (0-6, Sunday is 0) or Date object
         * @param {boolean} [short=false] - Whether to return short name
         * @returns {string} Day name in Polish
         */
        getDayName: function(day, short = false) {
            let dayIndex;
            
            if (day instanceof Date) {
                dayIndex = day.getDay();
            } else {
                dayIndex = parseInt(day, 10);
            }
            
            if (isNaN(dayIndex) || dayIndex < 0 || dayIndex > 6) {
                return '';
            }
            
            return short ? 
                POLISH_CONFIG.dayNamesShort[dayIndex] : 
                POLISH_CONFIG.dayNames[dayIndex];
        },
        
        /**
         * Generate calendar data for a specific month
         * @param {number} year - Year
         * @param {number} month - Month (0-11)
         * @returns {Array} Array of weeks, each with days
         */
        generateCalendarMonth: function(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            
            // Get first day of month
            let firstDayOfWeek = firstDay.getDay();
            // In Poland, first day is Monday (1), so adjust if it's Sunday (0)
            if (firstDayOfWeek === 0) firstDayOfWeek = 7;
            
            const daysInMonth = lastDay.getDate();
            const weeks = [];
            let days = [];
            
            // Add days from previous month
            const prevMonth = month === 0 ? 11 : month - 1;
            const prevMonthYear = month === 0 ? year - 1 : year;
            const daysInPrevMonth = new Date(prevMonthYear, prevMonth + 1, 0).getDate();
            
            // In Polish calendar, week starts with Monday (1), not Sunday (0)
            const startPadding = (firstDayOfWeek - 1 + 7) % 7;
            
            for (let i = startPadding - 1; i >= 0; i--) {
                days.push({
                    day: daysInPrevMonth - i,
                    month: prevMonth,
                    year: prevMonthYear,
                    isCurrentMonth: false
                });
            }
            
            // Add days from current month
            for (let i = 1; i <= daysInMonth; i++) {
                days.push({
                    day: i,
                    month: month,
                    year: year,
                    isCurrentMonth: true
                });
                
                if (days.length === 7) {
                    weeks.push([...days]);
                    days = [];
                }
            }
            
            // Add days from next month
            if (days.length > 0) {
                const nextMonth = month === 11 ? 0 : month + 1;
                const nextMonthYear = month === 11 ? year + 1 : year;
                
                let dayCounter = 1;
                while (days.length < 7) {
                    days.push({
                        day: dayCounter++,
                        month: nextMonth,
                        year: nextMonthYear,
                        isCurrentMonth: false
                    });
                }
                
                weeks.push([...days]);
            }
            
            return weeks;
        },
        
        /**
         * Setup input masking for date inputs
         */
        setupInputMasking: function() {
            // Find all date inputs
            const dateInputs = document.querySelectorAll('input[data-type="date"]');
            
            dateInputs.forEach(input => {
                input.addEventListener('input', (e) => {
                    let value = e.target.value.replace(/\D/g, '');
                    let formattedValue = '';
                    
                    // Format as DD.MM.YYYY
                    if (value.length > 0) {
                        formattedValue = value.substring(0, 2);
                    }
                    if (value.length > 2) {
                        formattedValue += '.' + value.substring(2, 4);
                    }
                    if (value.length > 4) {
                        formattedValue += '.' + value.substring(4, 8);
                    }
                    
                    e.target.value = formattedValue;
                });
                
                // Validate date on blur
                input.addEventListener('blur', (e) => {
                    const value = e.target.value;
                    if (value) {
                        const date = this.parseDate(value);
                        if (!date || isNaN(date)) {
                            e.target.classList.add('border-red-500');
                            e.target.setCustomValidity('Niepoprawny format daty. Użyj DD.MM.RRRR');
                        } else {
                            e.target.classList.remove('border-red-500');
                            e.target.setCustomValidity('');
                            // Normalize to standard format
                            e.target.value = this.formatDate(date, 'DD.MM.YYYY');
                        }
                    }
                });
            });
        }
    };
    
    // Initialize Date Utils component
    CarFuse.dateUtils.init();
})();
