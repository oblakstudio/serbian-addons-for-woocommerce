<?php //phpcs:disable Universal.Operators.DisallowShortTernary.Found
/**
 * Payments class file.
 *
 * @package Serbian Addons for WooCommerce
 */

namespace Oblak\WCSRB\Services;

use WC_Customer;
use WC_Order;

/**
 * Payments utility class
 *
 * @since 3.8.0
 */
class Payments {
    /**
     * Flag to determine if the address is being formatted
     *
     * @var bool
     */
    private bool $formatting = false;

    /**
     * Constructor
     *
     * @param Config $config Config instance.
     */
    public function __construct( private Config $config ) {
    }

    /**
     * Are we formatting the address?
     *
     * @return bool
     */
    public function is_formatting(): bool {
        return $this->formatting;
    }

    /**
     * Formats the order data for the payment slip or the IPS QR code
     *
     * @param  WC_Order $order   Order object.
     * @param  string   $context Context of the data.
     */
    public function format_order_data( WC_Order $order, string $context = 'display' ): array {
        $data = $order->get_meta( '_payment_slip_data', true )
            ?:
            $order->get_meta( '_wcsrb_payment_data', true );

        return array(
            'account'   => $this->format_account( $data['account'], $context ),
            'code'      => $this->format_code( $data['code'], $order ),
            'company'   => $this->format_address( $this->get_company_data(), $context ),
            'currency'  => $this->format_currency( $order->get_currency() ),
            'customer'  => $this->format_address( $order, $context ),
            'model'     => $this->format_model( $data['model'], $context ),
            'purpose'   => $this->format_purpose( $data['purpose'], $context ),
            'reference' => $this->format_reference( $data['reference'], $order, $context ),
            'total'     => $this->format_total( $order, $context ),
        );
    }

    /**
     * Gets the data for the IPS QR code
     *
     * @param  WC_Order $order   Order object.
     * @param  string   $context Context of the data.
     */
    public function get_data( WC_Order $order, string $context = 'display' ): array {
        $data = $this->format_order_data( $order, $context );

        if ( 'display' === $context ) {
            return $data;
        }

        //phpcs:disable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder
        $args = array(
            'K'  => array( 'PR' ),
            'V'  => array( '01' ),
            'C'  => array( '1' ),
            'R'  => array( 'account' ),
            'N'  => array( 'company' ),
            'I'  => array( 'currency', 'total' ),
            'P'  => array( 'customer' ),
            'SF' => array( 'code' ),
            'S'  => array( 'purpose' ),
            'RO' => array( 'model', 'reference' ),
		);
        //phpcs:enable SlevomatCodingStandard.Arrays.AlphabeticallySortedByKeys.IncorrectKeyOrder

        foreach ( $args as $key => $keys ) {
            $value = '';

            foreach ( $keys as $prop ) {
                $value .= $data[ $prop ] ?? $prop;
            }

            $args[ $key ] = $value;
        }

        return $args;
    }

    /**
     * Gets the IPS QR code string
     *
     * @param  WC_Order $order Order object.
     * @return string          The IPS QR code string.
     */
    public function get_qr_string( WC_Order $order ): string {
        $parts = array();

        foreach ( $this->get_data( $order, 'ips' ) as $key => $value ) {
            $parts[] = $key . ':' . $value;
        }

        return \implode( '|', $parts );
    }

    /**
     * Formats the currency
     *
     * @param  string $currency Currency code.
     * @return string          Formatted currency.
     */
    private function format_currency( string $currency ): string {
        return \wcsrb_is_rsd( $currency ) ? 'RSD' : $currency;
    }

    /**
     * Formats the payment reference
     *
     * @param  string   $reference Payment reference.
     * @param  WC_Order $order     Order object.
     * @param  string   $context   Context of the data.
     * @return string              Formatted payment reference.
     */
    private function format_reference( string $reference, WC_Order $order, string $context ): string {
        $replacements = \wcsrb_get_payment_reference_replacement_pairs( $order );
        $reference    = \strtr( $reference, $replacements );
		return 'display' === $context ? $reference : \str_replace( '-', '', $reference );
    }

    /**
     * Formats the payment model
     *
     * @param  string $model   Payment model.
     * @param  string $context Context of the data.
     * @return string          Formatted payment model.
     */
    private function format_model( string $model, string $context ): string {
        if ( 'mod97' === $model ) {
            return '97';
        }

        return 'ips' === $context ? '00' : '';
    }

    /**
     * Formats the payment code
     *
     * @param  string   $code  Payment code.
     * @param  WC_Order $order Order object.
     * @return string          Formatted payment code.
     */
    private function format_code( string $code, WC_Order $order ): string {
        $type = $order->get_meta( '_billing_type', true );

        return match ( true ) {
            'auto' !== $code    => $code,
            'company' === $type => '221',
            'person' === $type  => '289',
            default             => '289',
        };
    }

    /**
     * Formats the payment purpose
     *
     * @param  string $purpose Payment purpose.
     * @param  string $context Context of the data.
     * @return string          Formatted payment purpose.
     */
    private function format_purpose( string $purpose, string $context ): string {
        return 'display' === $context
            ? $purpose
            : $this->shorten_address( $purpose );
    }

    /**
     * Formats the bank account
     *
     * @param  string $account Bank account.
     * @param  string $context Context of the data.
     * @return string          Formatted bank account.
     */
    private function format_account( string $account, string $context ): string {
        return 'display' === $context
            ? \wcsrb_format_bank_acct( $account )
            : \wcsrb_format_bank_acct( $account, 'long', '' );
    }

    /**
     * Formats the total amount
     *
     * @param  WC_Order $order   Order object.
     * @param  string   $context Context of the data.
     * @return float|string      Formatted total amount.
     */
    private function format_total( WC_Order $order, string $context ): float|string {
        return 'display' === $context
            ? \floatval( $order->get_total( 'edit' ) )
            : \number_format( \floatval( $order->get_total( 'edit' ) ), 2, ',', '' );
    }

    /**
     * Formats the address
     *
     * @param  WC_Order|WC_Customer|array $target  Customer or Order object.
     * @param  string                     $context Context of the data.
     * @return string                             Formatted address.
     */
    public function format_address( WC_Order|WC_Customer|array $target, string $context = 'display' ): string {
        $this->formatting = true;

        $address = match ( true ) {
            $target instanceof WC_Order    => $target->get_formatted_billing_address(),
            $target instanceof WC_Customer => \wc_get_account_formatted_address( 'billing', $target->get_id() ),
            default                        => \WC()->countries->get_formatted_address( $target ),
        };

        $this->formatting = false;

        return 'ips' === $context ? $this->shorten_address( $address ) : $address;
	}

    /**
     * Shortens the address to fit the IPS QR code
     *
     * @param  string $address Address to shorten.
     * @return string          Shortened address.
     */
	private function shorten_address( string $address ): string {
		$address = \preg_replace( '/<br\/?>/', "\n", $address );
		$count   = 1;
		$length  = \strlen( $address );

		while ( 70 < $length && $count > 0 ) {
			$address = \preg_replace( "/\n.*$/", '', $address, -1, $count );
			$length  = \strlen( $address );
		}

		if ( 70 < $length && 0 === $count ) {
			$address = \substr( $address, 0, 70 );
		}

		return \trim( $address );
	}

    /**
     * Gets the company data
     *
     * @return array Company data.
     */
    protected function get_company_data(): array {
        $data = $this->config->get( 'company' );

        $data['company']  = $data['name'];
        $data['accounts'] = '';
        $data['name']     = '';

        return $data;
    }
}
