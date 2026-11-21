/* Tokopi admin – default Chart.js + format angka */
(function () {
  if (!window.Chart) return;
  var css = getComputedStyle(document.documentElement);
  var v = function (n, f) { return (css.getPropertyValue(n) || f).trim(); };
  window.TK_COLORS = [v('--chart-1', '#2D1B0A'), v('--chart-2', '#C08A3E'), v('--chart-3', '#A9471F'), v('--chart-4', '#56622F'), v('--chart-5', '#8C7A66')];

  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#5E5143';
  Chart.defaults.borderColor = '#F0ECE3';
  Chart.defaults.plugins.legend.labels.boxWidth = 10;
  Chart.defaults.plugins.legend.labels.boxHeight = 10;
  Chart.defaults.plugins.legend.labels.useBorderRadius = true;
  Chart.defaults.plugins.legend.labels.borderRadius = 3;
  Chart.defaults.plugins.tooltip.backgroundColor = '#22180E';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 8;
  Chart.defaults.maintainAspectRatio = false;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) Chart.defaults.animation = false;

  window.tkRp = function (n) { return 'Rp ' + Math.round(n || 0).toLocaleString('id-ID'); };
  window.tkRpShort = function (n) {
    n = Number(n) || 0; var a = Math.abs(n);
    if (a >= 1e9) return 'Rp ' + (n / 1e9).toFixed(1).replace('.', ',') + 'M';
    if (a >= 1e6) return 'Rp ' + (n / 1e6).toFixed(1).replace('.', ',') + 'jt';
    if (a >= 1e3) return 'Rp ' + Math.round(n / 1e3) + 'rb';
    return 'Rp ' + n;
  };
  window.tkMoneyAxis = { beginAtZero: true, ticks: { callback: function (val) { return window.tkRpShort(val); } } };
  window.tkMoneyTooltip = { callbacks: { label: function (c) { return ' ' + (c.dataset.label ? c.dataset.label + ': ' : '') + window.tkRp(c.raw); } } };
})();
