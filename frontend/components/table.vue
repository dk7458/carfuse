<template>
  <div class="table-wrapper">
    <table :class="{ 'table-hover': hover }">
      <thead>
        <tr>
          <th 
            v-for="column in columns" 
            :key="column.key"
            :class="{ 
              sortable: column.sortable,
              sorted: sortKey === column.key 
            }"
            @click="column.sortable && sort(column.key)"
          >
            {{ column.label }}
            <span v-if="column.sortable" class="sort-icon">
              {{ sortKey === column.key ? (sortOrder === 'asc' ? '↑' : '↓') : '↕' }}
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(item, index) in paginatedData" :key="index">
          <td v-for="column in columns" :key="column.key">
            <slot :name="column.key" :item="item">
              {{ item[column.key] }}
            </slot>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="paginated" class="pagination">
      <Button 
        size="sm" 
        :disabled="currentPage === 1"
        @click="currentPage--"
      >
        Previous
      </Button>
      <span>Page {{ currentPage }} of {{ totalPages }}</span>
      <Button 
        size="sm" 
        :disabled="currentPage === totalPages"
        @click="currentPage++"
      >
        Next
      </Button>
    </div>
  </div>
</template>

<script>
/**
 * @component Table
 * @description A reusable table component with sorting and pagination
 * 
 * @example
 * <Table
 *   :columns="[
 *     { key: 'name', label: 'Name', sortable: true },
 *     { key: 'email', label: 'Email' },
 *     { key: 'status', label: 'Status' }
 *   ]"
 *   :data="users"
 *   :per-page="10"
 *   :hover="true"
 * >
 *   <template #status="{ item }">
 *     <StatusBadge :status="item.status" />
 *   </template>
 * </Table>
 */
export default {
  name: 'Table',
  props: {
    columns: {
      type: Array,
      required: true
    },
    data: {
      type: Array,
      required: true
    },
    paginated: {
      type: Boolean,
      default: true
    },
    perPage: {
      type: Number,
      default: 10
    },
    hover: {
      type: Boolean,
      default: true
    }
  },

  data() {
    return {
      currentPage: 1,
      sortKey: '',
      sortOrder: 'asc'
    }
  },

  computed: {
    sortedData() {
      if (!this.sortKey) return this.data

      return [...this.data].sort((a, b) => {
        const aVal = a[this.sortKey]
        const bVal = b[this.sortKey]
        return this.sortOrder === 'asc' 
          ? aVal > bVal ? 1 : -1
          : aVal < bVal ? 1 : -1
      })
    },

    paginatedData() {
      if (!this.paginated) return this.sortedData
      
      const start = (this.currentPage - 1) * this.perPage
      return this.sortedData.slice(start, start + this.perPage)
    },

    totalPages() {
      return Math.ceil(this.data.length / this.perPage)
    }
  },

  methods: {
    sort(key) {
      if (this.sortKey === key) {
        this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc'
      } else {
        this.sortKey = key
        this.sortOrder = 'asc'
      }
    }
  },

  watch: {
    data() {
      this.currentPage = 1
    }
  }
}
</script>

<style scoped>
.table-wrapper {
  width: 100%;
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #dee2e6;
}

th {
  background: #f8f9fa;
  font-weight: 600;
}

.table-hover tbody tr:hover {
  background-color: #f8f9fa;
}

.sortable {
  cursor: pointer;
  user-select: none;
}

.sort-icon {
  margin-left: 0.5rem;
  opacity: 0.5;
}

.sorted .sort-icon {
  opacity: 1;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1rem;
  margin-top: 1rem;
  padding: 1rem;
}

@media (max-width: 768px) {
  .table-wrapper {
    font-size: 0.875rem;
  }

  th, td {
    padding: 0.5rem;
  }
}
</style>
