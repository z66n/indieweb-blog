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