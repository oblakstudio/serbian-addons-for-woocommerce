<?php
namespace Oblak\WCRS\Utils;

class Installer {
    public static function init() {
        add_action('init', [__CLASS__, 'check_version']);
        add_action('plugin_action_links_' . WCRS_PLUGIN_BASENAME, [__CLASS__, 'plugin_action_links']);
    }

    public static function check_version() {
        if (defined('IFRAME_REQUEST') && version_compare(get_option('wcrs_version', '0.0.1'), WCSRB()->version, '<')) {
            self::install();
            do_action('wcrs_updated');
        }
    }

    public static function install() {
        if (!is_blog_installed()) {
            return;
        }
        if (get_transient('wcrs_installing') === 'yes') {
            return;
        }

        set_transient('wcrs_installing', 'yes', MINUTE_IN_SECONDS * 5);
        wc_maybe_define_constant('WCRS_INSTALLING', true);

        self::createOptions();
        self::update_wcrs_version();

        delete_transient('wcrs_installing');
        do_action('wcrs_installed');
    }

    public static function createOptions() {
        add_option('woocommerce_serbian', [
            'enabled_customer_type'  => 'both',
            'remove_unneeded_fields' => 'yes',
            'fix_currency_symbol'    => 'no',
        ]);
    }

    /**
     * Update WCRS version to current.
     */
    public static function update_wcrs_version() {
        update_option('wcrs_version', WCSRB()->version);
    }

    /**
     * Show action links on the plugin screen
     *
     * @param  mixed $links Plugin Action links
     * @return array
     */
    public static function plugin_action_links($links) {
        $action_links = [
            'settings' => sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                admin_url('admin.php?page=wc-settings'),
                esc_attr__('Settings', 'serbian-addons-for-woocommerce'),
                esc_html__('Settings', 'serbian-addons-for-woocommerce'),
            ),
        ];

        return array_merge($action_links, $links);
    }
}
