</div>
</div>
<script src="/app/src/js/toast.js"></script>
<script src="/app/src/js/spa.js"></script>
<?php if (isset($js_files) && is_array($js_files)): ?>
<?php foreach ($js_files as $js): ?>
<script src="/app/src/js/<?php echo $js; ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<script>
    lucide.createIcons();
</script>
</body>

</html>