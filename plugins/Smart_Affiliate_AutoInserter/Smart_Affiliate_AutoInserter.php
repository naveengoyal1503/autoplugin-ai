/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_AutoInserter.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate AutoInserter
 * Plugin URI: https://example.com/smart-affiliate-autoinserter
 * Description: Automatically inserts relevant affiliate links into posts and pages to maximize earnings.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-autoinserter
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateAutoInserter {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_filter('the_content', array($this, 'auto_insert_affiliate_links'), 99);
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        load_plugin_textdomain('smart-affiliate-autoinserter', false, dirname(plugin_basename(__FILE__)) . '/languages');
        if (get_option('saa_free_version') !== 'pro') {
            add_action('admin_notices', array($this, 'pro_notice'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('saa-frontend', plugin_dir_url(__FILE__) . 'assets/frontend.js', array('jquery'), '1.0.0', true);
    }

    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'smart-affiliate') !== false) {
            wp_enqueue_script('saa-admin', plugin_dir_url(__FILE__) . 'assets/admin.js', array('jquery'), '1.0.0', true);
            wp_enqueue_style('saa-admin-css', plugin_dir_url(__FILE__) . 'assets/admin.css', array(), '1.0.0');
        }
    }

    public function auto_insert_affiliate_links($content) {
        if (is_admin() || !is_single() || is_admin_bar_showing()) {
            return $content;
        }

        $keywords = get_option('saa_keywords', array());
        $affiliates = get_option('saa_affiliates', array());
        $max_links = get_option('saa_max_links', 3);
        $inserted = 0;

        if (empty($keywords) || empty($affiliates) || $max_links < 1) {
            return $content;
        }

        foreach ($keywords as $keyword => $link_id) {
            if ($inserted >= $max_links) break;
            $link = $affiliates[$link_id] ?? '';
            if (!$link) continue;

            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/ui';
            if (preg_match($pattern, $content)) {
                $replacement = '<a href="' . esc_url($link) . '" target="_blank" rel="nofollow sponsored" class="saa-affiliate-link">' . esc_html($keyword) . '</a>';
                $content = preg_replace($pattern, $replacement, $content, 1);
                $inserted++;
            }
        }

        return $content;
    }

    public function admin_menu() {
        add_options_page(
            'Smart Affiliate AutoInserter',
            'Affiliate Inserter',
            'manage_options',
            'smart-affiliate-autoinserter',
            array($this, 'settings_page')
        );
    }

    public function admin_init() {
        register_setting('saa_settings', 'saa_keywords');
        register_setting('saa_settings', 'saa_affiliates');
        register_setting('saa_settings', 'saa_max_links');
    }

    public function settings_page() {
        if (isset($_POST['saa_upgrade'])) {
            echo '<div class="notice notice-success"><p>Upgrade to Pro for AI-powered insertion and analytics!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Affiliate AutoInserter Settings', 'smart-affiliate-autoinserter'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('saa_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th>Keywords & Links</th>
                        <td>
                            <p>Add keyword:affiliate_id pairs (e.g., "best laptop":1)</p>
                            <textarea name="saa_keywords" rows="5" cols="50" placeholder="keyword1:0&#10;keyword2:1"><?php echo esc_textarea(get_option('saa_keywords', '')); ?></textarea>
                            <p>Affiliate Links (id:url):</p>
                            <textarea name="saa_affiliates" rows="5" cols="50" placeholder="0:https://amazon.com/product1&#10;1:https://amazon.com/product2"><?php echo esc_textarea(get_option('saa_affiliates', '')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th>Max Links per Post</th>
                        <td>
                            <input type="number" name="saa_max_links" value="<?php echo esc_attr(get_option('saa_max_links', 3)); ?>" min="1" max="10">
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            <div class="saa-pro-upgrade">
                <h2>Go Pro!</h2>
                <p>Unlock AI keyword detection, click tracking, A/B testing, and more for $49/year.</p>
                <a href="https://example.com/pro" class="button button-primary button-large">Upgrade Now</a>
            </div>
        </div>
        <?php
    }

    public function pro_notice() {
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate AutoInserter:</strong> Upgrade to Pro for advanced AI features and unlimited links!</p></div>';
    }

    public function activate() {
        add_option('saa_keywords', "");
        add_option('saa_affiliates', "");
        add_option('saa_max_links', 3);
        add_option('saa_free_version', 'free');
    }

    public function deactivate() {
        // Cleanup optional
    }
}

SmartAffiliateAutoInserter::get_instance();

// Create assets directories if missing
$upload_dir = plugin_dir_path(__FILE__) . 'assets';
if (!file_exists($upload_dir)) {
    wp_mkdir_p($upload_dir);
    wp_mkdir_p($upload_dir . '/js');
    wp_mkdir_p($upload_dir . '/css');
}

// Frontend JS placeholder
$frontend_js = $upload_dir . '/frontend.js';
if (!file_exists($frontend_js)) {
    file_put_contents($frontend_js, "jQuery(document).ready(function($) {
    $('.saa-affiliate-link').on('click', function() {
        // Track clicks in pro version
        console.log('Affiliate link clicked');
    });
});");
}

// Admin JS
$admin_js = $upload_dir . '/admin.js';
if (!file_exists($admin_js)) {
    file_put_contents($admin_js, "jQuery(document).ready(function($) {
    // Admin enhancements
});");
}

// Admin CSS
$admin_css = $upload_dir . '/admin.css';
if (!file_exists($admin_css)) {
    file_put_contents($admin_css, ".saa-pro-upgrade { background: #fff3cd; padding: 20px; margin: 20px 0; border-left: 4px solid #ffeaa7; }");
}
?>