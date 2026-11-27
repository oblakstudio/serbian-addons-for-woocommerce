<?php
/**
 * Block_Checkout_Field_Controller class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Core;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Package;
use Oblak\WCSRB\Services\Config;
use XWP\DI\Decorators\Action;
use XWP\DI\Decorators\Filter;
use XWP\DI\Decorators\Handler;

/**
 * Core address fields in the block checkout.
 *
 * The block checkout asks for the company in its contact section (customer type, company name, MB, PIB), so the
 * core Company field is hidden there - and so are address 2 and the district of Serbian addresses when "Field
 * removal" is on. WooCommerce reads one company setting and one locale for both checkouts, so this applies only
 * while the Checkout block renders and in Store API requests: the classic checkout and My Account keep their fields.
 *
 * @since 4.0.0
 */
#[Handler( tag: 'woocommerce_init', priority: 99, container: 'wcsrb' )]
class Block_Checkout_Field_Controller {
    /**
     * Contact field keys (namespace wcsrb/).
     *
     * @var array<int,string>
     */
    public const FIELDS = array( 'is-company', 'company', 'mb', 'pib' );

    /**
     * Whether the Checkout block is rendering in this request.
     *
     * @var bool
     */
    private bool $block_checkout = false;

    /**
     * Whether to read WooCommerce's own company setting (skip hide_company_setting()).
     *
     * @var bool
     */
    private bool $real_company_setting = false;

    /**
     * Constructor
     *
     * @param  Config $config Config Service.
     */
    public function __construct( private Config $config ) {
    }

    /**
     * Marks the request as a block checkout before the Checkout block reads its fields and the locale.
     *
     * Checkout::enqueue_assets() fires this before enqueue_data() (countryData, then defaultFields);
     * `woocommerce_blocks_checkout_enqueue_data` would be too late - it fires after countryData is built.
     */
    #[Action( tag: 'woocommerce_blocks_enqueue_checkout_block_scripts_before', priority: 10 )]
    public function enter_block_checkout(): void {
        $this->block_checkout = true;
        $this->reset_country_caches();
    }

    /**
     * Ends the block checkout once the Checkout block has built its data (fields, locale, address formats).
     */
    #[Action( tag: 'woocommerce_blocks_enqueue_checkout_block_scripts_after', priority: 10 )]
    public function leave_block_checkout(): void {
        $this->block_checkout = false;
        $this->reset_country_caches();
    }

    /**
     * Removes the MB / PIB placeholders from the Serbian address format the Checkout block gets.
     *
     * The block formats its address cards in JavaScript, which knows WooCommerce's placeholders only - it would show
     * "{mb}, {pib}". Only while the block renders: order emails, sent during the Store API checkout, keep them.
     *
     * @param  array<string,string> $formats Address formats.
     * @return array<string,string>
     */
    #[Filter( tag: 'woocommerce_localisation_address_formats', priority: 1000000 )]
    public function remove_company_number_placeholders( array $formats ): array {
        if ( $this->block_checkout && isset( $formats['RS'] ) ) {
            $formats['RS'] = \str_replace( array( "{mb}\n", "{pib}\n", '{mb}', '{pib}' ), '', $formats['RS'] );
        }

        return $formats;
    }

    /**
     * Hides the core Company field of the block checkout.
     *
     * @param  mixed $value Value to return instead of the option (false: read the option).
     * @return mixed
     */
    #[Filter( tag: 'pre_option_woocommerce_checkout_company_field', priority: 10 )]
    public function hide_company_setting( mixed $value ): mixed {
        return ! $this->real_company_setting && $this->in_block_checkout() ? 'hidden' : $value;
    }

    /**
     * Registers the customer fields of the block checkout (after woocommerce_init, as the API requires).
     */
    #[Action( tag: 'init', priority: 10 )]
    public function register_fields(): void {
        foreach ( $this->get_fields() as $field ) {
            \woocommerce_register_additional_checkout_field( $field );
        }
    }

    /**
     * Keeps WooCommerce from listing the contact fields on the admin order screen - the classic admin billing
     * fields edit the customer type, MB and PIB already.
     *
     * @param  array<string,array<string,mixed>> $fields Admin billing fields.
     * @return array<string,array<string,mixed>>
     */
    #[Filter( tag: 'woocommerce_admin_billing_fields', priority: 20 )]
    public function remove_admin_fields( array $fields ): array {
        return \array_diff_key( $fields, \array_flip( $this->field_ids() ) );
    }

