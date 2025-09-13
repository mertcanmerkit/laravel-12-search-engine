<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <title>API Docs</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>html,body,#swagger-ui{height:100%} body{margin:0}</style>
</head>
<body>
<div id="swagger-ui"></div>
<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
<script>
    window.ui = SwaggerUIBundle({
        url: '/openapi.yaml',
        dom_id: '#swagger-ui',
        deepLinking: true
    });
</script>
</body>
</html>
