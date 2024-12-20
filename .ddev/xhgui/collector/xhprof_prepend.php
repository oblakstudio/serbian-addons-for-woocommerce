<?php
#ddev-generated

use Xhgui\Profiler\Profiler;

if (!extension_loaded('xhprof')) {
    return;
}

require_once '/var/www/html/profiler/autoload.php';

$config = [
    'save.handler'        => Profiler::SAVER_STACK,
    'save.handler.stack'  => [
        'savers' => [
            Profiler::SAVER_UPLOAD,
            Profiler::SAVER_FILE,
        ],
        'saveAll' => FALSE,
    ],
    'save.handler.file'   => [
        'filename' => '/tmp/xhgui.data.jsonl',
    ],
    'save.handler.upload' => [
        'url'     => 'http://xhgui-ui/run/import',
        'timeout' => 3,
        'token'   => getenv('XHGUI_UPLOAD_TOKEN') ?: 'token',
    ],
    'profiler.enable' => function () {
        return true;
    },
    'profiler.simple_url' => function ($url) {
        return str_replace(getenv('DDEV_HOSTNAME'), '', $url);
    },
];

$profiler = new Profiler($config);
$profiler->start();
