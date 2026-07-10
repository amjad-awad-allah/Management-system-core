<template>
  <div class="h-64 w-full">
    <Bar v-if="chartData" :data="chartData" :options="chartOptions" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import {
  Chart as ChartJS,
  Title,
  Tooltip,
  Legend,
  BarElement,
  CategoryScale,
  LinearScale
} from 'chart.js'
import { Bar } from 'vue-chartjs'
import { useDashboardStore } from '@/stores/dashboardStore'

ChartJS.register(CategoryScale, LinearScale, BarElement, Title, Tooltip, Legend)

const store = useDashboardStore()

const chartData = computed(() => {
  if (!store.revenueChart || store.revenueChart.length === 0) return null

  return {
    labels: store.revenueChart.map(item => item.month),
    datasets: [
      {
        label: 'Revenue (€)',
        backgroundColor: (context: any) => {
          const chart = context.chart
          const { ctx, chartArea } = chart
          if (!chartArea) return 'rgba(147, 51, 234, 0.8)'
          const gradient = ctx.createLinearGradient(0, chartArea.bottom, 0, chartArea.top)
          gradient.addColorStop(0, 'rgba(147, 51, 234, 0.2)') // Light purple at bottom
          gradient.addColorStop(1, 'rgba(79, 70, 229, 0.9)')  // Indigo at top
          return gradient
        },
        hoverBackgroundColor: 'rgba(99, 102, 241, 1)', // Indigo on hover
        borderRadius: 6,
        data: store.revenueChart.map(item => item.revenue)
      }
    ]
  }
})

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: {
      display: false
    },
    tooltip: {
      callbacks: {
        label: (context: any) => `€${context.raw.toFixed(2)}`
      }
    }
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: {
        color: 'rgba(156, 163, 175, 0.1)'
      },
      ticks: {
        callback: (value: any) => `€${value}`
      }
    },
    x: {
      grid: {
        display: false
      }
    }
  }
}
</script>
