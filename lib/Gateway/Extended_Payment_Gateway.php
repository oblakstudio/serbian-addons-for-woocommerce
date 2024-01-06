<?php
/**
 * Extended_Payment_Gateway class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WooCommerce\Serbian_Addons\Gateway;

use Automattic\Jetpack\Constants;
use WC_Logger;
use WC_Logger_Interface;
use WC_Payment_Gateway;
use WP_Error;

/**
 * Extended Payment Gateway which enables easy setting up of payment gateways.
 */
abstract class Extended_Payment_Gateway extends WC_Payment_Gateway {
    /**
	 * Whether or not logging is enabled
	 *
	 * @var string[]
	 */
	public static array $log_enabled = array();

	/**
	 * Logger instance
	 *
	 * @var WC_Logger|null
	 */
	public static ?WC_Logger_Interface $logger = null;

    /**
     * Log ID
     *
     * @var string|null
     */
    public static ?string $log_id = null;

    /**
     * Constructor for the gateway.
     */
    public function __construct() {
        $this->init_base_props();
        $this->init_form_fields();
        $this->init_settings();
        $this->load_settings();

        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
    }

    /**
     * Initializes base props needed for gateway functioning.
     */
    final protected function init_base_props() {
        $props = $this->get_base_props();
        $props = wp_parse_args(
            $props,
            array(
                'has_fields'        => false,
                'order_button_text' => null,
                'supports'          => array( 'products' ),
                'icon'              => apply_filters( "{$props['id']}_icon", '' ), //phpcs:ignore WooCommerce.Commenting.HookComment
            )
        );

        foreach ( $props as $key => $value ) {
            $this->$key = $value;
        }
    }

    /**
     * Get base props needed for gateway functioning.
     *
     * Base props are: id, 'method_title', 'method_description', 'has_fields', 'supports'
     *
     * @return array
     */
    abstract protected function get_base_props(): array;

    /**
     * {@inheritDoc}
     */
    final public function init_form_fields() {
        $this->form_fields = $this->is_accessing_settings()
            ? $this->process_form_fields()
            : $this->get_raw_form_fields();
    }

    /**
     * Processes callbacks in form fields.
     *
     * @return array
     */
    final protected function process_form_fields(): array {
        return array_map(
            fn( $s ) => array_map( fn( $f ) => is_callable( $f ) ? $f() : $f, $s ),
            $this->get_raw_form_fields()
        );
    }

    /**
     * Get raw form fields.
     *
     * @return array
     */
    abstract protected function get_raw_form_fields(): array;

    /**
     * Loads settings from the database.
     */
    final protected function load_settings() {
        foreach ( $this->get_available_settings() as $key => $default ) {
            $value = $this->get_option( $key, $default );
            $value = in_array( $value, array( 'yes', 'no' ), true ) ? wc_string_to_bool( $value ) : $value;

            $this->$key = $value;
        }
        $this->enabled = wc_bool_to_string( $this->enabled );

        self::$log_enabled[ self::$log_id ] = false;
    }

    /**
     * Get available settings.
     *
     * @return array
     */
    final protected function get_available_settings(): array {
        $settings = array_map(
            fn( $f ) => $f['default'] ?? null,
            array_filter( $this->form_fields, fn( $f ) => 'title' !== $f['type'] )
        );

        foreach ( $settings as $key => $default ) {
            $value = $this->get_option( $key, $default );
            $value = in_array( $value, array( 'yes', 'no' ), true ) ? wc_string_to_bool( $value ) : $value;

            $settings[ $key ] = $value;
        }

        return $settings;
    }

    /**
     * Checks if the gateway is available for use.
     *
     * @return WP_Error|bool
     */
    public function is_valid_for_use(): WP_Error|bool {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function admin_options() {
        $is_available = $this->is_valid_for_use();

        if ( ! is_wp_error( $is_available ) ) {
            parent::admin_options();
            return;
        }

        ?>
        <div class="inline error">
            <p>
                <strong>
                    <?php esc_html_e( 'Gateway disabled', 'woocommerce' ); ?>
                </strong>:
                <?php echo esc_html( $is_available->get_error_message() ); ?>
            </p>
        </div>
        <?php
    }

    /**
	 * Checks to see whether or not the admin settings are being accessed by the current request.
	 *
	 * @return bool
	 */
	final protected function is_accessing_settings() {
        global $wp;
        $rrq = $wp->query_vars['rest_route'] ?? '';
        $req = wp_parse_args(
            wp_array_slice_assoc( wc_clean( wp_unslash( $_REQUEST ) ), array( 'page', 'tab', 'section' ) ), // phpcs:ignore WordPress.Security.NonceVerification
            array(
				'page'    => '',
				'tab'     => '',
				'section' => '',
			)
        );

        if ( ! is_admin() && ! Constants::is_true( 'REST_REQUEST' ) ) {
            return false;
        }

        return ( Constants::is_true( 'REST_REQUEST' ) && str_contains( $rrq, '/payment_gateways' ) ) ||
        ( is_admin() && 'wc-settings' === $req['page'] && 'checkout' === $req['tab'] && $this->id === $req['section'] );
	}

    /**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 *
	 * @return bool was anything saved?
	 */
	public function process_admin_options() {
		$saved = parent::process_admin_options();

		// Maybe clear logs.
		if ( 'yes' !== $this->get_option( 'debug', 'no' ) ) {
            self::$logger ??= wc_get_logger();
            self::$logger->clear( self::$log_id );
		}

		return $saved;
	}

    /**
	 * Logging method.
	 *
	 * @param string $message Log message.
	 * @param string $level Optional. Default 'info'. Possible values:
	 *                      emergency|alert|critical|error|warning|notice|info|debug.
	 */
	final public static function log( $message, $level = 'info' ) {
        if ( ! self::$log_enabled[ self::$log_id ] ) {
            return;
        }
        self::$logger ??= wc_get_logger();

		self::$logger->log( $level, $message, array( 'source' => self::$log_id ) );
	}
}
