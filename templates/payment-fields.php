<?php
/**
 * The Template for displaying the credit card form on the checkout page
 *
 * Override this template by copying it to yourtheme/woocommerce/s4wc/payment-fields.php
 *
 * @author      Stephen Zuniga
 * @version     1.36
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $beanstream_for_wc;

//alias for beanstream_for_wc
$b4w = $beanstream_for_wc;

// Payment method description
if ( $b4w->settings['description'] ) {
    echo '<p class="beanstream-description">' .  $b4w->settings['description'] . '</p>';
}

// Saved customer information
$bci = get_user_meta( get_current_user_id(), $b4w->settings['beanstream_db_location'], true );

if ( is_user_logged_in() && $bci && isset( $bci['cards'] ) && count( $bci['cards'] ) && $b4w->settings['saved_cards'] === 'yes' ) :

    // Add option to use a saved card
    foreach ( $b4w['cards'] as $i => $credit_card ) :
        $checked = ( $b4w['default_card'] == $credit_card['id'] ) ? ' checked' : '';

        if ( $i === 0 && $b4w['default_card'] === '' ) {
            $checked = ' checked';
        }
    ?>

        <input type="radio" id="beanstream_card_<?php echo $i; ?>" name="beanstream_card" value="<?php echo $i; ?>"<?php echo $checked; ?>>
        <label for="beanstream_card_<?php echo $i; ?>"><?php printf( __( 'Card ending with %s (%s/%s)', 'beanstream-for-woocommerce' ), $credit_card['last4'], $credit_card['exp_month'], $credit_card['exp_year'] ); ?></label><br>

    <?php endforeach; ?>

    <input type="radio" id="beanstream_new_card" name="beanstream_card" value="new">
    <label for="beanstream_new_card"><?php _e( 'Use a new credit card', 'beanstream-for-woocommerce' ); ?></label>

<?php endif; ?>
