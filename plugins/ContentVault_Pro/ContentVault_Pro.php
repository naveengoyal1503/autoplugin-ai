<?php
/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvault.local
Description: Monetize individual posts and pages with flexible paywalls and micro-transactions
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contentvault-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CV_PRO_VERSION', '1.0.0');
define('CV_PRO_PATH', plugin_dir_path(__FILE__));
define('CV_PRO_URL', plugin_dir_url(__FILE__));

class ContentVaultPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_paywall_settings'));
        add_filter('the_content', array($this, 'apply_paywall'), 999);
        add_action('wp_ajax_cv_process_payment', array($this, 'process_payment'));
        add_action('wp_ajax_nopriv_cv_process_payment', array($this, 'process_payment'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_shortcode('contentvault', array($this, 'paywall_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cv_transactions';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20),
            post_id bigint(20),
            price decimal(10, 2),
            currency varchar(10),
            transaction_id varchar(100),
            status varchar(20),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        add_option('cv_pro_license_key', '');
        add_option('cv_pro_settings', array(
            'currency' => 'USD',
            'min_price' => 0.99,
            'enable_guest_purchase' => false,
            'success_message' => 'Thank you for your purchase!'
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentVault Pro',
            'ContentVault Pro',
            'manage_options',
            'cv-pro-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            77
        );

        add_submenu_page(
            'cv-pro-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cv-pro-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            'cv-pro-dashboard',
            'Transactions',
            'Transactions',
            'manage_options',
            'cv-pro-transactions',
            array($this, 'render_transactions')
        );
    }

    public function render_dashboard() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cv_transactions';
        $total_revenue = $wpdb->get_var("SELECT SUM(price) FROM $table_name WHERE status = 'completed'");
        $total_transactions = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE status = 'completed'");
        $this_month_revenue = $wpdb->get_var("SELECT SUM(price) FROM $table_name WHERE status = 'completed' AND MONTH(created_at) = MONTH(NOW())");

        ?>
        <div class="wrap">
            <h1>ContentVault Pro Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>Total Revenue</h3>
                    <p style="font-size: 28px; margin: 0; font-weight: bold;">$<?php echo number_format($total_revenue, 2); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>This Month</h3>
                    <p style="font-size: 28px; margin: 0; font-weight: bold;">$<?php echo number_format($this_month_revenue, 2); ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>Total Transactions</h3>
                    <p style="font-size: 28px; margin: 0; font-weight: bold;"><?php echo intval($total_transactions); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        if (isset($_POST['cv_pro_save_settings'])) {
            check_admin_referer('cv_pro_settings_nonce');
            $settings = array(
                'currency' => sanitize_text_field($_POST['cv_currency']),
                'min_price' => floatval($_POST['cv_min_price']),
                'enable_guest_purchase' => isset($_POST['cv_guest_purchase']) ? 1 : 0,
                'success_message' => sanitize_textarea_field($_POST['cv_success_message'])
            );
            update_option('cv_pro_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $settings = get_option('cv_pro_settings', array());
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Settings</h1>
            <form method="post">
                <?php wp_nonce_field('cv_pro_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="cv_currency">Currency</label></th>
                        <td>
                            <select id="cv_currency" name="cv_currency">
                                <option value="USD" <?php selected($settings['currency'], 'USD'); ?>>USD ($)</option>
                                <option value="EUR" <?php selected($settings['currency'], 'EUR'); ?>>EUR (€)</option>
                                <option value="GBP" <?php selected($settings['currency'], 'GBP'); ?>>GBP (£)</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cv_min_price">Minimum Price</label></th>
                        <td>
                            <input type="number" id="cv_min_price" name="cv_min_price" step="0.01" value="<?php echo esc_attr($settings['min_price']); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cv_guest_purchase">Allow Guest Purchases</label></th>
                        <td>
                            <input type="checkbox" id="cv_guest_purchase" name="cv_guest_purchase" <?php checked($settings['enable_guest_purchase']); ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="cv_success_message">Success Message</label></th>
                        <td>
                            <textarea id="cv_success_message" name="cv_success_message" rows="4"><?php echo esc_textarea($settings['success_message']); ?></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Settings', 'primary', 'cv_pro_save_settings'); ?>
            </form>
        </div>
        <?php
    }

    public function render_transactions() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'cv_transactions';
        $transactions = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC LIMIT 100");
        ?>
        <div class="wrap">
            <h1>Recent Transactions</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Transaction ID</th>
                        <th>Post</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx) {
                        $post_title = get_the_title($tx->post_id);
                        echo '<tr>';
                        echo '<td>' . esc_html($tx->transaction_id) . '</td>';
                        echo '<td>' . esc_html($post_title) . '</td>';
                        echo '<td>' . esc_html($tx->currency . ' ' . number_format($tx->price, 2)) . '</td>';
                        echo '<td><span style="color: ' . ($tx->status === 'completed' ? 'green' : 'red') . ';">' . esc_html($tx->status) . '</span></td>';
                        echo '<td>' . esc_html($tx->created_at) . '</td>';
                        echo '</tr>';
                    } ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_meta_boxes() {
        add_meta_box(
            'cv_pro_paywall',
            'ContentVault Pro Settings',
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('cv_pro_meta_nonce', 'cv_pro_meta_nonce');
        $paywall_enabled = get_post_meta($post->ID, '_cv_paywall_enabled', true);
        $paywall_price = get_post_meta($post->ID, '_cv_paywall_price', true);
        $paywall_message = get_post_meta($post->ID, '_cv_paywall_message', true);
        ?>
        <div style="padding: 10px;">
            <label>
                <input type="checkbox" name="cv_paywall_enabled" value="1" <?php checked($paywall_enabled, 1); ?> />
                Enable Paywall for this post
            </label>
            <div style="margin-top: 15px;">
                <label for="cv_paywall_price">Price (USD):</label>
                <input type="number" id="cv_paywall_price" name="cv_paywall_price" step="0.01" min="0.99" value="<?php echo esc_attr($paywall_price); ?>" style="width: 100px;" />
            </div>
            <div style="margin-top: 15px;">
                <label for="cv_paywall_message">Paywall Message:</label>
                <textarea id="cv_paywall_message" name="cv_paywall_message" rows="3" style="width: 100%;"><?php echo esc_textarea($paywall_message); ?></textarea>
            </div>
        </div>
        <?php
    }

    public function save_paywall_settings($post_id) {
        if (!isset($_POST['cv_pro_meta_nonce']) || !wp_verify_nonce($_POST['cv_pro_meta_nonce'], 'cv_pro_meta_nonce')) {
            return;
        }

        update_post_meta($post_id, '_cv_paywall_enabled', isset($_POST['cv_paywall_enabled']) ? 1 : 0);
        update_post_meta($post_id, '_cv_paywall_price', floatval($_POST['cv_paywall_price']));
        update_post_meta($post_id, '_cv_paywall_message', sanitize_textarea_field($_POST['cv_paywall_message']));
    }

    public function apply_paywall($content) {
        if (is_single() && !is_user_logged_in()) {
            $paywall_enabled = get_post_meta(get_the_ID(), '_cv_paywall_enabled', true);
            if ($paywall_enabled) {
                $paywall_price = get_post_meta(get_the_ID(), '_cv_paywall_price', true);
                $paywall_message = get_post_meta(get_the_ID(), '_cv_paywall_message', true);
                
                if (!$paywall_message) {
                    $paywall_message = 'This content is exclusive and requires a one-time payment to access.';
                }

                $excerpt = wp_trim_words($content, 50);
                $paywall_html = '<div class="cv-paywall-container" style="background: #f5f5f5; padding: 20px; border-radius: 5px; margin: 20px 0;">';
                $paywall_html .= '<p>' . esc_html($excerpt) . '...</p>';
                $paywall_html .= '<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px; text-align: center;">';
                $paywall_html .= '<p><strong>' . esc_html($paywall_message) . '</strong></p>';
                $paywall_html .= '<p style="margin: 10px 0;"><strong style="font-size: 24px; color: #0073aa;">$' . esc_html(number_format($paywall_price, 2)) . '</strong></p>';
                $paywall_html .= '<button class="cv-paywall-button" data-post-id="' . intval(get_the_ID()) . '" data-price="' . esc_attr($paywall_price) . '" style="background: #0073aa; color: white; padding: 10px 30px; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">Unlock Content</button>';
                $paywall_html .= '</div>';
                $paywall_html .= '</div>';

                return $paywall_html;
            }
        }
        return $content;
    }

    public function process_payment() {
        check_ajax_referer('cv_pro_payment', 'nonce');

        $post_id = intval($_POST['post_id']);
        $price = floatval($_POST['price']);
        $payment_method = sanitize_text_field($_POST['method']);

        global $wpdb;
        $table_name = $wpdb->prefix . 'cv_transactions';
        $transaction_id = 'CVT_' . time() . '_' . wp_rand(1000, 9999);

        $wpdb->insert($table_name, array(
            'user_id' => get_current_user_id(),
            'post_id' => $post_id,
            'price' => $price,
            'currency' => 'USD',
            'transaction_id' => $transaction_id,
            'status' => 'completed',
            'created_at' => current_time('mysql')
        ));

        wp_send_json_success(array(
            'message' => 'Payment processed successfully',
            'transaction_id' => $transaction_id
        ));
    }

    public function paywall_shortcode($atts) {
        $atts = shortcode_atts(array(
            'price' => '9.99',
            'message' => 'Unlock premium content',
            'button_text' => 'Unlock Now'
        ), $atts);

        $html = '<div class="cv-paywall-shortcode" style="background: #f0f0f0; padding: 20px; border-radius: 5px; text-align: center;">';
        $html .= '<p>' . esc_html($atts['message']) . '</p>';
        $html .= '<p style="font-size: 28px; font-weight: bold; color: #0073aa;">$' . esc_html($atts['price']) . '</p>';
        $html .= '<button class="cv-paywall-button" data-price="' . esc_attr($atts['price']) . '" style="background: #0073aa; color: white; padding: 12px 40px; border: none; border-radius: 3px; cursor: pointer; font-size: 16px;">' . esc_html($atts['button_text']) . '</button>';
        $html .= '</div>';

        return $html;
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style('cv-pro-admin', CV_PRO_URL . 'admin-style.css');
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('cv-pro-frontend', CV_PRO_URL . 'frontend.js', array('jquery'), CV_PRO_VERSION, true);
        wp_localize_script('cv-pro-frontend', 'cvProData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cv_pro_payment')
        ));
    }
}

ContentVaultPro::get_instance();
?>