    /**
     * Keeps the contact fields out of the block order confirmation's "Additional information".
     *
     * The wrapper block prints its heading when the order has any additional field value - unfiltered by
     * show_in_order_confirmation, and WooCommerce < 11 reports an unchecked toggle as false (a value).
     *
     * @param  array<string,mixed> $block Parsed block.
     * @return array<string,mixed>
     */
    #[Filter( tag: 'render_block_data', priority: 10 )]
    public function skip_fields_in_order_confirmation( array $block ): array {
        if ( 'woocommerce/order-confirmation-additional-fields-wrapper' === ( $block['blockName'] ?? '' ) ) {
            $this->skip_fields();
        }

        return $block;
    }

    /**
     * Drops the contact fields for the rest of the request: My Account > Account details and the order confirmation.
     *
     * WooCommerce shows contact fields in Account details and validates them on save, even if the form does not show
     * them (a companies-only store would ask for the company name to change a first name). Customers edit their
     * company data in the classic billing address form.
     */
    #[Action( tag: 'woocommerce_edit_account_form_start', priority: 10 )]
    #[Action( tag: 'woocommerce_save_account_details', priority: 9 )]
    public function skip_fields(): void {
        $checkout = Package::container()->get( CheckoutFields::class );

        foreach ( $this->field_ids() as $id ) {
            $checkout->deregister_checkout_field( $id );
        }
    }

    /**
     * Customer type, company name, MB and PIB - the contact section of the block checkout.
     *
     * Like the classic checkout: the customer type exists only for Serbian addresses, a Serbian company enters its
     * name, MB and PIB (whatever WooCommerce's company setting says), a foreign buyer gets a regular company field.
     *
     * @return array<int,array<string,mixed>>
     */
    public function get_fields(): array {
        $types = $this->config->get( 'core', 'enabled_customer_types' );

        if ( 'person' === $types ) {
            return array();
        }

        $serbian = $this->billing_country_is( 'RS' );
        $company = 'company' === $types ? $serbian : array( 'allOf' => array( $serbian, $this->buying_as_company() ) );
        $setting = $this->company_setting();
        $fields  = array();

        if ( 'both' === $types ) {
            $toggle   = \__( 'Buying as a company', 'serbian-addons-for-woocommerce' );
            $fields[] = $this->field(
                'is-company',
                $toggle,
                array(
                    'hidden'        => array( 'not' => $serbian ),
                    // A toggle - WooCommerce would label it "(optional)".
                    'optionalLabel' => $toggle,
                    'type'          => 'checkbox',
                ),
            );
        }

        $fields[] = $this->field(
            'company',
            \__( 'Company name', 'woocommerce' ),
            array(
                'attributes' => array( 'autocomplete' => 'organization' ),
                'hidden'     => 'hidden' === $setting
                    ? array( 'not' => $company )
                    : array( 'allOf' => array( $serbian, array( 'not' => $company ) ) ),
                'required'   => 'required' === $setting
                    ? array( 'anyOf' => array( $company, array( 'not' => $serbian ) ) )
                    : $company,
            ),
        );

        foreach ( array( 'mb', 'pib' ) as $key ) {
            $fields[] = $this->field(
                $key,
                \wcsrb_get_company_fields()[ $key ]['label'],
                array(
                    'hidden'            => array( 'not' => $company ),
                    'required'          => $company,
                    'validate_callback' => $this->number_validator( $key ),
                ),
            );
        }

        return $fields;
    }

    /**
     * Hides the core fields the block checkout does not ask for, in every country locale.
     *
     * Runs after Address_Field_Controller::add_custom_locale_field_data(), which shows Company for Serbian companies.
     *
     * @param  array<string,array<string,mixed>> $locale Country locales.
     * @return array<string,array<string,mixed>>
     */
    #[Filter( tag: 'woocommerce_get_country_locale', priority: 1002 )]
    public function hide_core_fields( array $locale ): array {
        if ( ! $this->in_block_checkout() ) {
            return $locale;
        }

        foreach ( $locale as &$fields ) {
            $fields = $this->hide( $fields, 'company' );
        }

        // The classic customer type, MB and PIB are not part of a block checkout address - the Store API would
        // require them for every Serbian address (it validates all required locale fields).
        $locale['RS'] = $this->hide( $locale['RS'] ?? array(), 'type', 'mb', 'pib' );

        if ( $this->config->get( 'core', 'remove_unneeded_fields' ) ) {
            $locale['RS'] = $this->hide( $locale['RS'], 'address_2', 'state' );
        }

        return $locale;
    }

