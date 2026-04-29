</main>
<script>
// Оборачиваем каждую .data-table в скроллируемый контейнер, чтобы
// длинные таблицы скроллились внутри, а не растягивали страницу на мобилке.
(function() {
  document.querySelectorAll('.data-table').forEach(function(t) {
    if (t.parentElement && t.parentElement.classList.contains('table-wrap')) return;
    var wrap = document.createElement('div');
    wrap.className = 'table-wrap';
    t.parentNode.insertBefore(wrap, t);
    wrap.appendChild(t);
  });
})();
</script>
</body>
</html>
