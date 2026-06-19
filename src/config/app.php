<?php
return [
    'app_name' => 'owebku',
    // Kosongkan base_url agar otomatis dideteksi dari server, atau isi manual jika di production
    'base_url' => '',
    'storage_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage',
    'workspace_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'workspaces',
    'sites_path' => dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'sites',
];
