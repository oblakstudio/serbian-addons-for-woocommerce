<?php
/**
 * BlockCheckoutContactFieldsTest class file.
 *
 * @package Serbian Addons for WooCommerce
 * @subpackage Tests
 */

namespace Oblak\WCSRB\Tests\Integration;

use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFields;
use Automattic\WooCommerce\Blocks\Domain\Services\CheckoutFieldsSchema\DocumentObject;
use Automattic\WooCommerce\Blocks\Package;
use Oblak\WCSRB\Core\Block_Checkout_Field_Controller;
use Oblak\WCSRB\Tests\Support\Fixtures;
use Oblak\WCSRB\Tests\Support\TestCase;

/**
 * Customer type, company name, MB and PIB in the contact section of the block checkout.
 */
class BlockCheckoutContactFieldsTest extends TestCase {
    /**
     * Load a cart - the rule document reads it.
     */
    public function set_up(): void {
        parent::set_up();

        \wc_load_cart();
    }

    public function test_stores_selling_to_both_types_get_the_toggle_and_the_company_fields(): void {
        $fields = $this->fields();

        $this->assertSame( array( 'wcsrb/is-company', 'wcsrb/company', 'wcsrb/mb', 'wcsrb/pib' ), \array_keys( $fields ) );

        foreach ( $fields as $id => $field ) {
            $this->assertSame( 'contact', $field['location'], $id );
            $this->assertFalse( $field['show_in_order_confirmation'], "{$id}: the address format already shows it" );
        }

        $this->assertSame( 'checkbox', $fields['wcsrb/is-company']['type'] );
        $this->assertSame( 'Buying as a company', $fields['wcsrb/is-company']['optionalLabel'], 'A toggle, not an "(optional)" field' );
        $this->assertSame( 'Company Number', $fields['wcsrb/mb']['label'] );
        $this->assertSame( 'Tax Number', $fields['wcsrb/pib']['label'] );
    }

    public function test_the_fields_are_registered_with_woocommerce(): void {
        $registered = Package::container()->get( CheckoutFields::class )->get_additional_fields();

        foreach ( array( 'wcsrb/is-company', 'wcsrb/company', 'wcsrb/mb', 'wcsrb/pib' ) as $id ) {
            $this->assertArrayHasKey( $id, $registered );
        }
    }

    public function test_the_toggle_is_translated_to_serbian(): void {
        $translations = new \MO();
        $translations->import_from_file( \WCRS_PLUGIN_PATH . 'languages/serbian-addons-for-woocommerce-sr_RS.mo' );

        $this->assertSame( 'Купујем као правно лице', $translations->translate( 'Buying as a company' ) );
    }

    public function test_persons_only_stores_get_no_fields(): void {
        $this->set_core_setting( 'enabled_customer_types', 'person' );

        $this->assertSame( array(), $this->controller()->get_fields() );
    }

    public function test_companies_only_stores_get_no_toggle(): void {
        $this->set_core_setting( 'enabled_customer_types', 'company' );

        $this->assertSame( array( 'wcsrb/company', 'wcsrb/mb', 'wcsrb/pib' ), \array_keys( $this->fields() ) );
    }

    /**
     * Shown and required fields per buyer.
     *
     * @return array<string,array{string,bool,string,string,array<string,array{bool,bool}>}>
     */
    public function provide_buyers(): array {
        // Field => [ shown, required ].
        return array(
            'serbian company'                     => array(
                'both',
                'hidden',
                'RS',
                true,
                array(
                    'wcsrb/is-company' => array( true, false ),
                    'wcsrb/company'    => array( true, true ),
                    'wcsrb/mb'         => array( true, true ),
                    'wcsrb/pib'        => array( true, true ),
                ),
            ),
            'serbian person'                      => array(
                'both',
                'optional',
                'RS',
                false,
                array(
                    'wcsrb/is-company' => array( true, false ),
                    'wcsrb/company'    => array( false, false ),
                    'wcsrb/mb'         => array( false, false ),
                    'wcsrb/pib'        => array( false, false ),
                ),
            ),
            'foreign buyer, company optional'     => array(
                'both',
                'optional',
                'DE',
                true,
                array(
                    'wcsrb/is-company' => array( false, false ),
                    'wcsrb/company'    => array( true, false ),
                    'wcsrb/mb'         => array( false, false ),
                    'wcsrb/pib'        => array( false, false ),
                ),
            ),
            'foreign buyer, company required'     => array(
                'both',
                'required',
                'DE',
                false,
                array(
                    'wcsrb/company' => array( true, true ),
                    'wcsrb/mb'      => array( false, false ),
                ),
            ),
            'foreign buyer, company hidden'       => array(
                'both',
                'hidden',
                'DE',
                false,
                array( 'wcsrb/company' => array( false, false ) ),
            ),
            'serbian buyer of a companies-only store' => array(
                'company',
                'hidden',
                'RS',
                false,
                array(
                    'wcsrb/company' => array( true, true ),
                    'wcsrb/mb'      => array( true, true ),
                    'wcsrb/pib'     => array( true, true ),
                ),
            ),
        );
    }

