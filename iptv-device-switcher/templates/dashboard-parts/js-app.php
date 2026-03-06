<script type="text/babel">
<?php
// NOTE: Safely modularized: this file is now a small orchestrator that prints the same JS app,
// split into smaller, ordered parts. No JS logic changed.

$__streamos_js_parts = __DIR__ . '/js-app';

require $__streamos_js_parts . '/01-icons-library.php';
require $__streamos_js_parts . '/02-shared-components.php';
require $__streamos_js_parts . '/03-complex-views.php';
require $__streamos_js_parts . '/04-main-app.php';
?>
</script>
