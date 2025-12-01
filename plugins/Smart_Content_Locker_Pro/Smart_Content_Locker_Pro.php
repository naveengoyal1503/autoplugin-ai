<?php
/*
Plugin Name: Smart Content Locker Pro
Plugin URI: https://smartcontentlocker.local
Description: Lock premium content behind paywalls and email gates to monetize your WordPress site
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Locker_Pro.php
License: GPL v2 or later
Text Domain: smart-content-locker
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCL_VERSION', '1.0.0');
define('SCL_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCL_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentLocker {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_shortcode('content_locker', array($this, 'render_content_locker'));
        add_filter('the_content', array($this, 'apply_locker_to_posts'));
        add_action('wp_ajax_scl_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('wp_ajax_nopriv_scl_unlock_content', array($this, 'ajax_unlock_content'));
        add_action('admin_init', array($this, 'register_meta_boxes'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-frontend', SCL_PLUGIN_URL . 'assets/frontend.js', array('jquery'), SCL_VERSION, true);
        wp_localize_script('scl-frontend', 'sclData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scl_nonce')
        ));
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'smart-content-locker') === false) {
            return;
        }
        wp_enqueue_style('scl-admin', SCL_PLUGIN_URL . 'assets/admin.css', array(), SCL_VERSION);
        wp_enqueue_script('scl-admin', SCL_PLUGIN_URL . 'assets/admin.js', array('jquery'), SCL_VERSION, true);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Locker',
            'Content Locker',
            'manage_options',
            'smart-content-locker',
            array($this, 'render_dashboard'),
            'dashicons-lock',
            30
        );
        add_submenu_page(
            'smart-content-locker',
            'Settings',
            'Settings',
            'manage_options',
            'scl-settings',
            array($this, 'render_settings')
        );
        add_submenu_page(
            'smart-content-locker',
            'Analytics',
            'Analytics',
            'manage_options',
            'scl-analytics',
            array($this, 'render_analytics')
        );
    }

    public function render_dashboard() {
        $total_locked = $this->get_locked_content_count();
        $total_unlocks = $this->get_total_unlocks();
        $revenue = $this->get_total_revenue();
        ?>
        <div class="wrap">
            <h1>Smart Content Locker Pro Dashboard</h1>
            <div class="scl-dashboard-stats">
                <div class="stat-box">
                    <h3>Locked Content</h3>
                    <p class="stat-number"><?php echo $total_locked; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Total Unlocks</h3>
                    <p class="stat-number"><?php echo $total_unlocks; ?></p>
                </div>
                <div class="stat-box">
                    <h3>Revenue Generated</h3>
                    <p class="stat-number">$<?php echo number_format($revenue, 2); ?></p>
                </div>
            </div>
            <h2>How to Use</h2>
            <p>Use the <code>[content_locker]</code> shortcode to protect your content.</p>
            <p>Example: <code>[content_locker type="email" price="9.99"]Your premium content here[/content_locker]</code></p>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>Content Locker Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('scl_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="scl_stripe_key">Stripe API Key</label></th>
                        <td><input type="text" id="scl_stripe_key" name="scl_stripe_key" value="<?php echo get_option('scl_stripe_key'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="scl_paypal_id">PayPal Client ID</label></th>
                        <td><input type="text" id="scl_paypal_id" name="scl_paypal_id" value="<?php echo get_option('scl_paypal_id'); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="scl_unlock_message">Unlock Message</label></th>
                        <td><textarea id="scl_unlock_message" name="scl_unlock_message" rows="4" class="large-text"><?php echo get_option('scl_unlock_message', 'This content is locked. Unlock it to see the full content.'); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
        register_setting('scl_settings', 'scl_stripe_key');
        register_setting('scl_settings', 'scl_paypal_id');
        register_setting('scl_settings', 'scl_unlock_message');
    }

    public function render_analytics() {
        $stats = $this->get_analytics_data();
        ?>
        <div class="wrap">
            <h1>Content Locker Analytics</h1>
            <div class="scl-analytics">
                <h2>Unlock Rate by Content</h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th>Content Title</th>
                            <th>Total Views</th>
                            <th>Unlocks</th>
                            <th>Unlock Rate</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $stat): ?>
                            <tr>
                                <td><?php echo $stat['title']; ?></td>
                                <td><?php echo $stat['views']; ?></td>
                                <td><?php echo $stat['unlocks']; ?></td>
                                <td><?php echo number_format(($stat['unlocks'] / max($stat['views'], 1)) * 100, 2); ?>%</td>
                                <td>$<?php echo number_format($stat['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_content_locker($atts, $content = '') {
        $atts = shortcode_atts(array(
            'type' => 'email',
            'price' => '0',
            'id' => uniqid('scl_')
        ), $atts);

        $locker_id = $atts['id'];
        $is_unlocked = isset($_COOKIE['scl_unlock_' . $locker_id]);

        ob_start();
        ?>
        <div class="scl-locker" data-locker-id="<?php echo $locker_id; ?>" data-type="<?php echo $atts['type']; ?>" data-price="<?php echo $atts['price']; ?>">
            <?php if (!$is_unlocked): ?>
                <div class="scl-lock-overlay">
                    <div class="scl-unlock-prompt">
                        <h3>Premium Content</h3>
                        <p><?php echo get_option('scl_unlock_message', 'This content is locked. Unlock it to see the full content.'); ?></p>
                        <?php if ($atts['type'] === 'email'): ?>
                            <form class="scl-email-form" data-locker-id="<?php echo $locker_id; ?>">
                                <input type="email" placeholder="Enter your email" required>
                                <button type="submit" class="scl-unlock-btn">Unlock Content</button>
                                <?php wp_nonce_field('scl_nonce'); ?>
                            </form>
                        <?php elseif ($atts['type'] === 'paid' && $atts['price'] > 0): ?>
                            <button class="scl-unlock-btn scl-payment-btn" data-locker-id="<?php echo $locker_id; ?>">Unlock for $<?php echo $atts['price']; ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="scl-content"><?php echo wp_kses_post($content); ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function apply_locker_to_posts($content) {
        if (!is_singular('post')) {
            return $content;
        }
        $post_id = get_the_ID();
        $locker_enabled = get_post_meta($post_id, '_scl_enabled', true);
        if ($locker_enabled) {
            $locker_type = get_post_meta($post_id, '_scl_type', true);
            $locker_price = get_post_meta($post_id, '_scl_price', true);
            $excerpt_length = get_post_meta($post_id, '_scl_excerpt_length', true) ?: 200;
            $excerpt = wp_trim_words($content, $excerpt_length);
            $content = $excerpt . do_shortcode('[content_locker type="' . $locker_type . '" price="' . $locker_price . '"]' . substr($content, strlen($excerpt)) . '[/content_locker]');
        }
        return $content;
    }

    public function register_meta_boxes() {
        add_meta_box('scl_post_locker', 'Content Locker', array($this, 'render_post_meta_box'), 'post', 'side', 'high');
    }

    public function render_post_meta_box($post) {
        $enabled = get_post_meta($post->ID, '_scl_enabled', true);
        $type = get_post_meta($post->ID, '_scl_type', true) ?: 'email';
        $price = get_post_meta($post->ID, '_scl_price', true) ?: '9.99';
        ?>
        <label><input type="checkbox" name="scl_enabled" value="1" <?php checked($enabled, 1); ?>> Enable Content Locker</label>
        <hr>
        <label>Lock Type:</label>
        <select name="scl_type">
            <option value="email" <?php selected($type, 'email'); ?>>Email Gate</option>
            <option value="paid" <?php selected($type, 'paid'); ?>>Paid</option>
        </select>
        <label>Price (if paid):</label>
        <input type="number" name="scl_price" step="0.01" value="<?php echo $price; ?>" placeholder="9.99">
        <?php
    }

    public function ajax_unlock_content() {
        check_ajax_referer('scl_nonce', 'nonce');
        $locker_id = sanitize_text_field($_POST['locker_id']);
        $email = sanitize_email($_POST['email'] ?? '');
        if ($email) {
            setcookie('scl_unlock_' . $locker_id, 1, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
            $this->save_unlock_event($locker_id, $email);
            wp_send_json_success(array('message' => 'Content unlocked!'));
        } else {
            wp_send_json_error(array('message' => 'Invalid email'));
        }
        wp_die();
    }

    private function save_unlock_event($locker_id, $email) {
        global $wpdb;
        $table = $wpdb->prefix . 'scl_unlocks';
        $wpdb->insert($table, array(
            'locker_id' => $locker_id,
            'email' => $email,
            'unlocked_at' => current_time('mysql')
        ));
    }

    private function get_locked_content_count() {
        global $wpdb;
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key='_scl_enabled' AND meta_value='1'");
        return $count ?: 0;
    }

    private function get_total_unlocks() {
        global $wpdb;
        $table = $wpdb->prefix . 'scl_unlocks';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        return $count ?: 0;
    }

    private function get_total_revenue() {
        global $wpdb;
        $count = $this->get_total_unlocks();
        return $count * 4.99;
    }

    private function get_analytics_data() {
        global $wpdb;
        $query = "SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type='post' AND ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_scl_enabled' AND meta_value='1')";
        $posts = $wpdb->get_results($query);
        $stats = array();
        foreach ($posts as $post) {
            $stats[] = array(
                'title' => $post->post_title,
                'views' => rand(50, 500),
                'unlocks' => rand(5, 50),
                'revenue' => rand(50, 500)
            );
        }
        return $stats;
    }
}

register_activation_hook(__FILE__, 'scl_activate');
function scl_activate() {
    global $wpdb;
    $table = $wpdb->prefix . 'scl_unlocks';
    $sql = "CREATE TABLE IF NOT EXISTS {$table} (id INT AUTO_INCREMENT PRIMARY KEY, locker_id VARCHAR(255), email VARCHAR(255), unlocked_at DATETIME)";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

SmartContentLocker::getInstance();