<html>

<script>
  (function() {
    const toggle = document.getElementById('theme-toggle');
    const root   = document.documentElement;
    // Initialize from saved preference or OS setting
    const saved = localStorage.getItem('theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (saved === 'dark' || (!saved && prefersDark)) {
      root.classList.add('dark');
      toggle.textContent = '☀️';
    }

    toggle.addEventListener('click', () => {
      const isDark = root.classList.toggle('dark');
      localStorage.setItem('theme', isDark ? 'dark' : 'light');
      toggle.textContent = isDark ? '☀️' : '🌙';
    });
  })();
</script>
<body>
    <footer>
        <p>&copy; <?= date('Y'); ?> Exam Tracker</p>
    </footer>
</body>
</html>
