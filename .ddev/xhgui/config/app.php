<?php #ddev-generated

$env = getenv();
$mup = $env['XHGUI_MONGO_USERNAME'] ?? false
    ? [
        'username' => $env['XHGUI_MONGO_USERNAME'],
        'password' => $env['XHGUI_MONGO_PASSWORD'],
    ] : [];

return [
    'save.handler'          => $env['XHGUI_SAVE_HANDLER'] ?? 'mongodb',
    'pdo'                   => [
        'dsn'        => $env['XHGUI_PDO_DSN']           ?? null,
        'user'       => $env['XHGUI_PDO_USER']          ?? null,
        'pass'       => $env['XHGUI_PDO_PASS']          ?? null,
        'table'      => $env['XHGUI_PDO_TABLE']         ?? 'results',
        'tableWatch' => $env['XHGUI_PDO_TABLE_WATCHES'] ?? 'watches',
    ],

    // Database options for MongoDB.
    'mongodb'               => [
        'hostname'      => $env['XHGUI_MONGO_HOSTNAME'] ?? 'xhgui-mongo',
        'port'          => $env['XHGUI_MONGO_PORT']     ?? 27017,
        'database'      => $env['XHGUI_MONGO_DATABASE'] ?? 'xhprof',
        'options'       => $mup,
        'driverOptions' => [],
    ],
    'run.view.filter.names' => ['Zend*', 'Composer*'],
    'upload.token'          => $env['XHGUI_UPLOAD_TOKEN'] ?? 'token',
    'path.prefix'           => '/xhgui',
    'timezone'              => '',
    'date.format'           => 'M jS H:i:s',
    'detail.count'          => 6,
    'page.limit'            => 25,
];