    /**
     * Hides the core Company field for countries without a locale of their own.
     *
     * @param  array<string,array<string,mixed>> $fields Default locale fields.
     * @return array<string,array<string,mixed>>
     */
    #[Filter( tag: 'woocommerce_get_country_locale_default', priority: 1000000 )]
    public function hide_default_company( array $fields ): array {
        return $this->in_block_checkout() ? $this->hide( $fields, 'company' ) : $fields;
    }

    /**
     * Contact field IDs.
     *
     * @return array<int,string>
     */
    private function field_ids(): array {
        return \array_map( static fn( string $key ): string => "wcsrb/{$key}", self::FIELDS );
    }

    /**
     * Contact field definition.
     *
     * @param  string              $key   Field key (without the namespace).
     * @param  string              $label Field label.
     * @param  array<string,mixed> $args  Field arguments.
     * @return array<string,mixed>
     */
    private function field( string $key, string $label, array $args ): array {
        return \array_merge(
            array(
                'hidden'                     => false,
                'id'                         => "wcsrb/{$key}",
                'label'                      => $label,
                'location'                   => 'contact',
                'required'                   => false,
                // The address format prints the company, MB and PIB already.
                'show_in_order_confirmation' => false,
                'type'                       => 'text',
            ),
            $args,
        );
    }

    /**
     * Validates MB / PIB with the classic checkout's validators and messages.
     *
     * @param  'mb'|'pib' $key Field key.
     * @return \Closure(mixed): (true|\WP_Error)
     */
    private function number_validator( string $key ): \Closure {
        [ $callback, $message ] = 'mb' === $key
            ? array( '\Oblak\validateMB', \__( 'Company number is invalid', 'serbian-addons-for-woocommerce' ) )
            : array( '\Oblak\validatePIB', \__( 'Company Tax Number is invalid', 'serbian-addons-for-woocommerce' ) );

        // bool, not true: standalone true is a PHP 8.2 type (the plugin supports 8.0).
        return static fn( mixed $value ): bool|\WP_Error => '' === (string) $value || $callback( (string) $value )
            ? true
            : new \WP_Error( "billing_{$key}_validation", $message );
    }

    /**
     * Rule: the billing address is in the given country.
     *
     * @param  string $country Country code.
     * @return array<string,mixed>
     */
    private function billing_country_is( string $country ): array {
        return $this->customer_rule( 'billing_address', array( 'country' => array( 'const' => $country ) ) );
    }

    /**
     * Rule: the buyer ticked "Buying as a company".
     *
     * @return array<string,mixed>
     */
    private function buying_as_company(): array {
        return $this->customer_rule( 'additional_fields', array( 'wcsrb/is-company' => array( 'const' => true ) ) );
    }

    /**
     * JSON schema rule on a customer object of the checkout document.
     *
     * @param  string              $prop       Customer property.
     * @param  array<string,mixed> $properties Required properties of it, with their schemas.
     * @return array<string,mixed>
     */
    private function customer_rule( string $prop, array $properties ): array {
        $object = static fn( array $props ): array => array(
            'properties' => $props,
            'required'   => \array_keys( $props ),
            'type'       => 'object',
        );

        return $object( array( 'customer' => $object( array( $prop => $object( $properties ) ) ) ) );
    }

    /**
     * WooCommerce's own company field setting (optional, required or hidden).
     *
     * @return string
     */
    private function company_setting(): string {
        $this->real_company_setting = true;
        $setting                    = \Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils::get_company_field_visibility();
        $this->real_company_setting = false;

        return $setting;
    }

    /**
     * WooCommerce caches the locale and the address formats per request - build them again.
     */
    private function reset_country_caches(): void {
        \WC()->countries->locale          = array();
        \WC()->countries->address_formats = array();
    }

    /**
     * Is this the block checkout (rendering or a Store API request)?
     *
     * @return bool
     */
    private function in_block_checkout(): bool {
        return $this->block_checkout || \WC()->is_store_api_request();
    }

    /**
     * Marks locale fields hidden and optional.
     *
     * @param  array<string,array<string,mixed>> $fields Locale fields.
     * @param  string                            ...$keys Field keys.
     * @return array<string,array<string,mixed>>
     */
    private function hide( array $fields, string ...$keys ): array {
        foreach ( $keys as $key ) {
            $fields[ $key ] = \array_merge(
                $fields[ $key ] ?? array(),
                array(
                    'hidden'   => true,
                    'required' => false,
                ),
            );
        }

        return $fields;
    }
}
