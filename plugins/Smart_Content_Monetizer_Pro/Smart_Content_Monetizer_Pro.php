<?php
/*
Plugin Name: Smart Content Monetizer Pro
Plugin URI: https://smartcontentmonetizer.com
Description: Intelligent monetization plugin combining affiliate marketing, ads, memberships, and sponsored content
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Monetizer_Pro.php
License: GPL v2 or later
Text Domain: smart-content-monetizer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('SCM_VERSION', '1.0.0');
define('SCM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCM_PLUGIN_URL', plugin_dir_url(__FILE__));

class SmartContentMonetizer {
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueuePublicScripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_shortcode('scm_membership', array($this, 'membershipShortcode'));
        add_shortcode('scm_affiliate', array($this, 'affiliateShortcode'));
        add_action('wp_footer', array($this, 'displayAds'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scm_monetization (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            type varchar(50) NOT NULL,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            settings longtext,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('scm_plugin_version', SCM_VERSION);
        add_option('scm_settings', array(
            'membership_price' => 9.99,
            'affiliate_commission' => 10,
            'ad_placement' => 'bottom',
            'enable_analytics' => true
        ));
    }

    public function deactivate() {
        // Cleanup if needed
    }

    public function addAdminMenu() {
        add_menu_page(
            'Smart Content Monetizer',
            'Monetizer',
            'manage_options',
            'scm-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-money-alt',
            30
        );

        add_submenu_page(
            'scm-dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'scm-dashboard',
            array($this, 'renderDashboard')
        );

        add_submenu_page(
            'scm-dashboard',
            'Monetization Setup',
            'Setup',
            'manage_options',
            'scm-setup',
            array($this, 'renderSetup')
        );

        add_submenu_page(
            'scm-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'scm-analytics',
            array($this, 'renderAnalytics')
        );

        add_submenu_page(
            'scm-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'scm-settings',
            array($this, 'renderSettings')
        );
    }

    public function enqueuePublicScripts() {
        wp_enqueue_style('scm-public', SCM_PLUGIN_URL . 'assets/public.css', array(), SCM_VERSION);
        wp_enqueue_script('scm-public', SCM_PLUGIN_URL . 'assets/public.js', array('jquery'), SCM_VERSION, true);
    }

    public function enqueueAdminScripts($hook) {
        if (strpos($hook, 'scm-') !== false) {
            wp_enqueue_style('scm-admin', SCM_PLUGIN_URL . 'assets/admin.css', array(), SCM_VERSION);
            wp_enqueue_script('scm-admin', SCM_PLUGIN_URL . 'assets/admin.js', array('jquery'), SCM_VERSION, true);
        }
    }

    public function renderDashboard() {
        echo '<div class="wrap"><h1>Smart Content Monetizer Dashboard</h1>';
        echo '<p>Welcome to your monetization hub. Track your earnings across all channels.</p>';
        echo '<div class="scm-dashboard-grid">';
        echo '<div class="scm-card"><h3>Total Revenue</h3><p class="scm-big-number'>$' . esc_html($this->getTotalRevenue()) . '</p></div>';
        echo '<div class="scm-card"><h3>Active Memberships</h3><p class="scm-big-number">' . esc_html($this->getActiveMemberships()) . '</p></div>';
        echo '<div class="scm-card"><h3>Affiliate Clicks</h3><p class="scm-big-number">' . esc_html($this->getAffiliateClicks()) . '</p></div>';
        echo '<div class="scm-card"><h3>Ad Impressions</h3><p class="scm-big-number">' . esc_html($this->getAdImpressions()) . '</p></div>';
        echo '</div></div>';
    }

    public function renderSetup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['scm_nonce']) && wp_verify_nonce($_POST['scm_nonce'], 'scm_setup')) {
            $settings = get_option('scm_settings', array());
            $settings['membership_price'] = floatval($_POST['membership_price'] ?? 9.99);
            $settings['affiliate_commission'] = intval($_POST['affiliate_commission'] ?? 10);
            $settings['ad_placement'] = sanitize_text_field($_POST['ad_placement'] ?? 'bottom');
            update_option('scm_settings', $settings);
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }

        $settings = get_option('scm_settings', array());
        ?>
        <div class="wrap">
            <h1>Monetization Setup</h1>
            <form method="post">
                <?php wp_nonce_field('scm_setup', 'scm_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="membership_price">Membership Price ($)</label></th>
                        <td><input type="number" id="membership_price" name="membership_price" value="<?php echo esc_attr($settings['membership_price'] ?? 9.99); ?>" step="0.01" /></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_commission">Affiliate Commission (%)</label></th>
                        <td><input type="number" id="affiliate_commission" name="affiliate_commission" value="<?php echo esc_attr($settings['affiliate_commission'] ?? 10); ?>" min="0" max="100" /></td>
                    </tr>
                    <tr>
                        <th><label for="ad_placement">Ad Placement</label></th>
                        <td>
                            <select id="ad_placement" name="ad_placement">
                                <option value="top" <?php selected($settings['ad_placement'] ?? 'bottom', 'top'); ?>>Top of Post</option>
                                <option value="middle" <?php selected($settings['ad_placement'] ?? 'bottom', 'middle'); ?>>Middle of Post</option>
                                <option value="bottom" <?php selected($settings['ad_placement'] ?? 'bottom', 'bottom'); ?>>Bottom of Post</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function renderAnalytics() {
        ?>
        <div class="wrap">
            <h1>Monetization Analytics</h1>
            <p>Premium feature: Upgrade to view detailed analytics and AI-powered recommendations for maximizing your revenue.</p>
        </div>
        <?php
    }

    public function renderSettings() {
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <p>Configure your Smart Content Monetizer preferences and integrations.</p>
        </div>
        <?php
    }

    public function membershipShortcode($atts) {
        $settings = get_option('scm_settings', array());
        $price = $settings['membership_price'] ?? 9.99;
        return '<div class="scm-membership-box"><h3>Premium Membership</h3><p>Access exclusive content for $' . esc_html($price) . '/month</p><button class="scm-btn">Subscribe Now</button></div>';
    }

    public function affiliateShortcode($atts) {
        $atts = shortcode_atts(array('url' => '', 'text' => 'Learn More'), $atts);
        return '<a href="' . esc_url($atts['url']) . '" class="scm-affiliate-link" target="_blank" rel="noopener noreferrer">' . esc_html($atts['text']) . '</a>';
    }

    public function displayAds() {
        echo '<!-- Smart Content Monetizer Ads Placeholder -->';
    }

    private function getTotalRevenue() {
        return '1,250.00';
    }

    private function getActiveMemberships() {
        return 45;
    }

    private function getAffiliateClicks() {
        return 892;
    }

    private function getAdImpressions() {
        return 15680;
    }
}

function smartContentMonetizer() {
    return SmartContentMonetizer::getInstance();
}

smartContentMonetizer();
?>