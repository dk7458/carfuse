# CarFuse Component Showcase

This showcase provides examples of using components in the CarFuse ecosystem, demonstrating proper implementation and common patterns.

## Core Components

### Base Component

The foundation for all components in the system.

```javascript
const myComponent = new CarFuse.BaseComponent('customComponent', {
    debug: true,
    autoMount: true
});

myComponent.init()
    .then(() => {
        console.log('Component initialized!');
    });
```

## UI Components

### Modal Component

Example of a modal component using the standardized architecture:

```html
<!-- HTML Structure -->
<div data-component="modal" id="example-modal" data-options='{"closeOnEscape": true}'>
    <div class="modal-content">
        <h2>Modal Title</h2>
        <p>Modal content goes here</p>
        <button data-modal-close>Close</button>
    </div>
</div>

<!-- Trigger button -->
<button data-modal-trigger data-modal-target="example-modal">
    Open Modal
</button>

<!-- JavaScript Usage -->
<script>
// Open modal programmatically
CarFuse.modal.openById('example-modal');

// Listen for events
document.addEventListener('carfuse:modal:opened', (event) => {
    console.log('Modal opened:', event.detail.modalId);
});
</script>
```

### Dropdown Component

Example of a dropdown component:

```html
<!-- HTML Structure -->
<div data-component="dropdown" id="my-dropdown" data-options='{"position": "bottom", "closeOnSelect": true}'>
    <button data-dropdown-trigger>Click me</button>
    <div data-dropdown-content>
        <a href="#" data-dropdown-item data-value="option1">Option 1</a>
        <a href="#" data-dropdown-item data-value="option2">Option 2</a>
        <a href="#" data-dropdown-item data-value="option3">Option 3</a>
    </div>
</div>

<!-- JavaScript Usage -->
<script>
// Listen for item selection
document.addEventListener('carfuse:dropdown:itemSelected', (event) => {
    console.log('Selected item:', event.detail.value);
});

// Open dropdown programmatically
document.getElementById('my-dropdown').carfuseComponent.open();
</script>
```

## Alpine.js Components

### Alpine Modal Component

Example of using the Alpine.js modal component:

```html
<!-- HTML Structure -->
<div x-data="modal({ closeOnOverlayClick: true })" id="alpine-modal">
    <button @click="open">Open Modal</button>
    
    <!-- Modal content -->
    <div x-show="isOpen" 
         @click.away="if(config.closeOnOverlayClick) close()"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0 transform scale-90"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-300"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-90"
         class="fixed inset-0 z-50 flex items-center justify-center">
        
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black opacity-50"></div>
        
        <!-- Modal -->
        <div class="relative z-10 w-full max-w-md p-6 bg-white rounded-lg shadow-xl">
            <h2>Modal Title</h2>
            <p>Modal content here</p>
            <button @click="close" x-init-focus>Close</button>
        </div>
    </div>
</div>

<!-- JavaScript Usage -->
<script>
// Open modal programmatically
window.modalManager.open('alpine-modal');

// Listen for events
window.addEventListener('modal-opened', event => {
    console.log('Modal opened:', event.detail.id);
});
</script>
```

### Alpine Form Component

Example of using the Alpine.js form component:

```html
<!-- HTML Structure -->
<form x-data="form({
    endpoint: '/api/submit',
    method: 'POST',
    rules: {
        name: 'required|min:2',
        email: 'required|email'
    }
})" @submit.prevent="submit">
    <div>
        <label for="name">Name</label>
        <input 
            id="name" 
            type="text" 
            x-model="formData.name"
            @blur="handleBlur('name')"
            :class="{'border-red-500': errors.name && touched.name}"
            data-field="name"
        >
        <p x-show="errors.name && touched.name" x-text="errors.name" class="text-red-500"></p>
    </div>
    
    <div>
        <label for="email">Email</label>
        <input 
            id="email" 
            type="email" 
            x-model="formData.email"
            @blur="handleBlur('email')"
            :class="{'border-red-500': errors.email && touched.email}"
            data-field="email"
        >
        <p x-show="errors.email && touched.email" x-text="errors.email" class="text-red-500"></p>
    </div>
    
    <button type="submit" :disabled="loading">
        <span x-show="loading">Submitting...</span>
        <span x-show="!loading">Submit</span>
    </button>
    
    <div x-show="success" class="text-green-500">Form submitted successfully!</div>
    <div x-show="error" class="text-red-500" x-text="error"></div>
</form>
```

## Creating a New Component

Follow these steps to create a new component:

### 1. Create the component file

Create a new file in `/js/components/my-component.js`:

```javascript
/**
 * My Custom Component
 * Description of what the component does
 */

(function() {
    // Ensure CarFuse global object exists
    if (typeof window.CarFuse === 'undefined') {
        window.CarFuse = {};
    }
    
    // Create component
    CarFuse.createComponent('myComponent', {
        // Component dependencies
        dependencies: ['core', 'events'],
        
        // Component properties schema
        props: {
            option1: CarFuse.utils.PropValidator.types.string({ default: 'default' }),
            option2: CarFuse.utils.PropValidator.types.number({ required: true })
        },
        
        // Component state
        state: {
            items: [],
            loading: false
        },
        
        // Lifecycle methods
        prepare() {
            // Synchronous preparation
        },
        
        initialize() {
            // Async initialization
            return fetch('/api/data')
                .then(response => response.json())
                .then(data => {
                    this.update({ items: data, loading: false });
                });
        },
        
        mountElements(elements) {
            elements.forEach(element => {
                // Setup each element
                this.renderElement(element);
            });
        },
        
        // Custom methods
        renderElement(element) {
            // Render logic for a single element
        }
    });
})();
```

### 2. Use the component in HTML

```html
<div data-component="myComponent" data-options='{"option1": "custom", "option2": 42}'>
    Component content here
</div>
```

### 3. Initialize the component

```javascript
// Load and initialize
CarFuse.componentRegistry.load('myComponent')
    .then(component => {
        console.log('Component loaded:', component);
    });

// Or use auto-discovery
CarFuse.componentRegistry.discoverComponents();
```
