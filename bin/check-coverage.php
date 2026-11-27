<?php
/**
 * Fails when any payment gateway file is below 100% line coverage.
 *
 * Usage: php bin/check-coverage.php [clover.xml]
 *
 * @package Serbian Addons for WooCommerce
 */

// phpcs:disable

$root   = dirname( __DIR__ );
$clover = $argv[1] ?? $root . '/.cache/coverage/clover.xml';
$scope  = array(
	'config/definition.php',
	'config/pg-slip-settings.php',
	'lib/Admin/Order_Edit_Page_Controller.php',
	'lib/Functions/wcsrb-payment-slip.php',
	'lib/Gateway/Gateway_Payment_Slip.php',
	'lib/Gateway/Gateway_Payment_Slip_Block.php',
	'lib/Gateway/Gateway_Payment_Slip_IPS_Handler.php',
	'lib/QR/QR_Code_Options.php',
	'lib/QR/QR_Generator_GD.php',
	'lib/QR/QR_Generator_ImageMagick.php',
	'lib/Services/Payments.php',
	'lib/Services/QR_Code_Manager.php',
	'lib/Utils/Template_Extender.php',
	'woocommerce/checkout/payment-slip-qr-code.php',
	'woocommerce/checkout/payment-slip.php',
);

if ( ! is_readable( $clover ) ) {
	fwrite( STDERR, "Coverage report not found: {$clover}\n" );
	exit( 2 );
}

$files = array();

foreach ( simplexml_load_file( $clover )->xpath( '//file' ) as $file ) {
	$files[ ltrim( str_replace( $root, '', (string) $file['name'] ), '/' ) ] = $file;
}

$failed = 0;

foreach ( $scope as $path ) {
	if ( ! isset( $files[ $path ] ) ) {
		printf( "%-50s missing from the report\n", $path );
		++$failed;
		continue;
	}

	$total   = 0;
	$missing = array();

	foreach ( $files[ $path ]->line as $line ) {
		if ( 'stmt' !== (string) $line['type'] ) {
			continue;
		}

		++$total;

		if ( 0 === (int) $line['count'] ) {
			$missing[] = (int) $line['num'];
		}
	}

	printf(
		"%-50s %6.2f%%%s\n",
		$path,
		$total ? 100 * ( $total - count( $missing ) ) / $total : 100,
		$missing ? '  uncovered lines: ' . implode( ',', $missing ) : '',
	);

	$failed += $missing ? 1 : 0;
}

exit( $failed ? 1 : 0 );
