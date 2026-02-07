import {
  Chart,
  LineController, LineElement, PointElement,
  LinearScale, CategoryScale,
  Tooltip, Filler,
} from "chart.js";

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Tooltip, Filler);

let ventasChartInstance = null;

export default function ventasCardChart() {
  const canvas = document.getElementById("ventas-card-chart");
  if (!canvas) return;

  const payload = window.__ventasCard || { labels: [], data: [] };

  // Si ya existe, destrúyelo antes (evita duplicados)
  if (ventasChartInstance) {
    ventasChartInstance.destroy();
    ventasChartInstance = null;
  }

  // Modo oscuro (tu template usa localStorage)
  const darkMode = localStorage.getItem("dark-mode") === "true";

  ventasChartInstance = new Chart(canvas, {
    type: "line",
    data: {
      labels: payload.labels || [],
      datasets: [
        {
          data: payload.data || [],
          fill: true,
          borderWidth: 2,
          pointRadius: 0,
          tension: 0.25,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      layout: { padding: 16 },
      scales: {
        x: { display: false },
        y: { display: false, beginAtZero: true },
      },
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            title: () => null,
            label: (ctx) => {
              const v = Number(ctx.parsed.y || 0);
              return `$ ${v.toLocaleString("es-CO")}`;
            },
          },
          bodyColor: darkMode ? "#E5E7EB" : "#374151",
          backgroundColor: darkMode ? "#0F172A" : "#FFFFFF",
          borderColor: darkMode ? "#334155" : "#E5E7EB",
          borderWidth: 1,
        },
      },
      interaction: { intersect: false, mode: "nearest" },
    },
  });

  // Si tu template dispara evento "darkMode" como en tu ejemplo
  document.addEventListener("darkMode", (e) => {
    const mode = e.detail?.mode;
    if (!ventasChartInstance) return;

    ventasChartInstance.options.plugins.tooltip.bodyColor = mode === "on" ? "#E5E7EB" : "#374151";
    ventasChartInstance.options.plugins.tooltip.backgroundColor = mode === "on" ? "#0F172A" : "#FFFFFF";
    ventasChartInstance.options.plugins.tooltip.borderColor = mode === "on" ? "#334155" : "#E5E7EB";
    ventasChartInstance.update("none");
  });
}
