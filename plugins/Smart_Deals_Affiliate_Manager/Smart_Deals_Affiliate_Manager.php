<?php
/*
Plugin Name: Smart Deals Affiliate Manager
Description: Aggregates affiliate coupons, displays personalized deals, and sends notification emails to boost engagement and revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Deals_Affiliate_Manager.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class SmartDealsAffiliateManager {

    private $option_name = 'sdam_deals_data';
    private $cron_hook = 'sdam_fetch_deals_cron_hook';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('sdam_deals', array($this, 'render_deals_shortcode'));
        add_action('init', array($this, 'init_actions'));
        add_action($this->cron_hook, array($this, 'fetch_and_store_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_sdam_subscribe', array($this, 'ajax_subscribe')); // ajax for subscription
        add_action('wp_ajax_nopriv_sdam_subscribe', array($this, 'ajax_subscribe')); // non-logged-in users
    }

    public function init_actions() {
        if (!wp_next_scheduled($this->cron_hook)) {
            wp_schedule_event(time(), 'hourly', $this->cron_hook);
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_style('sdam-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('jquery');
        wp_enqueue_script('sdam-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), null, true);
        wp_localize_script('sdam-script', 'sdam_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    // Admin menu page
    public function admin_menu() {
        add_menu_page('Smart Deals Manager', 'Smart Deals', 'manage_options', 'sdam_admin', array($this, 'admin_page'), 'dashicons-tickets-alt');
    }

    public function register_settings() {
        register_setting('sdam_settings_group', 'sdam_affiliate_id');
        register_setting('sdam_settings_group', 'sdam_api_key');
        register_setting('sdam_settings_group', 'sdam_subscribers'); // stores array of emails
    }

    public function admin_page() {
        $affiliate_id = get_option('sdam_affiliate_id', '');
        $api_key = get_option('sdam_api_key', '');
        ?>
        <div class="wrap">
            <h1>Smart Deals Affiliate Manager</h1>
            <form method="post" action="options.php">
                <?php settings_fields('sdam_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Affiliate Network ID</th>
                        <td><input type="text" name="sdam_affiliate_id" value="<?php echo esc_attr($affiliate_id); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">API Key (for deals feed)</th>
                        <td><input type="text" name="sdam_api_key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>

            <h2>Current Deals</h2>
            <?php $deals = get_option($this->option_name, array()); ?>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Coupon Code</th>
                        <th>Expiry</th>
                        <th>Affiliate Link</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($deals && is_array($deals)) : ?>
                    <?php foreach ($deals as $deal) : ?>
                        <tr>
                            <td><?php echo esc_html($deal['title']); ?></td>
                            <td><?php echo esc_html($deal['code']); ?></td>
                            <td><?php echo esc_html($deal['expiry']); ?></td>
                            <td><a href="<?php echo esc_url($deal['affiliate_url']); ?>" target="_blank">Link</a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="4">No deals found or fetched yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    // Fetch deals from a (mocked) affiliate coupon API and store in options
    public function fetch_and_store_deals() {
        $affiliate_id = get_option('sdam_affiliate_id');
        $api_key = get_option('sdam_api_key');

        if (empty($affiliate_id) || empty($api_key)) {
            return; // No credentials configured
        }

        // Example: call a fake API - in production replace with real API URL and params
        $response = wp_remote_get('https://fakeaffiliateapi.example.com/v1/coupons?affiliate_id=' . urlencode($affiliate_id) . '&key=' . urlencode($api_key));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            return; // API call failed
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['deals'])) {
            return; // no deals
        }

        $deals = array();
        foreach ($data['deals'] as $deal) {
            $deals[] = array(
                'title' => sanitize_text_field($deal['title']),
                'code' => sanitize_text_field($deal['coupon_code']),
                'expiry' => sanitize_text_field($deal['expiry_date']),
                'affiliate_url' => esc_url_raw($deal['affiliate_link'])
            );
        }

        update_option($this->option_name, $deals);
        $this->send_new_deals_notifications($deals);
    }

    // Send email notifications about new deals to subscribers
    private function send_new_deals_notifications($deals) {
        $subscribers = get_option('sdam_subscribers', array());

        if (empty($subscribers) || !is_array($subscribers)) return;

        $subject = 'New Affiliate Deals Available Now';
        $message = "Hello!\n\nCheck out our latest deals and coupons we just aggregated for you:\n\n";

        foreach ($deals as $deal) {
            $message .= $deal['title'] . " - Code: " . $deal['code'] . "\n";
            $message .= "Link: " . $deal['affiliate_url'] . "\nExpires: " . $deal['expiry'] . "\n\n";
        }

        $headers = array('Content-Type: text/plain; charset=UTF-8');

        foreach ($subscribers as $email) {
            wp_mail($email, $subject, $message, $headers);
        }
    }

    // Shortcode output for listing deals + subscription form
    public function render_deals_shortcode() {
        $deals = get_option($this->option_name, array());

        ob_start();
        ?>
        <div class="sdam-deals-wrapper">
            <h3>Exclusive Affiliate Deals & Coupons</h3>
            <?php if ($deals && is_array($deals)): ?>
            <ul class="sdam-deals-list">
                <?php foreach ($deals as $deal): ?>
                    <li>
                        <strong><?php echo esc_html($deal['title']); ?></strong><br/>
                        Code: <code><?php echo esc_html($deal['code']); ?></code><br/>
                        Expires: <?php echo esc_html($deal['expiry']); ?><br/>
                        <a href="<?php echo esc_url($deal['affiliate_url']); ?>" target="_blank" rel="nofollow noopener">Shop Now</a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php else: ?>
                <p>No deals available currently. Please check back later!</p>
            <?php endif; ?>

            <h4>Subscribe to get deal alerts via email:</h4>
            <form id="sdam-subscribe-form">
                <input type="email" name="email" placeholder="Your email address" required />
                <button type="submit">Subscribe</button>
                <div id="sdam-subscribe-msg"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    // AJAX handler to save subscriber emails
    public function ajax_subscribe() {
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        if (!is_email($email)) {
            wp_send_json_error('Invalid email address');
        }

        $subscribers = get_option('sdam_subscribers', array());

        if (!is_array($subscribers)) $subscribers = array();

        if (in_array($email, $subscribers)) {
            wp_send_json_error('Email already subscribed');
        }

        $subscribers[] = $email;
        update_option('sdam_subscribers', $subscribers);

        wp_send_json_success('Subscription successful!');
    }

}
new SmartDealsAffiliateManager();

// Uninstall hook to clean options
register_uninstall_hook(__FILE__, function () {
    delete_option('sdam_deals_data');
    delete_option('sdam_affiliate_id');
    delete_option('sdam_api_key');
    delete_option('sdam_subscribers');
});
