<template>
  <div class="user-profile">
    <div v-if="message" :class="['alert', message.type]">
      {{ message.text }}
    </div>

    <!-- Profile Information Form -->
    <form @submit.prevent="saveProfile" class="profile-form">
      <div class="form-group">
        <label for="name">Name *</label>
        <input 
          id="name"
          v-model="formData.name"
          :class="{ 'error': errors.name }"
          required
        />
        <span class="error-text" v-if="errors.name">{{ errors.name }}</span>
      </div>

      <div class="form-group">
        <label for="email">Email *</label>
        <input 
          id="email"
          type="email"
          v-model="formData.email"
          :class="{ 'error': errors.email }"
          required
        />
        <span class="error-text" v-if="errors.email">{{ errors.email }}</span>
      </div>

      <div class="form-group">
        <label for="phone">Phone</label>
        <input 
          id="phone"
          type="tel"
          v-model="formData.phone"
          :class="{ 'error': errors.phone }"
        />
        <span class="error-text" v-if="errors.phone">{{ errors.phone }}</span>
      </div>

      <div class="form-group">
        <label for="address">Address</label>
        <textarea 
          id="address"
          v-model="formData.address"
          :class="{ 'error': errors.address }"
        ></textarea>
        <span class="error-text" v-if="errors.address">{{ errors.address }}</span>
      </div>

      <button type="submit" :disabled="loading">
        {{ loading ? 'Saving...' : 'Save Changes' }}
      </button>
    </form>

    <!-- Password Change Section -->
    <div class="password-section">
      <h3>Change Password</h3>
      <form @submit.prevent="changePassword" class="password-form">
        <div class="form-group">
          <label for="currentPassword">Current Password *</label>
          <input 
            id="currentPassword"
            type="password"
            v-model="passwordData.current"
            :class="{ 'error': errors.currentPassword }"
            required
          />
          <span class="error-text" v-if="errors.currentPassword">
            {{ errors.currentPassword }}
          </span>
        </div>

        <div class="form-group">
          <label for="newPassword">New Password *</label>
          <input 
            id="newPassword"
            type="password"
            v-model="passwordData.new"
            :class="{ 'error': errors.newPassword }"
            required
          />
          <span class="error-text" v-if="errors.newPassword">
            {{ errors.newPassword }}
          </span>
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirm Password *</label>
          <input 
            id="confirmPassword"
            type="password"
            v-model="passwordData.confirm"
            :class="{ 'error': errors.confirmPassword }"
            required
          />
          <span class="error-text" v-if="errors.confirmPassword">
            {{ errors.confirmPassword }}
          </span>
        </div>

        <button type="submit" :disabled="loading">
          {{ loading ? 'Updating...' : 'Update Password' }}
        </button>
      </form>
    </div>
  </div>
</template>

<script>
import { fetchData, handleError } from '../shared/utils'

/**
 * @component UserProfile
 * @description User profile management component
 * 
 * @api {GET} /api/users/:id - Fetch user profile
 * @api {PUT} /api/users/:id - Update user profile
 * @api {PUT} /api/users/:id/password - Update password
 */
export default {
  name: 'UserProfile',
  props: {
    userId: {
      type: String,
      required: true
    }
  },

  data() {
    return {
      formData: {
        name: '',
        email: '',
        phone: '',
        address: ''
      },
      passwordData: {
        current: '',
        new: '',
        confirm: ''
      },
      errors: {},
      loading: false,
      message: null
    }
  },

  async created() {
    await this.fetchUserProfile();
  },

  methods: {
    async fetchUserProfile() {
      try {
        const response = await fetchData(`/api/users/${this.userId}`);
        this.formData = response;
      } catch (error) {
        const { message } = handleError(error, 'UserProfile');
        this.showMessage(message, 'error');
      }
    },

    async saveProfile() {
      if (!this.validateForm()) return;

      this.loading = true;
      try {
        await fetchData(`/api/users/${this.userId}`, {
          method: 'PUT',
          body: JSON.stringify(this.formData),
          noCache: true
        });
        this.showMessage('Profile updated successfully', 'success');
      } catch (error) {
        const { message } = handleError(error, 'UserProfile');
        this.showMessage(message, 'error');
      } finally {
        this.loading = false;
      }
    },

    async changePassword() {
      if (!this.validatePasswordForm()) return

      this.loading = true
      try {
        await axios.put(`/api/users/${this.userId}/password`, {
          currentPassword: this.passwordData.current,
          newPassword: this.passwordData.new
        })
        this.showMessage('Password updated successfully', 'success')
        this.resetPasswordForm()
      } catch (error) {
        this.showMessage('Error updating password', 'error')
      } finally {
        this.loading = false
      }
    },

    validateForm() {
      this.errors = {}
      
      if (!this.formData.name) {
        this.errors.name = 'Name is required'
      }
      
      if (!this.formData.email) {
        this.errors.email = 'Email is required'
      } else if (!this.isValidEmail(this.formData.email)) {
        this.errors.email = 'Invalid email format'
      }

      return Object.keys(this.errors).length === 0
    },

    validatePasswordForm() {
      this.errors = {}

      if (this.passwordData.new !== this.passwordData.confirm) {
        this.errors.confirmPassword = 'Passwords do not match'
      }

      if (this.passwordData.new.length < 8) {
        this.errors.newPassword = 'Password must be at least 8 characters'
      }

      return Object.keys(this.errors).length === 0
    },

    isValidEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)
    },

    showMessage(text, type) {
      this.message = { text, type }
      setTimeout(() => {
        this.message = null
      }, 3000)
    },

    resetPasswordForm() {
      this.passwordData = {
        current: '',
        new: '',
        confirm: ''
      }
    }
  }
}
</script>

<style scoped>
.user-profile {
  max-width: 600px;
  margin: 0 auto;
  padding: 2rem;
}

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
}

input, textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 1rem;
}

input.error, textarea.error {
  border-color: #dc3545;
}

.error-text {
  color: #dc3545;
  font-size: 0.875rem;
  margin-top: 0.25rem;
}

button {
  background: #4CAF50;
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 1rem;
}

button:disabled {
  background: #ccc;
  cursor: not-allowed;
}

.alert {
  padding: 1rem;
  margin-bottom: 1rem;
  border-radius: 4px;
}

.alert.success {
  background: #d4edda;
  color: #155724;
}

.alert.error {
  background: #f8d7da;
  color: #721c24;
}

.password-section {
  margin-top: 3rem;
  padding-top: 2rem;
  border-top: 1px solid #eee;
}

@media (max-width: 768px) {
  .user-profile {
    padding: 1rem;
  }
  
  input, textarea {
    font-size: 16px; /* Prevents zoom on mobile */
  }
}
</style>
