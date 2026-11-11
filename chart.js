/**
 * Kinz Jewellery Admin - Luxury HTML5 Canvas Chart Engine
 * Pure static chart rendering for UI without localStorage or external database dependencies
 */

function getChartData() {
  const orders = (window.DB && Array.isArray(window.DB.orders)) ? window.DB.orders : [];
  const today = new Date();
  const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

  // 1. Last 7 Days
  const sevenDaysLabels = [];
  const sevenDaysRevenue = [];
  const sevenDaysOrders = [];

  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(today.getDate() - i);
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const dateStr = `${yyyy}-${mm}-${dd}`;
    
    sevenDaysLabels.push(dayNames[d.getDay()]);

    let rev = 0;
    let ordCount = 0;
    orders.forEach(o => {
      if (o.status !== 'Cancelled' && o.order_date && o.order_date.startsWith(dateStr)) {
        rev += parseFloat(o.total_amount) || 0;
        ordCount++;
      }
    });
    sevenDaysRevenue.push(rev);
    sevenDaysOrders.push(ordCount);
  }

  // 2. 30 Days (4 Weeks)
  const monthLabels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
  const monthRevenue = [0, 0, 0, 0];
  const monthOrders = [0, 0, 0, 0];
  orders.forEach(o => {
    if (o.status !== 'Cancelled' && o.order_date) {
      const oDate = new Date(o.order_date.replace(' ', 'T'));
      const diffDays = Math.floor((today - oDate) / (1000 * 60 * 60 * 24));
      if (diffDays >= 0 && diffDays < 28) {
        const weekIdx = 3 - Math.floor(diffDays / 7);
        if (weekIdx >= 0 && weekIdx < 4) {
          monthRevenue[weekIdx] += parseFloat(o.total_amount) || 0;
          monthOrders[weekIdx]++;
        }
      }
    }
  });

  // 3. 1 Year (12 Months)
  const yearLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  const yearRevenue = new Array(12).fill(0);
  const yearOrders = new Array(12).fill(0);
  const curYear = today.getFullYear();
  orders.forEach(o => {
    if (o.status !== 'Cancelled' && o.order_date) {
      const oDate = new Date(o.order_date.replace(' ', 'T'));
      if (oDate.getFullYear() === curYear) {
        const m = oDate.getMonth();
        yearRevenue[m] += parseFloat(o.total_amount) || 0;
        yearOrders[m]++;
      }
    }
  });

  return {
    '7days': { labels: sevenDaysLabels, revenue: sevenDaysRevenue, orders: sevenDaysOrders },
    '30days': { labels: monthLabels, revenue: monthRevenue, orders: monthOrders },
    '1year': { labels: yearLabels, revenue: yearRevenue, orders: yearOrders }
  };
}

class KinzChartEngine {
  constructor() {
    this.currentPeriod = '7days';
    this.currentMetric = 'revenue'; // 'revenue' or 'orders'
    this.hoverIndex = -1;
  }

  initRevenueChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.revenueCanvas = canvas;
    this.setupDPI(canvas);

    // Mouse hover tracking
    canvas.addEventListener('mousemove', (e) => {
      const rect = canvas.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const width = rect.width;
      const chartData = getChartData();
      const data = chartData[this.currentPeriod] || chartData['7days'];
      const step = width / (data.labels.length - 1);
      const index = Math.round(x / step);
      if (index >= 0 && index < data.labels.length) {
        if (this.hoverIndex !== index) {
          this.hoverIndex = index;
          this.renderRevenueChart();
        }
      }
    });

    canvas.addEventListener('mouseleave', () => {
      this.hoverIndex = -1;
      this.renderRevenueChart();
    });

    window.addEventListener('resize', () => {
      this.setupDPI(this.revenueCanvas);
      this.renderRevenueChart();
    });

