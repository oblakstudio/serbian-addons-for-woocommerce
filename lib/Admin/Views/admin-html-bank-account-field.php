<?php
/**
 * Bank account settings field HTML template.
 *
 * @package Serbian Addons for WooCommerce
 * @version 3.7.3
 *
 * @var array<string> $value      Field value.
 * @var string        $field_name Field name.
 * @var array<string> $custom_attributes Custom attributes.
 */

defined( 'ABSPATH' ) || exit;


$field_name = "{$value['field_name']}[]";
$field_desc = WC_Admin_Settings::get_field_description( $value );

?>
<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo \esc_attr( $value['id'] ); ?>">
            <?php echo esc_html( $value['title'] ); ?> <?php echo $field_desc['tooltip_html']; // phpcs:ignore ?>
        </label>
    </th>
    <td class="bank-accounts forminp forminp-<?php echo \esc_attr( \sanitize_title( $value['type'] ) ); ?>">
        <div id="<?php echo \esc_attr( $value['id'] ); ?>">
        <?php foreach ( $option_value ?? array() as $row_value ) : ?>
            <div class="repeater-row row">
                <input
                    name="<?php echo \esc_attr( $field_name ); ?>"
                    id="<?php echo \esc_attr( $value['id'] ); ?>"
                    type="text"
                    value="<?php echo \esc_attr( $row_value ); ?>"
                    class="<?php echo \esc_attr( $value['class'] ); ?>"
                    placeholder="<?php echo \esc_attr( $value['placeholder'] ); ?>"
                    <?php echo implode( ' ', $custom_attributes ); // phpcs:ignore ?>
                />
                <?php echo esc_html( $value['suffix'] ); ?> <?php echo $$field_desc['description']; //phpcs:ignore ?>
                <button type="button" class="button minus repeater-remove-row">
                    <?php \esc_html_e( 'Remove', 'woocommerce' ); ?>
                </button>
            </div>
        <?php endforeach; ?>
        </div>
        <button
            type="button"
            class="button plus repeater-add-row"
            data-tmpl="<?php echo \esc_attr( $value['id'] ); ?>"
            data-name="<?php echo \esc_attr( $field_name ); ?>"
            data-type="text"
            data-value=""
            data-class="<?php echo \esc_attr( $value['class'] ); ?>"
            data-placeholder="<?php echo \esc_attr( $value['placeholder'] ); ?>"
            data-custom_atts="<?php echo \esc_attr( \implode( ' ', $custom_attributes ) ); ?>"
            data-suffix="<?php echo \esc_attr( $value['suffix'] ); ?>"
        >
            <?php \esc_html_e( 'Add', 'woocommerce' ); ?>
        </button>
    </td>
</tr>
