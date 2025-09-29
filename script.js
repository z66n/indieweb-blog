// Convert UTC ISO 8601 to local time
document.querySelectorAll('time.dt-published').forEach(el => {
    const utcTime = el.getAttribute('datetime');
    if (!utcTime) return;
    
    const dt = new Date(utcTime); // JS parses ISO 8601 UTC
    const formatted = dt.toLocaleString([], {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
    el.textContent = formatted;
});

// Click or tap to toggle metadata visibility
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".wm").forEach(wm => {
    wm.addEventListener("click", e => {
      // If click was inside a link (<a> or its children), don’t toggle
      if (e.target.closest("a")) return;

      wm.classList.toggle("show-meta");
    });
  });
});
