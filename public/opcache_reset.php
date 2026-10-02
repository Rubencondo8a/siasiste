<?php
header('Content-Type: text/plain');
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "OpCache reset OK\n";
} else {
    echo "OpCache no disponible\n";
}
echo "OPcache status:\n";
if (function_exists('opcache_get_status')) {
    $s = opcache_get_status(false);
    echo "  enabled: " . ($s['opcache_enabled'] ? 'yes' : 'no') . "\n";
    echo "  scripts: " . ($s['opcache_statistics']['num_cached_scripts'] ?? 'N/A') . "\n";
}
