<template>
  <Transition name="modal">
    <div v-if="modelValue" class="modal-overlay" @click="closeOnBackdrop && close()">
      <div class="modal" :class="[size, { 'loading': loading.value }]" @click.stop>
        <div class="modal-header">
          <slot name="header">
            <h3>{{ title }}</h3>
          </slot>
          <button v-if="closeable" class="close-btn" @click="close">×</button>
        </div>

        <div class="modal-body">
          <slot></slot>
        </div>

        <div class="modal-footer">
          <slot name="footer">
            <Button @click="close" variant="secondary" :disabled="loading.value">Cancel</Button>
            <Button @click="confirm" variant="primary" :loading="loading.value">Confirm</Button>
          </slot>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script>
import { useLoading } from '../shared/utils'

/**
 * @component Modal
 * @description Enhanced modal with loading state management
 */
export default {
  name: 'Modal',
  props: {
    modelValue: {
      type: Boolean,
      required: true
    },
    title: {
      type: String,
      default: ''
    },
    size: {
      type: String,
      default: 'md',
      validator: (value) => ['sm', 'md', 'lg', 'xl'].includes(value)
    },
    closeable: {
      type: Boolean,
      default: true
    },
    closeOnBackdrop: {
      type: Boolean,
      default: true
    }
  },
  
  emits: ['update:modelValue', 'confirm', 'close', 'error'],

  setup() {
    const loading = useLoading('modal')
    return { loading }
  },
  
  methods: {
    async confirm() {
      this.loading.value = true
      try {
        await this.$emit('confirm')
        this.close()
      } catch (error) {
        console.error('Modal confirmation failed:', error)
        this.$emit('error', error)
      } finally {
        this.loading.value = false
      }
    },

    close() {
      if (this.loading.value) return
      this.$emit('update:modelValue', false)
      this.$emit('close')
    }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal {
  background: white;
  border-radius: 8px;
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal.sm { width: 300px; }
.modal.md { width: 500px; }
.modal.lg { width: 800px; }
.modal.xl { width: 1100px; }

.modal-header {
  padding: 1rem;
  border-bottom: 1px solid #dee2e6;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-body {
  padding: 1rem;
  overflow-y: auto;
}

.modal-footer {
  padding: 1rem;
  border-top: 1px solid #dee2e6;
  display: flex;
  justify-content: flex-end;
  gap: 0.5rem;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.5rem;
  cursor: pointer;
  padding: 0.25rem 0.5rem;
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

@media (max-width: 768px) {
  .modal {
    width: 90% !important;
    margin: 1rem;
  }
}
</style>