    this.renderRevenueChart();
  }

  setupDPI(canvas) {
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();
    canvas.width = rect.width * dpr;
    canvas.height = rect.height * dpr;
    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);
  }

  setPeriod(period) {
    this.currentPeriod = period;
    this.renderRevenueChart();
  }

  setMetric(metric) {
    this.currentMetric = metric;
    this.renderRevenueChart();
  }

  renderRevenueChart() {
    if (!this.revenueCanvas) return;
    const canvas = this.revenueCanvas;
    const ctx = canvas.getContext('2d');
    const rect = canvas.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;

    ctx.clearRect(0, 0, w, h);

    const chartData = getChartData();
    const series = chartData[this.currentPeriod] || chartData['7days'];
    if (!series) return;

    const values = series[this.currentMetric];
    const labels = series.labels;
    const maxVal = Math.max(...values) * 1.15 || 100;
    const minVal = 0;

    const padding = { top: 30, right: 24, bottom: 40, left: 55 };
    const chartW = w - padding.left - padding.right;
    const chartH = h - padding.top - padding.bottom;

    // Draw Subtle Gridlines (Warm Ivory / Champagne Beige tone)
    ctx.strokeStyle = 'rgba(244, 226, 197, 0.45)';
    ctx.lineWidth = 1;
    ctx.setLineDash([4, 4]);

    const gridLines = 4;
    ctx.font = '11px "Poppins", sans-serif';
    ctx.fillStyle = '#7d9690';
    ctx.textAlign = 'right';

    for (let i = 0; i <= gridLines; i++) {
      const y = padding.top + (chartH / gridLines) * i;
      const val = maxVal - (maxVal / gridLines) * i;
      
      ctx.beginPath();
      ctx.moveTo(padding.left, y);
      ctx.lineTo(w - padding.right, y);
      ctx.stroke();

      const labelText = this.currentMetric === 'revenue' 
        ? '₹' + (val >= 100000 ? (val / 100000).toFixed(1) + 'L' : (val >= 1000 ? (val / 1000).toFixed(0) + 'k' : val.toFixed(0)))
        : Math.round(val);
      ctx.fillText(labelText, padding.left - 10, y + 4);
    }
    ctx.setLineDash([]);

    // Points calculation
    const points = values.map((val, idx) => {
      const x = padding.left + (chartW / (values.length - 1)) * idx;
      const y = padding.top + chartH - ((val - minVal) / (maxVal - minVal)) * chartH;
      return { x, y, val, label: labels[idx] };
    });

    if (points.length < 2) return;

    // Gradient Area Fill (Forest Green & Warm Gold)
    const areaGrad = ctx.createLinearGradient(0, padding.top, 0, padding.top + chartH);
    if (this.currentMetric === 'revenue') {
      areaGrad.addColorStop(0, 'rgba(200, 121, 39, 0.28)'); // Antique copper
      areaGrad.addColorStop(0.5, 'rgba(24, 53, 45, 0.12)'); // Forest green
      areaGrad.addColorStop(1, 'rgba(250, 248, 240, 0.02)');
    } else {
      areaGrad.addColorStop(0, 'rgba(24, 53, 45, 0.35)');
      areaGrad.addColorStop(1, 'rgba(250, 248, 240, 0.02)');
    }

    ctx.beginPath();
    ctx.moveTo(points[0].x, padding.top + chartH);
    ctx.lineTo(points[0].x, points[0].y);

    for (let i = 0; i < points.length - 1; i++) {
      const xc = (points[i].x + points[i + 1].x) / 2;
      const yc = (points[i].y + points[i + 1].y) / 2;
      ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
    }
    ctx.quadraticCurveTo(
      points[points.length - 2].x,
      points[points.length - 2].y,
      points[points.length - 1].x,
      points[points.length - 1].y
    );

    ctx.lineTo(points[points.length - 1].x, padding.top + chartH);
    ctx.closePath();
    ctx.fillStyle = areaGrad;
    ctx.fill();

    // Spline Line Stroke
    ctx.beginPath();
    ctx.moveTo(points[0].x, points[0].y);

    for (let i = 0; i < points.length - 1; i++) {
      const xc = (points[i].x + points[i + 1].x) / 2;
      const yc = (points[i].y + points[i + 1].y) / 2;
      ctx.quadraticCurveTo(points[i].x, points[i].y, xc, yc);
    }
    ctx.quadraticCurveTo(
      points[points.length - 2].x,
      points[points.length - 2].y,
      points[points.length - 1].x,
      points[points.length - 1].y
    );

    ctx.strokeStyle = this.currentMetric === 'revenue' ? '#C87927' : '#18352D';
    ctx.lineWidth = 3;
    ctx.stroke();

    // Draw Data Points & X-Labels
    ctx.font = '11px "Poppins", sans-serif';
    ctx.fillStyle = '#18352D';
    ctx.textAlign = 'center';

    points.forEach((pt, idx) => {
      // X-Axis labels
      ctx.fillText(pt.label, pt.x, padding.top + chartH + 20);

      // Point circle
      const isHovered = this.hoverIndex === idx;
      ctx.beginPath();
      ctx.arc(pt.x, pt.y, isHovered ? 6 : 4, 0, Math.PI * 2);
      ctx.fillStyle = isHovered ? '#FFFFFF' : (this.currentMetric === 'revenue' ? '#C87927' : '#18352D');
      ctx.fill();
      ctx.lineWidth = isHovered ? 3 : 2;
      ctx.strokeStyle = isHovered ? '#C87927' : '#FFFFFF';
      ctx.stroke();

      if (isHovered) {
        this.drawTooltip(ctx, pt.x, pt.y, pt.val, pt.label);
      }
    });
  }

  drawTooltip(ctx, x, y, val, label) {
    const text = this.currentMetric === 'revenue' ? '₹' + Number(val).toLocaleString('en-IN') : val + ' Orders';
    const boxW = Math.max(ctx.measureText(text).width + 24, 75);
    const boxH = 42;
    let boxX = x - boxW / 2;
    let boxY = y - boxH - 12;

    if (boxX < 10) boxX = 10;
    if (boxX + boxW > this.revenueCanvas.width / (window.devicePixelRatio || 1) - 10) {
      boxX = this.revenueCanvas.width / (window.devicePixelRatio || 1) - boxW - 10;
    }
    if (boxY < 5) boxY = y + 12;

    // Tooltip Card
    ctx.fillStyle = 'rgba(24, 53, 45, 0.95)';
    ctx.beginPath();
    ctx.roundRect(boxX, boxY, boxW, boxH, 6);
    ctx.fill();
    ctx.strokeStyle = 'rgba(200, 121, 39, 0.6)';
    ctx.lineWidth = 1;
    ctx.stroke();

    // Text
    ctx.fillStyle = '#FAF8F0';
    ctx.textAlign = 'center';
    ctx.font = 'bold 11px "Poppins", sans-serif';
    ctx.fillText(text, boxX + boxW / 2, boxY + 16);
    ctx.font = '10px "Poppins", sans-serif';
    ctx.fillStyle = '#F4E2C5';
    ctx.fillText(label, boxX + boxW / 2, boxY + 28);
  }

  initDonutChart(canvasId) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    this.donutCanvas = canvas;
    this.setupDPI(canvas);
    this.renderDonutChart();
  }

  renderDonutChart() {
    if (!this.donutCanvas) return;
    const ctx = this.donutCanvas.getContext('2d');
    const rect = this.donutCanvas.getBoundingClientRect();
    const w = rect.width;
    const h = rect.height;

    ctx.clearRect(0, 0, w, h);

    const products = (window.DB && Array.isArray(window.DB.products)) ? window.DB.products : [];
    const catMap = {};
    products.forEach(p => {
      const cat = p.category || 'Other';
      catMap[cat] = (catMap[cat] || 0) + 1;
    });

    const palette = ['#18352D', '#C87927', '#D4A62A', '#172B28', '#8B5A2B', '#2E5B50'];
    let categories = Object.keys(catMap).map((cat, idx) => ({
      category: cat,
      value: catMap[cat],
      color: palette[idx % palette.length]
    }));

    if (categories.length === 0) {
      categories = [{ category: 'No Products', value: 1, color: '#e0ded8' }];
    }
    const total = categories.reduce((sum, c) => sum + c.value, 0);

    const centerX = w / 2;
    const centerY = h / 2;
    const radius = Math.min(centerX, centerY) - 15;
    const innerRadius = radius * 0.65;

    let startAngle = -Math.PI / 2;

    categories.forEach(item => {
      const sliceAngle = (item.value / total) * (Math.PI * 2);

      ctx.beginPath();
      ctx.arc(centerX, centerY, radius, startAngle, startAngle + sliceAngle);
      ctx.arc(centerX, centerY, innerRadius, startAngle + sliceAngle, startAngle, true);
      ctx.closePath();

      ctx.fillStyle = item.color;
      ctx.fill();

      // Border separation
      ctx.lineWidth = 2.5;
      ctx.strokeStyle = '#FFFFFF';
      ctx.stroke();

      startAngle += sliceAngle;
    });

    // Center text
    ctx.fillStyle = '#172B28';
    ctx.font = 'bold 1.25rem "Poppins", serif';
    ctx.textAlign = 'center';
    ctx.fillText('100%', centerX, centerY + 2);
    ctx.font = '10px "Poppins", sans-serif';
    ctx.fillStyle = '#536b65';
    ctx.fillText('Share', centerX, centerY + 18);
  }
}

window.kinzChart = new KinzChartEngine();
