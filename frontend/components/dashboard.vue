<template>
  <div class="dashboard">
    <!-- Summary Cards -->
    <div class="summary-cards">
      <div 
        v-for="(metric, key) in metrics" 
        :key="key"
        class="metric-card"
        :class="{ active: selectedMetric === key }"
        @click="selectMetric(key)"
      >
        <h3>{{ metric.label }}</h3>
        <div class="metric-value">{{ metric.value }}</div>
        <div class="metric-trend" :class="metric.trend">
          {{ metric.percentage }}%
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filters">
      <div class="date-range">
        <input type="date" v-model="dateRange.start" @change="updateData">
        <input type="date" v-model="dateRange.end" @change="updateData">
      </div>
      <select v-model="chartType" @change="updateChartType">
        <option value="line">Line Chart</option>
        <option value="bar">Bar Chart</option>
      </select>
    </div>

    <!-- Chart -->
    <div class="chart-container">
      <Line
        v-if="chartType === 'line'"
        :data="chartData"
        :options="chartOptions"
      />
      <Bar
        v-else
        :data="chartData"
        :options="chartOptions"
      />
    </div>

    <!-- Recent Activity Table -->
    <div class="activity-table">
      <h3>Recent Activity</h3>
      <table>
        <thead>
          <tr>
            <th>Time</th>
            <th>Type</th>
            <th>User</th>
            <th>Details</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="activity in recentActivities" :key="activity.id">
            <td>{{ formatDate(activity.timestamp) }}</td>
            <td>{{ activity.type }}</td>
            <td>{{ activity.user }}</td>
            <td>{{ activity.details }}</td>
            <td>{{ activity.status }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <slot></slot>
  </div>
</template>

<script>
import { Line, Bar } from 'vue-chartjs'
import { Chart as ChartJS, CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend } from 'chart.js'
import { format } from 'date-fns'
import { fetchData, useLoading } from '../shared/utils'

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, BarElement, Title, Tooltip, Legend)

/**
 * @component Dashboard
 * @description Main dashboard with metrics and charts
 * 
 * @api {GET} /api/dashboard/metrics - Get dashboard metrics
 * @api {GET} /api/dashboard/activities - Get recent activities
 */
export default {
  name: 'Dashboard',
  components: { Line, Bar },
  props: {
    metrics: {
      type: Object,
      required: true
    },
    chartData: {
      type: Object,
      required: true
    },
    chartType: {
      type: String,
      required: true
    }
  },
  emits: ['filter-changed', 'chart-type-changed'],
  data() {
    return {
      selectedMetric: 'bookings',
      dateRange: {
        start: new Date(Date.now() - 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        end: new Date().toISOString().split('T')[0]
      },
      chartType: 'line',
      chartOptions: {
        responsive: true,
        maintainAspectRatio: false
      }
    }
  },
  setup() {
    const loading = useLoading('dashboard')
    return { loading }
  },
  methods: {
    selectMetric(metric) {
      this.selectedMetric = metric
      this.updateData()
    },

    async updateData() {
      try {
        const data = await fetchData(`/api/dashboard/metrics`, {
          params: {
            start: this.dateRange.start,
            end: this.dateRange.end,
            metric: this.selectedMetric
          }
        })
        this.$emit('data-updated', data)
      } catch (error) {
        this.$emit('error', error)
      }
    },

    updateChartType() {
      this.$emit('chart-type-changed', this.chartType)
    },

    formatDate(date) {
      return format(new Date(date), 'MMM d, yyyy HH:mm')
    },

    applyFilters() {
      this.$emit('filter-changed');
      // Apply filters logic here
      // ...existing code...
    }
  }
}
</script>

<style scoped>
.dashboard {
  padding: 1rem;
  gap: 1.5rem;
  display: flex;
  flex-direction: column;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.metric-card {
  padding: 1.5rem;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  cursor: pointer;
  transition: transform 0.2s;
}

.metric-card:hover {
  transform: translateY(-2px);
}

.metric-card.active {
  border: 2px solid #4CAF50;
}

.chart-container {
  height: 400px;
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.filters {
  display: flex;
  gap: 1rem;
  align-items: center;
  flex-wrap: wrap;
}

.activity-table {
  background: white;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th, td {
  padding: 0.75rem;
  text-align: left;
  border-bottom: 1px solid #eee;
}

@media (max-width: 768px) {
  .summary-cards {
    grid-template-columns: 1fr;
  }
  
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
  
  .chart-container {
    height: 300px;
  }
}
</style>
