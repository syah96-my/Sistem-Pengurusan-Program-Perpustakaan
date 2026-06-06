const API = window.GM_API_BASE || "/api/?route=";
const KEY = window.GM_API_KEY;

/* =========================================================
   HELPERS
========================================================= */
function apiGet(route) {
  return fetch(API + route, {
    headers: { "X-API-KEY": KEY }
  }).then(r => r.json());
}

/* =========================================================
   LOAD DASHBOARD CARDS
========================================================= */
function loadDashboardCards() {
  apiGet("dashboard/summary").then(res => {
    if (!res.success) return;

    document.getElementById("total-program").textContent  = res.cards.total;
    document.getElementById("total-pending").textContent  = res.cards.pending;
    document.getElementById("total-rejected").textContent = res.cards.rejected;
  });
}

/* =========================================================
   MONTHLY VERIFIED PROGRAM CHART
========================================================= */
let monthlyChart;

function loadMonthlyChart() {
  apiGet("dashboard/monthly").then(res => {
    if (!res.success) return;

    // Fixed labels (Jan-Dec)
    const labels = [
      "Jan","Feb","Mar","Apr","May","Jun",
      "Jul","Aug","Sep","Oct","Nov","Dec"
    ];

    const values = res.monthly;

    const ctx = document.getElementById("monthlyChart");

    if (monthlyChart) {
      monthlyChart.destroy();
    }

    monthlyChart = new Chart(ctx, {
      type: "bar",
      data: {
        labels,
        datasets: [{
          label: "Verified Programs",
          data: values,
          backgroundColor: "rgba(27,27,178,0.7)",
          borderRadius: 6,
          maxBarThickness: 40
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
          tooltip: {
            callbacks: {
              label: ctx => ` ${ctx.raw} programs`
            }
          }
        },
        scales: {
          x: {
            grid: { display: false }
          },
          y: {
            beginAtZero: true,
            ticks: {
              precision: 0,
              stepSize: 1
            }
          }
        }
      }
    });
  });
}
/* =========================================================
   RECENT PROGRAMS TABLE
========================================================= */
function loadRecentPrograms() {
  apiGet("dashboard/recent").then(res => {
    if (!res.success) return;

    const tbody = document.querySelector(".dash-table tbody");
    tbody.innerHTML = "";

    if (!res.programs.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center-muted">No programs found</td>
        </tr>
      `;
      return;
    }

    res.programs.forEach(p => {
      const statusClass = p.status;
      const statusText  = p.status.charAt(0).toUpperCase() + p.status.slice(1);

      tbody.innerHTML += `
        <tr>
          <td>${p.program_name}</td>
          <td>${p.library_name ?? "-"}</td>
          <td>${p.program_start?.substring(0,10) ?? "-"}</td>
          <td><span class="status ${statusClass}">${statusText}</span></td>
        </tr>
      `;
    });
  });
}

/* =========================================================
   INIT DASHBOARD
========================================================= */
document.addEventListener("DOMContentLoaded", () => {
  loadDashboardCards();
  loadMonthlyChart();
  loadRecentPrograms();
});
