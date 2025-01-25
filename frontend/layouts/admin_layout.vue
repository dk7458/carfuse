<template>
  <div class="admin-layout" :class="{ 'sidebar-collapsed': isSidebarCollapsed }">
    <header class="admin-header">
      <button class="sidebar-toggle" @click="toggleSidebar">
        <i class="fas fa-bars"></i>
      </button>
      
      <nav class="breadcrumb">
        <ul>
          <li v-for="breadcrumb in breadcrumbs" :key="breadcrumb.text">
            <router-link :to="breadcrumb.link">{{ breadcrumb.text }}</router-link>
          </li>
        </ul>
      </nav>

      <div class="admin-profile">
        <img :src="adminAvatar" :alt="adminName" class="avatar">
        <span class="admin-name">{{ adminName }}</span>
        <button @click="logout" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i>
          Logout
        </button>
      </div>
    </header>

    <aside class="sidebar">
      <nav class="sidebar-nav">
        <router-link to="/admin/dashboard" class="nav-item">
          <i class="fas fa-tachometer-alt"></i>
          <span>Dashboard</span>
        </router-link>
        <router-link to="/admin/bookings" class="nav-item">
          <i class="fas fa-calendar-alt"></i>
          <span>Bookings</span>
        </router-link>
        <router-link to="/admin/users" class="nav-item">
          <i class="fas fa-users"></i>
          <span>Users</span>
        </router-link>
        <router-link to="/admin/reports" class="nav-item">
          <i class="fas fa-chart-bar"></i>
          <span>Reports</span>
        </router-link>
      </nav>
      <slot name="sidebar"></slot>
    </aside>

    <main class="main-content">
      <h1 class="page-title">{{ pageTitle }}</h1>
      <slot></slot>
    </main>
  </div>
</template>

<script>
export default {
  name: 'AdminLayout',
  props: {
    pageTitle: {
      type: String,
      required: true
    },
    breadcrumbs: {
      type: Array,
      required: true
    },
    adminName: {
      type: String,
      required: true
    },
    adminAvatar: {
      type: String,
      default: '/default-avatar.png'
    }
  },

  data() {
    return {
      isSidebarCollapsed: false
    }
  },

  methods: {
    toggleSidebar() {
      this.isSidebarCollapsed = !this.isSidebarCollapsed
    },

    logout() {
      // Implement logout logic
      this.$router.push('/login')
    }
  },

  created() {
    // Handle responsive sidebar on mobile
    const handleResize = () => {
      this.isSidebarCollapsed = window.innerWidth <= 768
    }
    window.addEventListener('resize', handleResize)
    handleResize()
  }
}
</script>

<style scoped>
.admin-layout {
  display: grid;
  grid-template-areas:
    "sidebar header"
    "sidebar main";
  grid-template-columns: 250px 1fr;
  grid-template-rows: auto 1fr;
  min-height: 100vh;
  transition: all 0.3s ease;
}

.admin-header {
  grid-area: header;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  background: white;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.sidebar {
  grid-area: sidebar;
  background: #2c3e50;
  color: white;
  padding: 1rem;
  transition: all 0.3s ease;
}

.main-content {
  grid-area: main;
  padding: 2rem;
  background: #f8f9fa;
}

.sidebar-nav {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  color: white;
  text-decoration: none;
  border-radius: 4px;
  transition: background 0.2s;
}

.nav-item:hover {
  background: rgba(255,255,255,0.1);
}

.admin-profile {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
}

.sidebar-toggle {
  display: none;
}

/* Responsive Design */
@media (max-width: 768px) {
  .admin-layout {
    grid-template-columns: 0 1fr;
  }

  .sidebar-toggle {
    display: block;
  }

  .sidebar {
    position: fixed;
    left: 0;
    top: 0;
    bottom: 0;
    width: 250px;
    transform: translateX(0);
    z-index: 1000;
  }

  .sidebar-collapsed .sidebar {
    transform: translateX(-100%);
  }

  .admin-profile .admin-name {
    display: none;
  }
}
</style>
