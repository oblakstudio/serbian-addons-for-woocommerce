<?php

namespace Oblak\WCRS;

use Oblak\WCRS\Utils\Installer;

class WooCommerceSerbian {
    /**
     * Serbian WooCommerce version.
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * @var WooCommerceSerbian Plugin instance
     */
    protected static $instance = null;

    /**
     * @var array Plugin settings
     */
    protected $options = [];

    public function __clone() {
        wc_doing_it_wrong(__FUNCTION__, 'Cloning is disabled', 'WooSync 2.1');
    }

    public function __wakeup() {
        wc_doing_it_wrong(__FUNCTION__, 'Unserializing is disabled', 'WooSync 2.1');
    }

    /**
     * Retrieves the singleton instance
     *
     * @return WooCommerceSerbian
     */
    public static function getInstance() {
        return is_null(self::$instance)
            ? self::$instance = new self()
            : self::$instance;
    }

    private function __construct() {
        $this->defineConstants();
        $this->loadClasses();
        $this->initHooks();
    }

    private function defineConstants() {
        $this->define('WCRS_ABSPATH', dirname(WCRS_PLUGIN_FILE) . '/');
        $this->define('WCRS_PLUGIN_BASENAME', plugin_basename(WCRS_PLUGIN_FILE));
        $this->define('WCRS_PLUGIN_PATH', plugin_dir_path(WCRS_PLUGIN_FILE));
        $this->define('WCRS_VERSION', $this->version);
    }

    /**
     * Define constant if not already set.
     *
     * @param string      $name  Constant name.
     * @param string|bool $value Constant value.
     */
    private function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    private function loadClasses() {
        Installer::init();
        new Core\Scripts();
    }

    private function initHooks() {
        register_activation_hook(WCRS_PLUGIN_FILE, ['Oblak\WCRS\Utils\Installer', 'install']);
        add_action('plugins_loaded', [$this, 'loadTextdomain']);

        add_action('init', [$this, 'init']);
    }

    public function loadTextdomain() {
        load_plugin_textdomain(
            'serbian-addons-for-woocommerce',
            false,
            dirname(WCRS_PLUGIN_BASENAME).'/languages'
        );
    }

    /**
     * What type of request is this?
     *
     * Copied verbatim from WooCommerce
     *
     * @param  string $type admin, ajax, cron or frontend.
     * @return bool
     */
    public function is_request($type) {
        switch ($type) {
            case 'admin':
                return is_admin();
            case 'ajax':
                return defined('DOING_AJAX');
            case 'cron':
                return defined('DOING_CRON');
            case 'frontend':
                return (!is_admin() || defined('DOING_AJAX')) && !defined('DOING_CRON') && !WC()->is_rest_api_request();
        }
    }

    public function init() {
        $this->options = get_option('woocommerce_serbian');

        if ($this->is_request('admin')) {
            new Admin\Settings();
        }

        new CustomerType\FieldCustomizer();
        new CustomerType\FieldValidation();
        new CustomerType\FieldDisplay();
        new Tweaks();
    }

    public function getOptions() {
        return $this->options;
    }
}
