<?php
namespace Oblak\WCRS\Core;

use Oblak\Asset\Loader;

class Scripts {
    public function __construct() {
        add_action('plugins_loaded', [$this, 'initializeLoader'], PHP_INT_MAX);
        // add_action('wcrs/localize/main.js', [$this, 'localizeScript'], 99);
    }

    public function initializeLoader() {
        Loader::getInstance()->registerNamespace('wcrs', require_once WCRS_PLUGIN_PATH . 'config/assets.php');
    }

    public function localizeScript() {
        $data = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ];

        wp_localize_script(
            'extremis/main.js',
            'wcrs',
            $data
        );
    }
}
