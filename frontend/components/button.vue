<template>
  <button
    :class="[
      'custom-button',
      size,
      variant,
      { 'loading': loading, 'block': block }
    ]"
    :disabled="disabled || loading"
    @click="$emit('click', $event)"
  >
    <span v-if="loading" class="spinner"></span>
    <slot></slot>
  </button>
</template>

<script>
/**
 * @component Button
 * @description A customizable button component with various sizes, colors, and states
 * 
 * @example
 * <Button
 *   size="lg"
 *   variant="primary"
 *   :loading="isLoading"
 *   :block="true"
 *   @click="handleClick"
 * >
 *   Submit
 * </Button>
 */
export default {
  name: 'Button',
  props: {
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['sm', 'md', 'lg'].includes(value)
    },
    variant: {
      type: String,
      default: 'primary',
      validator: (value) => [
        'primary',
        'secondary',
        'success',
        'danger',
        'warning',
        'info'
      ].includes(value)
    },
    loading: {
      type: Boolean,
      default: false
    },
    disabled: {
      type: Boolean,
      default: false
    },
    block: {
      type: Boolean,
      default: false
    }
  },
  
  emits: ['click']
}
</script>

<style scoped>
.custom-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
}

/* Sizes */
.sm { padding: 0.25rem 0.5rem; font-size: 0.875rem; }
.md { padding: 0.5rem 1rem; font-size: 1rem; }
.lg { padding: 0.75rem 1.5rem; font-size: 1.125rem; }

/* Variants */
.primary {
  background: #4CAF50;
  color: white;
}

.secondary {
  background: #6c757d;
  color: white;
}

.success {
  background: #28a745;
  color: white;
}

.danger {
  background: #dc3545;
  color: white;
}

.warning {
  background: #ffc107;
  color: #212529;
}

.info {
  background: #17a2b8;
  color: white;
}

/* States */
.custom-button:disabled {
  opacity: 0.65;
  cursor: not-allowed;
}

.block {
  width: 100%;
}

.loading {
  position: relative;
  color: transparent !important;
}

.spinner {
  position: absolute;
  width: 16px;
  height: 16px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}
</style>
