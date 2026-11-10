<script setup>
import { onMounted, ref, watch, onBeforeUnmount } from 'vue';
import {
  Chart,
  LineController,
  LineElement,
  PointElement,
  LinearScale,
  CategoryScale,
  Title,
  Tooltip,
  Filler
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
    Title,
    Tooltip,
    Filler
);

const props = defineProps({
  chartValues: {
    type: Object,
    required: true,
    default: () => ({ labels: [], data: [] })
  }
});
const canvasRef = ref(null);

let chartInstance = null;

const renderChart = () => {
  if (!canvasRef.value) return;

  // Prevent for building blank chart, wait till fetch data from backend
  if (!props.chartValues.labels || props.chartValues.labels.length === 0) {
    return;
  }

  if (chartInstance) {
    chartInstance.destroy();
  }

  const ctx = canvasRef.value.getContext('2d');
  const gradient = ctx.createLinearGradient(0, 0, 0, 300);
  gradient.addColorStop(0, 'rgba(79, 70, 229, 0.35)');
  gradient.addColorStop(1, 'rgba(79, 70, 229, 0.00)');

  chartInstance = new Chart(ctx, {
    type: 'line',
    data: {
      labels: props.chartValues.labels || [],
      datasets: [
        {
          label: 'Registrations',
          data: props.chartValues.data || [],
          borderColor: '#6366f1',
          borderWidth: 2.5,
          backgroundColor: gradient,
          fill: true,
          tension: 0.3,
          pointBackgroundColor: '#6366f1',
          pointBorderColor: '#0b0f17',
          pointBorderWidth: 2,
          pointRadius: 4,
          pointHoverRadius: 6
        }
      ]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: {
          display: false
        },
        tooltip: {
          backgroundColor: '#1f2937',
          titleColor: '#f3f4f6',
          bodyColor: '#9ca3af',
          borderColor: '#374151',
          borderWidth: 1,
          padding: 10,
          displayColors: false,
          cornerRadius: 8
        }
      },
      scales: {
        x: {
          grid: {
            display: false,
          },
          ticks: {
            color: '#9ca3af',
            font: { size: 11 }
          }
        },
        y: {
          grid: {
            color: '#1f2937',
          },
          min: 0,
          ticks: {
            color: '#9ca3af',
            font: { size: 11 },
            stepSize: 1
          }
        }
      }
    }
  })
};

watch(() => props.chartValues, () => {
  renderChart();
}, { deep: true });

onMounted(() => {
  renderChart();
});

onBeforeUnmount(() => {
  if (chartInstance) {
    chartInstance.destroy();
  }
});
</script>

<template>
  <div class="h-72 w-full relative">
    <canvas ref="canvasRef"></canvas>
  </div>
</template>
