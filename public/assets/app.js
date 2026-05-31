function doubleConfirm(message) {
  return window.confirm(message) && window.confirm('请再次确认该危险操作。');
}

function renderTrend(id, rows) {
  const canvas = document.getElementById(id);
  if (!canvas) return;
  const ctx = canvas.getContext('2d');
  const width = canvas.clientWidth || 640;
  const height = Number(canvas.getAttribute('height')) || 240;
  const dpr = window.devicePixelRatio || 1;
  canvas.width = width * dpr;
  canvas.height = height * dpr;
  ctx.scale(dpr, dpr);
  ctx.clearRect(0, 0, width, height);
  const series = [
    ['sent', '#0f9f6e', '成功'],
    ['bounced', '#c2410c', '退信'],
    ['deferred', '#b7791f', '延迟'],
  ];
  const max = Math.max(1, ...rows.flatMap((row) => series.map(([key]) => Number(row[key] || 0))));
  const pad = { top: 24, right: 18, bottom: 34, left: 42 };
  const cw = width - pad.left - pad.right;
  const ch = height - pad.top - pad.bottom;
  ctx.strokeStyle = '#dde4ee';
  ctx.fillStyle = '#667085';
  ctx.font = '12px system-ui';
  for (let i = 0; i <= 4; i++) {
    const y = pad.top + ch * i / 4;
    ctx.beginPath(); ctx.moveTo(pad.left, y); ctx.lineTo(width - pad.right, y); ctx.stroke();
    ctx.fillText(String(Math.round(max - max * i / 4)), 6, y + 4);
  }
  series.forEach(([key, color, label], si) => {
    ctx.strokeStyle = color; ctx.lineWidth = 2; ctx.beginPath();
    rows.forEach((row, i) => {
      const x = pad.left + (rows.length <= 1 ? 0 : cw * i / (rows.length - 1));
      const y = pad.top + ch - Number(row[key] || 0) / max * ch;
      if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
    });
    ctx.stroke(); ctx.fillStyle = color; ctx.fillRect(pad.left + si * 80, 6, 10, 10); ctx.fillText(label, pad.left + si * 80 + 14, 15);
  });
}
