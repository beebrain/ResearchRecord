<?php
/**
 * CSRF Phase 1 includes
 *
 * Drop this partial into any view's <head> AFTER jQuery is loaded:
 *   <?= view('partials/csrf_head') ?>
 *
 * It renders:
 *   - <meta name="csrf-token"> with the current CI4 token
 *   - csrf-setup.js which attaches X-CSRF-TOKEN to every non-GET AJAX call
 *     and rotates the cached token from the response header.
 *
 * Backend enforcement is currently OFF — adding this partial is safe and
 * has no behavioral effect until Filters.php enables 'csrf'.
 */
?>
<meta name="csrf-token" content="<?= csrf_hash() ?>">
<script src="<?= base_url('assets/js/csrf-setup.js') ?>"></script>
