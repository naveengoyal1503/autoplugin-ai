<?php
/*
Plugin Name: Smart Content Monetizer Pro
Plugin URI: https://smartcontentmonetizer.com
Description: Intelligent WordPress monetization platform that optimizes ads, affiliates, memberships, and donations
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Content_Monetizer_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
    private $db;
    private $options;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->db = $wpdb;
        $this->options = get_option('scm_settings', array());
        $this->init_hooks();
    }

    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('the_content', array($this, 'inject_monetization'), 999);
        add_action('wp_ajax_scm_track_view', array($this, 'ajax_track_view'));
        add_action('wp_ajax_scm_track_click', array($this, 'ajax_track_click'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}scm_monetization (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            ad_revenue float DEFAULT 0,
            affiliate_revenue float DEFAULT 0,
            membership_revenue float DEFAULT 0,
            donation_revenue float DEFAULT 0,
            total_views int DEFAULT 0,
            total_clicks int DEFAULT 0,
            date_created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_date (post_id, date_created)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('scm_settings', array(
            'enable_ads' => 1,
            'enable_affiliates' => 1,
            'enable_memberships' => 0,
            'enable_donations' => 1,
            'adsense_id' => '',
            'ad_positions' => 'above,middle,below',
            'affiliate_disclosure' => 'This post contains affiliate links.',
            'donation_text' => 'Enjoyed this content? Support us with a donation!',
            'is_premium' => 0
        ));
    }

    public function deactivate() {
        // Cleanup on deactivation
    }

    public function add_admin_menu() {
        add_menu_page(
            'Smart Content Monetizer',
            'SCM Pro',
            'manage_options',
            'scm-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            80
        );

        add_submenu_page(
            'scm-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'scm-settings',
            array($this, 'render_settings')
        );

        add_submenu_page(
            'scm-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'scm-analytics',
            array($this, 'render_analytics')
        );
    }

    public function register_settings() {
        register_setting('scm_settings_group', 'scm_settings');
    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'scm-') === false) {
            return;
        }
        wp_enqueue_style('scm-admin-style', SCM_PLUGIN_URL . 'admin-style.css', array(), SCM_VERSION);
        wp_enqueue_script('scm-admin-script', SCM_PLUGIN_URL . 'admin-script.js', array('jquery'), SCM_VERSION, true);
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_script('scm-frontend', SCM_PLUGIN_URL . 'frontend.js', array('jquery'), SCM_VERSION, true);
        wp_localize_script('scm-frontend', 'scmData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scm_nonce')
        ));
    }

    public function inject_monetization($content) {
        if (!is_singular('post')) {
            return $content;
        }

        $post_id = get_the_ID();
        $output = $content;

        if ($this->options['enable_ads']) {
            $output = $this->inject_ads($output, $post_id);
        }

        if ($this->options['enable_affiliates']) {
            $output = $this->inject_affiliate_disclosure($output, $post_id);
        }

        if ($this->options['enable_donations']) {
            $output = $this->inject_donation_box($output, $post_id);
        }

        return $output;
    }

    private function inject_ads($content, $post_id) {
        if (empty($this->options['adsense_id'])) {
            return $content;
        }

        $positions = explode(',', $this->options['ad_positions']);
        $ad_html = '<div class="scm-ad-container" data-post-id="' . $post_id . '">';
        $ad_html .= '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>';
        $ad_html .= '<!-- SCM Ad Unit -->';
        $ad_html .= '<ins class="adsbygoogle" style="display:block;text-align:center;" data-ad-client="ca-' . esc_attr($this->options['adsense_id']) . '" data-ad-slot="1234567890" data-ad-format="auto" data-full-width-responsive="true"></ins>';
        $ad_html .= '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>';
        $ad_html .= '</div>';

        if (in_array('above', $positions)) {
            $content = $ad_html . $content;
        }

        if (in_array('below', $positions)) {
            $content = $content . $ad_html;
        }

        if (in_array('middle', $positions)) {
            $parts = str_split($content, strlen($content) / 2);
            $content = $parts[0] . $ad_html . $parts[1];
        }

        return $content;
    }

    private function inject_affiliate_disclosure($content, $post_id) {
        $disclosure = '<div class="scm-affiliate-disclosure" data-post-id="' . $post_id . '">';
        $disclosure .= '<p><strong>Disclosure:</strong> ' . esc_html($this->options['affiliate_disclosure']) . '</p>';
        $disclosure .= '</div>';
        return $content . $disclosure;
    }

    private function inject_donation_box($content, $post_id) {
        $donation_html = '<div class="scm-donation-box" data-post-id="' . $post_id . '">';
        $donation_html .= '<div class="scm-donation-content">';
        $donation_html .= '<p>' . wp_kses_post($this->options['donation_text']) . '</p>';
        $donation_html .= '<form class="scm-donation-form" method="POST" action="https://www.paypal.com/donate" target="_blank">';
        $donation_html .= '<input type="hidden" name="hosted_button_id" value="" />';
        $donation_html .= '<input type="submit" value="Donate Now" class="scm-donation-btn" />';
        $donation_html .= '</form>';
        $donation_html .= '</div>';
        $donation_html .= '</div>';
        return $content . $donation_html;
    }

    public function ajax_track_view() {
        check_ajax_referer('scm_nonce');
        $post_id = intval($_POST['post_id']);
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}scm_monetization (post_id, total_views, date_created) VALUES (%d, 1, NOW()) ON DUPLICATE KEY UPDATE total_views = total_views + 1",
            $post_id
        ));
        wp_send_json_success();
    }

    public function ajax_track_click() {
        check_ajax_referer('scm_nonce');
        $post_id = intval($_POST['post_id']);
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "INSERT INTO {$wpdb->prefix}scm_monetization (post_id, total_clicks, date_created) VALUES (%d, 1, NOW()) ON DUPLICATE KEY UPDATE total_clicks = total_clicks + 1",
            $post_id
        ));
        wp_send_json_success();
    }

    public function render_dashboard() {
        global $wpdb;
        $stats = $wpdb->get_results("SELECT SUM(ad_revenue) as total_ads, SUM(affiliate_revenue) as total_affiliates, SUM(membership_revenue) as total_memberships, SUM(donation_revenue) as total_donations FROM {$wpdb->prefix}scm_monetization");
        ?>
        <div class="wrap">
            <h1>Smart Content Monetizer Dashboard</h1>
            <div class="scm-dashboard-grid">
                <div class="scm-card">
                    <h3>Total Ad Revenue</h3>
                    <p class="scm-amount">$<?php echo number_format($stats[0]->total_ads ?? 0, 2); ?></p>
                </div>
                <div class="scm-card">
                    <h3>Affiliate Revenue</h3>
                    <p class="scm-amount">$<?php echo number_format($stats[0]->total_affiliates ?? 0, 2); ?></p>
                </div>
                <div class="scm-card">
                    <h3>Membership Revenue</h3>
                    <p class="scm-amount">$<?php echo number_format($stats[0]->total_memberships ?? 0, 2); ?></p>
                </div>
                <div class="scm-card">
                    <h3>Donation Revenue</h3>
                    <p class="scm-amount">$<?php echo number_format($stats[0]->total_donations ?? 0, 2); ?></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings() {
        ?>
        <div class="wrap">
            <h1>Smart Content Monetizer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('scm_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="enable_ads">Enable Display Ads</label></th>
                        <td><input type="checkbox" id="enable_ads" name="scm_settings[enable_ads]" value="1" <?php checked($this->options['enable_ads'] ?? 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="adsense_id">AdSense ID</label></th>
                        <td><input type="text" id="adsense_id" name="scm_settings[adsense_id]" value="<?php echo esc_attr($this->options['adsense_id'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label for="enable_affiliates">Enable Affiliate Links</label></th>
                        <td><input type="checkbox" id="enable_affiliates" name="scm_settings[enable_affiliates]" value="1" <?php checked($this->options['enable_affiliates'] ?? 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="enable_donations">Enable Donations</label></th>
                        <td><input type="checkbox" id="enable_donations" name="scm_settings[enable_donations]" value="1" <?php checked($this->options['enable_donations'] ?? 0, 1); ?> /></td>
                    </tr>
                    <tr>
                        <th><label for="donation_text">Donation Box Text</label></th>
                        <td><textarea id="donation_text" name="scm_settings[donation_text]"><?php echo esc_textarea($this->options['donation_text'] ?? ''); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics() {
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <p>View detailed monetization analytics and performance metrics for your content.</p>
        </div>
        <?php
    }
}

if (is_admin() || !empty($_REQUEST['action'])) {
    SmartContentMonetizer::get_instance();
}

add_action('plugins_loaded', function() {
    SmartContentMonetizer::get_instance();
});
?>