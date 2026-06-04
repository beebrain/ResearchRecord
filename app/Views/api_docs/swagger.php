<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= esc($title ?? 'Research Record API') ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.18.2/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .rr-docs-banner {
            background: #1e3a5f;
            color: #fff;
            padding: 10px 20px;
            font-family: system-ui, sans-serif;
            font-size: 14px;
        }
        .rr-docs-banner a { color: #93c5fd; }
    </style>
</head>
<body>
<div class="rr-docs-banner">
    <strong>Research Record API Docs</strong>
    — Public endpoints, ไม่ต้องใช้ token.
</div>
<div id="swagger-ui"></div>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.18.2/swagger-ui-bundle.js" crossorigin></script>
<script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5.18.2/swagger-ui-standalone-preset.js" crossorigin></script>
<script>
window.onload = function () {
    window.ui = SwaggerUIBundle({
        url: <?= json_encode($specUrl, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP) ?>,
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
        plugins: [SwaggerUIBundle.plugins.DownloadUrl],
        layout: 'StandaloneLayout',
        persistAuthorization: true,
        tryItOutEnabled: true,
        displayRequestDuration: true,
        defaultModelsExpandDepth: 1,
        docExpansion: 'list',
    });
};
</script>
</body>
</html>