    /**
     * Visibility and requirement rules.
     *
     * @dataProvider provide_buyers
     *
     * @param string                          $types      Enabled customer types.
     * @param string                          $setting    WooCommerce company field setting.
     * @param string                          $country    Billing country.
     * @param bool                            $is_company Company toggle.
     * @param array<string,array{bool,bool}>  $expected   Field => [ shown, required ].
     */
    public function test_rules( string $types, string $setting, string $country, bool $is_company, array $expected ): void {
        $this->set_core_setting( 'enabled_customer_types', $types );
        \update_option( 'woocommerce_checkout_company_field', $setting );

        $document = new DocumentObject(
            array(
                'customer' => array(
                    'additional_fields' => array( 'wcsrb/is-company' => $is_company ),
                    'billing_address'   => array( 'country' => $country ),
                ),
            ),
        );
        $checkout = Package::container()->get( CheckoutFields::class );
        $fields   = $this->fields();

        foreach ( $expected as $id => [ $shown, $required ] ) {
            $this->assertSame( ! $shown, $checkout->is_hidden_field( $fields[ $id ], $document ), "{$id} hidden" );
            $this->assertSame( $required, $checkout->is_required_field( $fields[ $id ], $document ), "{$id} required" );
        }
    }

    public function test_company_numbers_are_validated_like_the_classic_checkout(): void {
        $fields = $this->fields();
        $mb     = $fields['wcsrb/mb']['validate_callback'];
        $pib    = $fields['wcsrb/pib']['validate_callback'];

        $this->assertTrue( $mb( Fixtures::MB, $fields['wcsrb/mb'] ) );
        $this->assertTrue( $pib( Fixtures::PIB, $fields['wcsrb/pib'] ) );
        $this->assertSame( 'Company number is invalid', $mb( '12345678', $fields['wcsrb/mb'] )->get_error_message() );
        $this->assertSame( 'Company Tax Number is invalid', $pib( '123456789', $fields['wcsrb/pib'] )->get_error_message() );
    }

    public function test_the_fields_are_prefilled_from_the_customer(): void {
        $customer = new \WC_Customer( self::factory()->user->create( array( 'role' => 'customer' ) ) );
        $customer->set_billing_company( 'Firma d.o.o.' );
        $customer->update_meta_data( 'billing_type', 'company' );
        $customer->update_meta_data( 'billing_mb', Fixtures::MB );
        $customer->update_meta_data( 'billing_pib', Fixtures::PIB );
        $customer->save();

        $checkout = Package::container()->get( CheckoutFields::class );

        $this->assertTrue( $checkout->get_field_from_object( 'wcsrb/is-company', $customer ) );
        $this->assertSame( 'Firma d.o.o.', $checkout->get_field_from_object( 'wcsrb/company', $customer ) );
        $this->assertSame( Fixtures::MB, $checkout->get_field_from_object( 'wcsrb/mb', $customer ) );
        $this->assertSame( Fixtures::PIB, $checkout->get_field_from_object( 'wcsrb/pib', $customer ) );
    }

    public function test_the_company_name_is_prefilled_from_the_saved_customer(): void {
        $saved = new \WC_Customer( self::factory()->user->create( array( 'role' => 'customer' ) ) );
        $saved->set_billing_company( 'Firma d.o.o.' );
        $saved->update_meta_data( 'billing_type', 'company' );
        $saved->save();

        // The Checkout block resets the hidden core Company of the session customer.
        $session = new \WC_Customer( $saved->get_id(), true );
        $session->set_billing_company( '' );

        $this->assertSame( 'Firma d.o.o.', Package::container()->get( CheckoutFields::class )->get_field_from_object( 'wcsrb/company', $session ) );
    }

    public function test_orders_are_not_prefilled(): void {
        // WooCommerce < 11.3 also asks for defaults when showing an order in admin - that would list them twice.
        $order = \wc_create_order();
        $order->update_meta_data( '_billing_mb', Fixtures::MB );

        $this->assertSame( '', Package::container()->get( CheckoutFields::class )->get_field_from_object( 'wcsrb/mb', $order ) );
    }

    /**
     * Get the controller.
     *
     * @return Block_Checkout_Field_Controller
     */
    private function controller(): Block_Checkout_Field_Controller {
        return \xwp_app( 'wcsrb' )->get( Block_Checkout_Field_Controller::class );
    }

    /**
     * Field definitions keyed by ID.
     *
     * @return array<string,array<string,mixed>>
     */
    private function fields(): array {
        return \array_column( $this->controller()->get_fields(), null, 'id' );
    }
}
