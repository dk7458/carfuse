<template>
  <div class="user-layout">
    <header>
      <!-- Primary Navigation -->
      <slot name="navbar">
        <nav class="primary-nav">
          <router-link to="/">Home</router-link>
          <router-link to="/bookings">My Bookings</router-link>
          <router-link to="/profile">Profile</router-link>
        </nav>
      </slot>

      <!-- Dynamic Breadcrumbs -->
      <nav v-if="breadcrumbs.length" class="breadcrumb-nav">
        <ul>
          <li v-for="(crumb, index) in breadcrumbs" :key="index">
            <router-link v-if="crumb.link" :to="crumb.link">{{ crumb.text }}</router-link>
            <span v-else>{{ crumb.text }}</span>
            <span v-if="index < breadcrumbs.length - 1" class="separator">/</span>
          </li>
        </ul>
      </nav>

      <!-- Secondary Navigation -->
      <nav v-if="secondaryNavItems.length" class="secondary-nav">
        <router-link
          v-for="item in secondaryNavItems"
          :key="item.path"
          :to="item.path"
          :class="{ active: isCurrentPath(item.path) }"
        >
          {{ item.label }}
        </router-link>
      </nav>

      <h1>{{ pageTitle }}</h1>
      <button @click="logout" class="logout-btn">
        <i class="fas fa-sign-out-alt"></i>
        Logout
      </button>
    </header>

    <main>
      <slot></slot>
    </main>

    <footer>
      <slot name="footer">
        <p>&copy; {{ currentYear }} CarFuze. All rights reserved.</p>
      </slot>
    </footer>
  </div>
</template>

<script>
/**
 * @component UserLayout
 * @description Base layout for user-facing pages with navigation and breadcrumbs
 * 
 * @example
 * <UserLayout
 *   page-title="My Bookings"
 *   :breadcrumbs="[
 *     { text: 'Home', link: '/' },
 *     { text: 'My Bookings' }
 *   ]"
 *   :secondary-nav-items="[
 *     { label: 'Active Bookings', path: '/bookings/active' },
 *     { label: 'History', path: '/bookings/history' }
 *   ]"
 * >
 *   <template #navbar>
 *     <CustomNavbar />
 *   </template>
 *   
 *   <template #footer>
 *     <CustomFooter />
 *   </template>
 *   
 *   <MainContent />
 * </UserLayout>
 */
export default {
  name: 'UserLayout',
  
  props: {
    /**
     * Page title displayed in the header
     */
    pageTitle: {
      type: String,
      required: true
    },
    /**
     * Array of breadcrumb items
     * @type {Array<{ text: string, link?: string }>}
     */
    breadcrumbs: {
      type: Array,
      default: () => []
    },
    /**
     * Secondary navigation items for sub-pages
     * @type {Array<{ label: string, path: string }>}
     */
    secondaryNavItems: {
      type: Array,
      default: () => []
    }
  },

  computed: {
    currentYear() {
      return new Date().getFullYear()
    }
  },

  methods: {
    logout() {
      // Implement logout logic here
      this.$router.push('/login')
    },

    isCurrentPath(path) {
      return this.$route.path === path
    }
  }
}
</script>

<style scoped>
.user-layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

header {
  padding: 1rem;
  background-color: #f8f9fa;
  border-bottom: 1px solid #dee2e6;
}

.primary-nav {
  display: flex;
  gap: 1rem;
  margin-bottom: 1rem;
}

.breadcrumb-nav ul {
  display: flex;
  list-style: none;
  padding: 0;
  margin: 0.5rem 0;
}

.breadcrumb-nav li {
  display: flex;
  align-items: center;
}

.separator {
  margin: 0 0.5rem;
  color: #6c757d;
}

.secondary-nav {
  display: flex;
  gap: 1rem;
  margin: 1rem 0;
  padding: 0.5rem;
  background-color: #fff;
  border-radius: 4px;
}

.secondary-nav a {
  padding: 0.5rem 1rem;
  text-decoration: none;
  color: #6c757d;
  border-radius: 4px;
}

.secondary-nav a.active {
  background-color: #e9ecef;
  color: #212529;
}

main {
  flex: 1;
  padding: 2rem;
}

footer {
  padding: 1rem;
  background-color: #f8f9fa;
  text-align: center;
  border-top: 1px solid #dee2e6;
}

/* Responsive Design */
@media (max-width: 768px) {
  .primary-nav,
  .secondary-nav {
    flex-direction: column;
    gap: 0.5rem;
  }

  .breadcrumb-nav {
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 0.5rem;
  }

  main {
    padding: 1rem;
  }
}
</style>
