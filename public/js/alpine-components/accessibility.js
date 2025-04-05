/**
 * CarFuse Alpine.js Accessibility Enhancements
 * Keyboard navigation utilities
 */

document.addEventListener('alpine:init', () => {
    // Keyboard navigation utilities
    Alpine.data('keyboardNav', () => ({
        // Add keyboard navigation to a list of items
        initKeyboardNav(selector, options = {}) {
            const container = options.container || this.$el;
            const items = container.querySelectorAll(selector);
            
            items.forEach((item, index) => {
                item.setAttribute('tabindex', '0');
                
                item.addEventListener('keydown', (e) => {
                    const lastIndex = items.length - 1;
                    let nextIndex = null;
                    
                    switch(e.key) {
                        case 'ArrowDown':
                            nextIndex = index < lastIndex ? index + 1 : 0;
                            e.preventDefault();
                            break;
                        case 'ArrowUp':
                            nextIndex = index > 0 ? index - 1 : lastIndex;
                            e.preventDefault();
                            break;
                        case 'Home':
                            nextIndex = 0;
                            e.preventDefault();
                            break;
                        case 'End':
                            nextIndex = lastIndex;
                            e.preventDefault();
                            break;
                    }
                    
                    if (nextIndex !== null) {
                        items[nextIndex].focus();
                    }
                });
            });
        }
    }));
});
