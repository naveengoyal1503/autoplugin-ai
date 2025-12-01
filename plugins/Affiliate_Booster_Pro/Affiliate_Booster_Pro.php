/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Booster_Pro.php
*/
<?php
/**
 * Plugin Name: Affiliate Booster Pro
 * Description: Manage and optimize your affiliate marketing program with WooCommerce and EDD integration.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class AffiliateBoosterPro {
    public function __construct() {
        add_action('admin_menu', [$this, 'abp_add_admin_menu']);
        add_action('admin_init', [$this, 'abp_settings_init']);
        add_shortcode('affiliate_referral_link', [$this, 'abp_referral_link_shortcode']);
        add_action('woocommerce_thankyou', [$this, 'abp_handle_order_complete']);
        add_action('edd_complete_purchase', [$this, 'abp_handle_edd_purchase']);
    }

    public function abp_add_admin_menu() {
        add_menu_page('Affiliate Booster Pro', 'Affiliate Booster', 'manage_options', 'affiliate_booster_pro', [$this, 'abp_options_page']);
    }

    public function abp_settings_init() {
        register_setting('abp_settings', 'abp_settings');

        add_settings_section(
            'abp_plugin_section',
            __('Affiliate Booster Settings', 'abp'),
            null,
            'abp_settings'
        );

        add_settings_field(
            'abp_commission_rate',
            __('Default Commission Rate (%)', 'abp'),
            [$this, 'abp_commission_rate_render'],
            'abp_settings',
            'abp_plugin_section'
        );
    }

    public function abp_commission_rate_render() {
        $options = get_option('abp_settings');
        ?>
        <input type='number' min='0' max='100' step='0.1' name='abp_settings[abp_commission_rate]' value='<?php echo isset($options['abp_commission_rate']) ? esc_attr($options['abp_commission_rate']) : 10; ?>'>
        <p class="description">Set the default commission rate for affiliates as a percentage of sale.</p>
        <?php
    }

    public function abp_options_page() {
        ?>
        <form action='options.php' method='post'>
            <h1>Affiliate Booster Pro Settings</h1>
            <?php
            settings_fields('abp_settings');
            do_settings_sections('abp_settings');
            submit_button();
            ?>
        </form>
        <?php
    }

    public function abp_referral_link_shortcode($atts) {
        if (is_user_logged_in()) {
            $user_id = get_current_user_id();
            $ref_link = add_query_arg('ref', $user_id, home_url('/'));
            return '<input type="text" readonly value="' . esc_url($ref_link) . '" style="width:100%;padding:5px;">';
        }
        return 'Please log in to get your referral link.';
    }

    private function abp_add_affiliate_referral($user_id, $order_amount) {
        $options = get_option('abp_settings');
        $commission_rate = isset($options['abp_commission_rate']) ? floatval($options['abp_commission_rate']) : 10;
        $commission = ($commission_rate / 100) * $order_amount;

        if ($commission > 0) {
            $refs = get_user_meta($user_id, 'abp_referrals', true);
            if (!is_array($refs)) {
                $refs = [];
            }
            $refs[] = ['commission' => $commission, 'date' => current_time('mysql')];
            update_user_meta($user_id, 'abp_referrals', $refs);
        }
    }

    public function abp_handle_order_complete($order_id) {
        $order = wc_get_order($order_id);
        if (!$order) {
            return;
        }

        $referrer_id = isset($_GET['ref']) ? intval($_GET['ref']) : 0;
        if ($referrer_id && get_userdata($referrer_id)) {
            $total = floatval($order->get_total());
            $this->abp_add_affiliate_referral($referrer_id, $total);
        }
    }

    public function abp_handle_edd_purchase($payment_id) {
        $payment = new EDD_Payment($payment_id);
        if (!$payment) {
            return;
        }

        $referrer_id = isset($_GET['ref']) ? intval($_GET['ref']) : 0;
        if ($referrer_id && get_userdata($referrer_id)) {
            $total = floatval($payment->total);
            $this->abp_add_affiliate_referral($referrer_id, $total);
        }
    }
}

new AffiliateBoosterPro();
