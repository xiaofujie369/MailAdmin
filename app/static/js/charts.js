function doubleConfirm(message) {
  return window.confirm(message) && window.confirm("Please confirm this operation one more time.");
}

function renderTrendChart(id, rawData) {
  const canvas = document.getElementById(id);
  if (!canvas || !rawData) return;
  const ctx = canvas.getContext("2d");
  const dpr = window.devicePixelRatio || 1;
  const width = canvas.clientWidth || 720;
  const height = Number(canvas.getAttribute("height")) || 260;
  canvas.width = Math.floor(width * dpr);
  canvas.height = Math.floor(height * dpr);
  ctx.scale(dpr, dpr);
  ctx.clearRect(0, 0, width, height);

  const labels = Object.keys(rawData);
  const series = [
    { key: "sent", color: "#0f9f6e", label: "Sent" },
    { key: "bounced", color: "#c2410c", label: "Bounced" },
    { key: "deferred", color: "#b7791f", label: "Deferred" },
  ];
  const values = labels.flatMap((label) => series.map((s) => Number(rawData[label][s.key] || 0)));
  const max = Math.max(1, ...values);
  const pad = { top: 22, right: 18, bottom: 34, left: 42 };
  const chartW = width - pad.left - pad.right;
  const chartH = height - pad.top - pad.bottom;

  ctx.strokeStyle = "#dde4ee";
  ctx.lineWidth = 1;
  ctx.fillStyle = "#667085";
  ctx.font = "12px system-ui, sans-serif";
  for (let i = 0; i <= 4; i += 1) {
    const y = pad.top + (chartH * i) / 4;
    const val = Math.round(max - (max * i) / 4);
    ctx.beginPath();
    ctx.moveTo(pad.left, y);
    ctx.lineTo(width - pad.right, y);
    ctx.stroke();
    ctx.fillText(String(val), 6, y + 4);
  }

  series.forEach((item) => {
    ctx.strokeStyle = item.color;
    ctx.lineWidth = 2;
    ctx.beginPath();
    labels.forEach((label, index) => {
      const x = pad.left + (labels.length === 1 ? chartW : (chartW * index) / (labels.length - 1));
      const y = pad.top + chartH - (Number(rawData[label][item.key] || 0) / max) * chartH;
      if (index === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    });
    ctx.stroke();
  });

  const step = Math.max(1, Math.ceil(labels.length / 7));
  ctx.fillStyle = "#667085";
  labels.forEach((label, index) => {
    if (index % step !== 0 && index !== labels.length - 1) return;
    const x = pad.left + (labels.length === 1 ? chartW : (chartW * index) / (labels.length - 1));
    ctx.save();
    ctx.translate(x, height - 10);
    ctx.rotate(-0.35);
    ctx.fillText(label, -22, 0);
    ctx.restore();
  });

  let legendX = pad.left;
  series.forEach((item) => {
    ctx.fillStyle = item.color;
    ctx.fillRect(legendX, 6, 10, 10);
    ctx.fillStyle = "#475467";
    ctx.fillText(item.label, legendX + 14, 15);
    legendX += 88;
  });
}
