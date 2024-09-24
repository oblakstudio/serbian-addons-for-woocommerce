<?php
/**
 * Bank account row JS template.
 *
 * @package Serbian Addons for WooCommerce
 * @version 3.7.3
 *
 * @var array<string, mixed> $value Field value.
 */

defined( 'ABSPATH' ) || exit;

?>
<script id="tmpl-<?php echo \esc_attr( $value['id'] ); ?>" type="text/html" class="repeater-tmpl">
    <div class="repeater-row row">
        <input
            name="{{ data.name }}"
            type="{{ data.type }}"
            value="{{ data.value }}"
            class="{{ data.class }}"
            placeholder="{{ data.placeholder }}"
            {{ data.custom_atts }}
        >{{ data.suffix }}
        <button type="button" class="button minus repeater-remove-row">
            <?php \esc_html_e( 'Remove', 'woocommerce' ); ?>
        </button>
    </div>
</script>
