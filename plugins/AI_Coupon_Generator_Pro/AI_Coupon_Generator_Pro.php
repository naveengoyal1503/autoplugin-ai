/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Coupon_Generator_Pro.php
*/
<?php
/**
 * Plugin Name: AI Coupon Generator Pro
 * Plugin URI: https://example.com/ai-coupon-generator
 * Description: AI-powered coupon and deal generator for WordPress monetization.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class AICouponGenerator {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('ai_coupon_widget', array($this, 'coupon_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        // Create coupons table on activation
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            code varchar(100) DEFAULT '' NOT NULL,
            discount varchar(50) DEFAULT '' NOT NULL,
            affiliate_url varchar(500) DEFAULT '' NOT NULL,
            expiry date DEFAULT '0000-00-00' NOT NULL,
            uses int DEFAULT 0,
            max_uses int DEFAULT 0,
            active tinyint DEFAULT 1,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function activate() {
        $this->init();
        // Insert sample coupon
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $wpdb->insert($table_name, array(
            'title' => 'Welcome 20% Off',
            'code' => $this->generate_coupon_code(),
            'discount' => '20%',
            'affiliate_url' => 'https://example.com/affiliate',
            'expiry' => date('Y-m-d', strtotime('+30 days')),
            'max_uses' => 100
        ));
    }

    public function generate_coupon_code($length = 10) {
        return substr(str_shuffle(strtoupper('abcdefghijklmnopqrstuvwxyz0123456789')), 0, $length);
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ai-coupon-style', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0');
        wp_enqueue_script('ai-coupon-script', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0', true);
    }

    public function admin_menu() {
        add_options_page('AI Coupon Generator', 'AI Coupons', 'manage_options', 'ai-coupons', array($this, 'admin_page'));
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        if (isset($_POST['generate_coupon'])) {
            $wpdb->insert($table_name, array(
                'title' => sanitize_text_field($_POST['title']),
                'code' => $this->generate_coupon_code(),
                'discount' => sanitize_text_field($_POST['discount']),
                'affiliate_url' => esc_url_raw($_POST['affiliate_url']),
                'expiry' => sanitize_text_field($_POST['expiry']),
                'max_uses' => intval($_POST['max_uses'])
            ));
            echo '<div class="notice notice-success"><p>Coupon generated!</p></div>';
        }
        $coupons = $wpdb->get_results("SELECT * FROM $table_name WHERE active = 1 ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>AI Coupon Generator</h1>
            <form method="post">
                <table class="form-table">
                    <tr><th>Title</th><td><input type="text" name="title" required /></td></tr>
                    <tr><th>Discount</th><td><input type="text" name="discount" placeholder="20%" required /></td></tr>
                    <tr><th>Affiliate URL</th><td><input type="url" name="affiliate_url" style="width: 400px;" /></td></tr>
                    <tr><th>Expiry</th><td><input type="date" name="expiry" /></td></tr>
                    <tr><th>Max Uses</th><td><input type="number" name="max_uses" value="0" /></td></tr>
                </table>
                <?php submit_button('Generate AI Coupon', 'primary', 'generate_coupon'); ?>
            </form>
            <h2>Active Coupons</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Code</th><th>Discount</th><th>URL</th><th>Uses</th><th>Shortcode</th></tr></thead>
                <tbody>
        <?php foreach ($coupons as $coupon): ?>
                    <tr>
                        <td><?php echo esc_html($coupon->title); ?></td>
                        <td><strong><?php echo esc_html($coupon->code); ?></strong></td>
                        <td><?php echo esc_html($coupon->discount); ?></td>
                        <td><a href="<?php echo esc_url($coupon->affiliate_url); ?>" target="_blank">View</a></td>
                        <td><?php echo $coupon->uses; ?>/<?php echo $coupon->max_uses ?: 'Unlimited'; ?></td>
                        <td><code>[ai_coupon_widget id="<?php echo $coupon->id; ?>"]</code></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function coupon_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_coupons';
        $coupon = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d AND active = 1", $atts['id']));
        if (!$coupon || ($coupon->max_uses > 0 && $coupon->uses >= $coupon->max_uses)) {
            return '<p>Coupon expired or invalid.</p>';
        }
        ob_start();
        ?>
        <div class="ai-coupon-widget">
            <h3><?php echo esc_html($coupon->title); ?></h3>
            <div class="coupon-code"><strong><?php echo esc_html($coupon->code); ?></strong></div>
            <p><em><?php echo esc_html($coupon->discount); ?> Off - Expires: <?php echo date('M j, Y', strtotime($coupon->expiry)); ?></em></p>
            <a href="<?php echo esc_url($coupon->affiliate_url); ?}" class="coupon-button" target="_blank">Get Deal Now</a>
            <small>Used <?php echo $coupon->uses; ?> times</small>
        </div>
        <style>
        .ai-coupon-widget { border: 2px dashed #007cba; padding: 20px; border-radius: 10px; text-align: center; background: #f9f9f9; }
        .coupon-code { font-size: 2em; color: #007cba; margin: 10px 0; }
        .coupon-button { display: inline-block; background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .coupon-button:hover { background: #005a87; }
        </style>
        <script>
        jQuery('.coupon-button').on('click', function() {
            // Track click for analytics
            console.log('Coupon clicked');
            // Premium: Send to analytics endpoint
        });
        </script>
        <?php
        return ob_get_clean();
    }
}

new AICouponGenerator();

// Premium upsell notice
add_action('admin_notices', function() {
    if (!current_user_can('manage_options')) return;
    echo '<div class="notice notice-info"><p><strong>AI Coupon Generator Pro:</strong> Unlock AI auto-generation, analytics, and unlimited coupons for $49/year. <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
});