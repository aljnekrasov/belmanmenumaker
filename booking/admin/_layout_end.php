</main>
<script>
(function() {
    var toggle = document.getElementById('menuToggle');
    var backdrop = document.getElementById('sidebarBackdrop');
    var body = document.body;
    if (!toggle) return;
    function close() { body.classList.remove('menu-open'); }
    toggle.addEventListener('click', function() {
        body.classList.toggle('menu-open');
    });
    if (backdrop) backdrop.addEventListener('click', close);
    document.querySelectorAll('.sidebar-nav a').forEach(function(a) {
        a.addEventListener('click', close);
    });
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) close();
    });
})();
</script>
</body>
</html>
