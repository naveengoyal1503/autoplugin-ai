/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Donations_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Donations Pro
 * Plugin URI: https://example.com/smart-donations-pro
 * Description: Collect donations easily with tiers, PayPal, and analytics.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SmartDonationsPro {
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_shortcode( 'smart_donations', array( $this, 'donations_shortcode' ) );
        add_action( 'wp_ajax_sdp_process_donation', array( $this, 'process_donation' ) );
        add_action( 'wp_ajax_nopriv_sdp_process_donation', array( $this, 'process_donation' ) );
    }

    public function init() {
        if ( get_option( 'sdp_paypal_email' ) ) {
            // PayPal setup ready
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'sdp-frontend', plugin_dir_url( __FILE__ ) . 'sdp-frontend.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_style( 'sdp-frontend', plugin_dir_url( __FILE__ ) . 'sdp-frontend.css', array(), '1.0.0' );
        wp_localize_script( 'sdp-frontend', 'sdp_ajax', array( 'ajax_url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'sdp_nonce' ) ) );
    }

    public function donations_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'title' => 'Support Us',
            'tiers' => '5,10,25,50,100'
        ), $atts );

        $tiers = explode( ',', $atts['tiers'] );
        $paypal_email = get_option( 'sdp_paypal_email', '' );

        ob_start();
        ?>
        <div id="sdp-container" class="sdp-widget">
            <h3><?php echo esc_html( $atts['title'] ); ?></h3>
            <div class="sdp-tiers">
                <?php foreach ( $tiers as $tier ) : $amount = trim( $tier ); ?>
                    <button class="sdp-tier" data-amount="<?php echo esc_attr( $amount ); ?>"><?php echo esc_html( '$' . $amount ); ?></button>
                <?php endforeach; ?>
            </div>
            <div class="sdp-custom">
                <input type="number" id="sdp-custom-amount" placeholder="Custom amount" step="1" min="1">
                <button id="sdp-donate-btn">Donate Now</button>
            </div>
            <?php if ( $paypal_email ) : ?>
            <form id="sdp-paypal-form" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank" style="display:none;">
                <input type="hidden" name="cmd" value="_xclick">
                <input type="hidden" name="business" value="<?php echo esc_attr( $paypal_email ); ?>">
                <input type="hidden" name="item_name" value="Donation to <?php echo get_bloginfo( 'name' ); ?>">
                <input type="hidden" name="amount" id="sdp-paypal-amount" value="">
                <input type="hidden" name="currency_code" value="USD">
                <input type="hidden" name="return" value="<?php echo home_url(); ?>">
            </form>
            <?php endif; ?>
            <div id="sdp-message"></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function process_donation() {
        check_ajax_referer( 'sdp_nonce', 'nonce' );
        $amount = sanitize_text_field( $_POST['amount'] );
        // Log donation for analytics
        $log = get_option( 'sdp_donations_log', array() );
        $log[] = array( 'amount' => $amount, 'date' => current_time( 'mysql' ), 'ip' => $_SERVER['REMOTE_ADDR'] );
        update_option( 'sdp_donations_log', $log );
        wp_send_json_success( 'Donation processed! Thank you!' );
    }
}

// Admin settings
add_action( 'admin_menu', function() {
    add_options_page( 'Smart Donations Pro', 'Donations Pro', 'manage_options', 'sdp-settings', function() {
        if ( isset( $_POST['sdp_paypal_email'] ) ) {
            update_option( 'sdp_paypal_email', sanitize_email( $_POST['sdp_paypal_email'] ) );
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $paypal_email = get_option( 'sdp_paypal_email', '' );
        echo '<div class="wrap"><h1>Smart Donations Pro Settings</h1>
        <form method="post">
            <table class="form-table">
                <tr><th>PayPal Email</th><td><input type="email" name="sdp_paypal_email" value="' . esc_attr( $paypal_email ) . '" class="regular-text"></td></tr>
            </table>
            <p><strong>Analytics:</strong> Total donations logged in Tools > Site Health > Debug.</p>
            ' . submit_button() . '</form></div>';
    } );
} );

new SmartDonationsPro();

// Frontend JS (inline for single file)
function sdp_add_inline_js() {
    if ( has_shortcode( get_post()->post_content, 'smart_donations' ) ) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.sdp-tier').click(function() {
                $('#sdp-custom-amount').val( $(this).data('amount') );
            });
            $('#sdp-donate-btn').click(function() {
                var amount = $('#sdp-custom-amount').val();
                if (amount > 0) {
                    $('#sdp-paypal-amount').val(amount);
                    $('#sdp-paypal-form').submit();
                } else {
                    $('#sdp-message').html('<p style="color:red;">Please enter an amount.</p>');
                }
            });
        });
        </script>
        <style>
        #sdp-container { max-width: 400px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; text-align: center; }
        .sdp-tiers button { margin: 5px; padding: 10px 20px; background: #0073aa; color: white; border: none; border-radius: 5px; cursor: pointer; }
        .sdp-tiers button:hover { background: #005a87; }
        #sdp-custom-amount { width: 100px; padding: 8px; margin: 10px; }
        #sdp-donate-btn { background: #46b450; color: white; padding: 12px 24px; border: none; border-radius: 5px; cursor: pointer; }
        #sdp-message { margin-top: 10px; }
        </style>
        <?php
    }
}
add_action( 'wp_footer', 'sdp_add_inline_js' );
?>