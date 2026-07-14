<?php
return [
    'app_name' => getenv('APP_NAME') ?: 'owebku',
    'app_env' => getenv('APP_ENV') ?: 'production',
    'app_debug' => filter_var(getenv('APP_DEBUG') ?: '0', FILTER_VALIDATE_BOOL),
    'base_url' => rtrim(getenv('APP_URL') ?: 'https://owebku.site', '/'),
    'storage_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage',
    'workspace_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'workspaces',
    'sites_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites',
];
