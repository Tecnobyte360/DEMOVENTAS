// Import Chart.js
import {
  Chart, LineController, LineElement, Filler, PointElement, LinearScale, CategoryScale, Tooltip,
} from 'chart.js';

import { chartAreaGradient } from '../app';
import { formatValue, getCssVariable, adjustColorOpacity } from '../utils';

Chart.register(LineController, LineElement, Filler, PointElement, LinearScale, CategoryScale, Tooltip);

const ventasCardChart = () => {
  const ctx = document.getElementById('ventas-card-chart');
  if (!ctx) return;

  const darkMode = localStorage.getItem('dark-mode') === 'true';

  const tooltipBodyColor = { light: '#6B7280', dark: '#9CA3AF' };
  const tooltipBgColor = { light: '#ffffff', dark: '#374151' };
  const tooltipBorderColor = { light: '#E5E7EB', dark: '#4B5563' };

  // ✅ Data inyectada desde Blade
  const payload = window.__ventasCard || { labels: [], data: [] };
  const labels = Array.isArray(payload.labels) ? payload.labels : [];
  const data = Array.isArray(payload.data) ? payload.data : [];

  // Si ya existe un chart con ese canvas, destrúyelo (evita duplicados al navegar)
  if (ctx.__chart) {
    ctx.__chart.destroy();
    ctx.__chart = null;
  }

  const chart = new Chart(ctx, {
    type: 'line',
    data: {
      labels,
      datasets: [
        {
          data,
          fill: true,
          backgroundColor: (context) => {
            const chart = context.chart;
            const { ctx, chartArea } = chart;
            if (!chartArea) return 'transparent';

            return chartAreaGradient(ctx, chartArea, [
              { stop: 0, color: adjustColorOpacity(getCssVariable('--color-emerald-500'), 0) },
              { stop: 1, color: adjustColorOpacity(getCssVariable('--color-emerald-500'), 0.22) },
            ]);
          },
          borderColor: getCssVariable('--color-emerald-500'),
          borderWidth: 2,
          pointRadius: 0,
          pointHoverRadius: 3,
          pointBackgroundColor: getCssVariable('--color-emerald-500'),
          pointHoverBackgroundColor: getCssVariable('--color-emerald-500'),
          pointBorderWidth: 0,
          pointHoverBorderWidth: 0,
          clip: 20,
          tension: 0.28,
        },
      ],
    },
    options: {
      layout: { padding: 16 },
      scales: {
        y: {
          display: false,
          beginAtZero: true,
          ticks: {
            callback: (v) => formatValue(v),
          },
        },
        x: {
          display: false,
          type: 'category',
        },
      },
      plugins: {
        tooltip: {
          callbacks: {
            title: (items) => (items?.[0]?.label ? `Hora: ${items[0].label}` : false),
            label: (context) => `Ventas: ${formatValue(context.parsed.y)}`,
          },
          bodyColor: darkMode ? tooltipBodyColor.dark : tooltipBodyColor.light,
          backgroundColor: darkMode ? tooltipBgColor.dark : tooltipBgColor.light,
          borderColor: darkMode ? tooltipBorderColor.dark : tooltipBorderColor.light,
          borderWidth: 1,
        },
        legend: { display: false },
      },
      interaction: { intersect: false, mode: 'nearest' },
      maintainAspectRatio: false,
    },
  });

  // Guarda referencia para destruir luego si Livewire re-renderiza
  ctx.__chart = chart;

  // Dark mode listener (igual que el tuyo)
  document.addEventListener('darkMode', (e) => {
    const { mode } = e.detail;
    if (!ctx.__chart) return;

    if (mode === 'on') {
      ctx.__chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.dark;
      ctx.__chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.dark;
      ctx.__chart.options.plugins.tooltip.borderColor = tooltipBorderColor.dark;
    } else {
      ctx.__chart.options.plugins.tooltip.bodyColor = tooltipBodyColor.light;
      ctx.__chart.options.plugins.tooltip.backgroundColor = tooltipBgColor.light;
      ctx.__chart.options.plugins.tooltip.borderColor = tooltipBorderColor.light;
    }
    ctx.__chart.update('none');
  });
};

export default ventasCardChart;
