<script setup>
import { onMounted, ref, watch, onBeforeUnmount } from 'vue';
import {
  Chart,
  DoughnutController,
  ArcElement,
  Tooltip,
  Legend
} from 'chart.js';

Chart.register(DoughnutController, ArcElement, Tooltip, Legend);

const props = defineProps({
  chartValues: {
    type: Object,
    required: true,
    default: () => ({ labels: [], data: [] })
  }
});

const canvasRef = ref(null);
let chartInstance = null;

const neonPalette = [
  '#6366f1', // Indigo
  '#06b6d4', // Cyan
  '#8b5cf6', // Violet
  '#10b981', // Emerald
  '#f59e0b', // Amber
  '#64748b'  // Slate (of others/unspecified)
];

const renderChart = () => {
  if (!canvasRef.value) return;

  if (!props.chartValues.labels || props.chartValues.labels.length === 0) {
    return;
  }

  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = canvasRef.value.getContext('2d');
  chartInstance = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: props.chartValues.labels,
      datasets: [
        {
          data: props.chartValues.data,
          backgroundColor: neonPalette,
          borderColor: '#111827',
          borderWidth: 3,
          hoverOffset: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '72%', // Thin circle
      plugins: {
        legend: {
          position: 'right',
          labels: {
            color: '#9ca3af',
            font: { size: 12 },
            padding: 16,
            usePointStyle: true,
            pointStyle: 'circle'
          }
        },
        tooltip: {
          backgroundColor: '#1f2937',
          titleColor: '#f3f4f6',
          bodyColor: '#e5e7eb',
          borderColor: '#374151',
          borderWidth: 1,
          padding: 10,
          cornerRadius: 8
        }
      }
    }
  });
};

watch(() => props.chartValues, () => {
  renderChart();
}, { deep: true });

onMounted(() => {
  renderChart();
});

onBeforeUnmount(() => {
  if (chartInstance) chartInstance.destroy();
});
</script>

<template>
  <div class="h-72 w-full relative flex items-center justify-center">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>
