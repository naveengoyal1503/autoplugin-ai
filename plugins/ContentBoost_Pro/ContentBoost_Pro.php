<?php
/*
Plugin Name: ContentBoost Pro
Plugin URI: https://contentboostpro.com
Description: AI-powered content optimization and monetization management for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentBoost_Pro.php
License: GPL v2 or later
Text Domain: contentboost-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CONTENTBOOST_VERSION', '1.0.0');
define('CONTENTBOOST_PATH', plugin_dir_path(__FILE__));
define('CONTENTBOOST_URL', plugin_dir_url(__FILE__));

class ContentBoostPro {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->init_hooks();
        $this->register_activation();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_shortcode('contentboost_affiliate_box', array($this, 'render_affiliate_box'));
        add_filter('the_content', array($this, 'inject_ad_placements'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentBoost Pro',
            'ContentBoost Pro',
            'manage_options',
            'contentboost-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-trending-up',
            30
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Ad Settings',
            'Ad Settings',
            'manage_options',
            'contentboost-ads',
            array($this, 'render_ads_page')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'contentboost-affiliates',
            array($this, 'render_affiliates_page')
        );

        add_submenu_page(
            'contentboost-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentboost-analytics',
            array($this, 'render_analytics_page')
        );
    }

    public function register_settings() {
        register_setting('contentboost_settings', 'contentboost_adsense_id');
        register_setting('contentboost_settings', 'contentboost_affiliate_links');
        register_setting('contentboost_settings', 'contentboost_ad_positions');
        register_setting('contentboost_settings', 'contentboost_premium_tier');
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('contentboost-frontend', CONTENTBOOST_URL . 'assets/frontend.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-frontend', CONTENTBOOST_URL . 'assets/frontend.js', array('jquery'), CONTENTBOOST_VERSION, true);
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('contentboost-admin', CONTENTBOOST_URL . 'assets/admin.css', array(), CONTENTBOOST_VERSION);
        wp_enqueue_script('contentboost-admin', CONTENTBOOST_URL . 'assets/admin.js', array('jquery', 'chart.js'), CONTENTBOOST_VERSION, true);
    }

    public function render_dashboard() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>ContentBoost Pro Dashboard</h1>
            <div class="contentboost-dashboard">
                <div class="dashboard-card">
                    <h2>Revenue Overview</h2>
                    <p class="big-number"><?php echo $this->get_total_revenue(); ?></p>
                    <p>This Month</p>
                </div>
                <div class="dashboard-card">
                    <h2>Active Ad Placements</h2>
                    <p class="big-number"><?php echo $this->get_active_ad_count(); ?></p>
                </div>
                <div class="dashboard-card">
                    <h2>Affiliate Links Tracked</h2>
                    <p class="big-number"><?php echo $this->get_affiliate_count(); ?></p>
                </div>
                <div class="dashboard-card">
                    <h2>Content Performance</h2>
                    <p class="big-number"><?php echo $this->get_top_performing_posts(); ?></p>
                </div>
            </div>
            <div class="upgrade-section">
                <h3>Upgrade to Pro</h3>
                <p>Get advanced analytics, AI content optimization, and priority support.</p>
                <button class="button button-primary">Upgrade Now</button>
            </div>
        </div>
        <?php
    }

    public function render_ads_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        if (isset($_POST['contentboost_save_ads'])) {
            check_admin_referer('contentboost_ads_nonce');
            update_option('contentboost_adsense_id', sanitize_text_field($_POST['adsense_id']));
            echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
        }
        ?>
        <div class="wrap">
            <h1>Ad Settings</h1>
            <form method="post">
                <?php wp_nonce_field('contentboost_ads_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="adsense_id">Google AdSense ID</label></th>
                        <td>
                            <input type="text" name="adsense_id" id="adsense_id" value="<?php echo esc_attr(get_option('contentboost_adsense_id')); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th><label>Ad Positions</label></th>
                        <td>
                            <label><input type="checkbox" name="ad_positions[]" value="after_title" /> After Title</label><br />
                            <label><input type="checkbox" name="ad_positions[]" value="middle_content" /> Middle of Content</label><br />
                            <label><input type="checkbox" name="ad_positions[]" value="after_content" /> After Content</label>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Save Ad Settings', 'primary', 'contentboost_save_ads'); ?>
            </form>
        </div>
        <?php
    }

    public function render_affiliates_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Affiliate Links Manager</h1>
            <p>Manage and track your affiliate links with detailed click and conversion analytics.</p>
            <button class="button button-primary">Add New Affiliate Link</button>
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th>Link Name</th>
                        <th>URL</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Revenue</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="6" style="text-align:center;">No affiliate links yet. Premium feature.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_analytics_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <div id="contentboost-chart"></div>
            <p style="text-align: center; color: #999; padding: 40px;">Analytics dashboard available in Premium plan</p>
        </div>
        <?php
    }

    public function inject_ad_placements($content) {
        if (is_single() && !is_admin()) {
            $adsense_id = get_option('contentboost_adsense_id');
            if (!empty($adsense_id)) {
                $ad_code = '<div class="contentboost-ad-placement">';
                $ad_code .= '<!-- Google AdSense Ad -->';
                $ad_code .= '</div>';
                $content = $ad_code . $content . $ad_code;
            }
        }
        return $content;
    }

    public function render_affiliate_box($atts) {
        $atts = shortcode_atts(array(
            'title' => 'Recommended Product',
            'url' => '',
            'image' => '',
            'price' => ''
        ), $atts);

        $html = '<div class="contentboost-affiliate-box">';
        $html .= '<h3>' . esc_html($atts['title']) . '</h3>';
        if (!empty($atts['image'])) {
            $html .= '<img src="' . esc_url($atts['image']) . '" alt="' . esc_attr($atts['title']) . '" />';
        }
        if (!empty($atts['price'])) {
            $html .= '<p class="price">' . esc_html($atts['price']) . '</p>';
        }
        if (!empty($atts['url'])) {
            $html .= '<a href="' . esc_url($atts['url']) . '" target="_blank" class="button">Learn More</a>';
        }
        $html .= '</div>';

        return $html;
    }

    private function get_total_revenue() {
        return '$0.00';
    }

    private function get_active_ad_count() {
        return '0';
    }

    private function get_affiliate_count() {
        return '0';
    }

    private function get_top_performing_posts() {
        return 'N/A';
    }

    public function register_activation() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        add_option('contentboost_adsense_id', '');
        add_option('contentboost_affiliate_links', array());
        add_option('contentboost_ad_positions', array('after_content'));
    }

    public function deactivate() {
        flush_rewrite_rules();
    }
}

ContentBoostPro::get_instance();
?>