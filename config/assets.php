<?php
return [
    'version'   => WCSRB()->version,
    'priority'  => 50,
    'dist_path' => WCRS_PLUGIN_PATH . 'dist',
    'dist_uri'  => plugins_url('dist', WCRS_PLUGIN_BASENAME),
    'assets'    => [
        'front' => [
            'styles'  => ['styles/main.css'],
            'scripts' => ['scripts/main.js'],
        ],
        'admin' => [
            'styles'  => [],
            'scripts' => [],
        ]
    ]
];
