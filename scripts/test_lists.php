<?php
require '/var/www/vendor/autoload.php';
$app = require '/var/www/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$wl = \App\Models\EntityWhitelist::active()->get();
echo 'Whitelist entries activas: ' . $wl->count() . PHP_EOL;
foreach ($wl as $e) {
    echo '  [' . ($e->entity_type ?? 'ANY') . '] ' . $e->term . PHP_EOL;
}

$bl = \App\Models\EntityBlacklist::active()->get();
echo 'Blacklist entries activas: ' . $bl->count() . PHP_EOL;
foreach ($bl as $e) {
    echo '  [' . ($e->entity_type ?? 'ANY') . '] ' . $e->term . PHP_EOL;
}
