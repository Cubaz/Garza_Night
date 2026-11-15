</main>

<footer class="footer">
  <span><?= e(APP_NOMBRE) ?> · Proyecto de Programación Web</span>
  <span>Solo comunidad universitaria · Eventos con bebidas: solo mayores de 18 años</span>
</footer>

<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<?php foreach (($scripts ?? []) as $js): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= e($js) ?>"></script>
<?php endforeach; ?>
</body>
</html>
