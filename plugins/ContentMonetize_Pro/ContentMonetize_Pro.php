/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMonetize_Pro.php
*/
<?php
/**
 * Plugin Name: ContentMonetize Pro
 * Plugin URI: https://contentmonetizepro.com
 * Description: Comprehensive monetization plugin for WordPress blogs and content sites
 * Version: 1.0.0
 * Author: ContentMonetize
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CMP_VERSION', '1.0.0');
define('CMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMonetizePro {
    private static $instance = null;
    private $db;

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->db = new CMP_Database();
        
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAdminScripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueFrontendScripts'));
        add_shortcode('cmp_donation_button', array($this, 'donationButtonShortcode'));
        add_shortcode('cmp_affiliate_link', array($this, 'affiliateLinkShortcode'));
        add_shortcode('cmp_subscription_form', array($this, 'subscriptionFormShortcode'));
        add_action('wp_ajax_cmp_track_click', array($this, 'trackAffiliateClick'));
        add_action('wp_ajax_nopriv_cmp_track_click', array($this, 'trackAffiliateClick'));
        
        register_activation_hook(__FILE__, array($this->db, 'createTables'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function addAdminMenu() {
        add_menu_page(
            'ContentMonetize Pro',
            'ContentMonetize Pro',
            'manage_options',
            'cmp-dashboard',
            array($this, 'renderDashboard'),
            'dashicons-chart-line'
        );
        
        add_submenu_page(
            'cmp-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'cmp-affiliates',
            array($this, 'renderAffiliatesPage')
        );
        
        add_submenu_page(
            'cmp-dashboard',
            'Donations Setup',
            'Donations Setup',
            'manage_options',
            'cmp-donations',
            array($this, 'renderDonationsPage')
        );
        
        add_submenu_page(
            'cmp-dashboard',
            'Subscriptions',
            'Subscriptions',
            'manage_options',
            'cmp-subscriptions',
            array($this, 'renderSubscriptionsPage')
        );
        
        add_submenu_page(
            'cmp-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'cmp-analytics',
            array($this, 'renderAnalyticsPage')
        );
    }

    public function enqueueAdminScripts() {
        wp_enqueue_style('cmp-admin', CMP_PLUGIN_URL . 'assets/admin.css', array(), CMP_VERSION);
        wp_enqueue_script('cmp-admin', CMP_PLUGIN_URL . 'assets/admin.js', array('jquery'), CMP_VERSION, true);
        wp_localize_script('cmp-admin', 'cmpData', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('cmp_nonce')
        ));
    }

    public function enqueueFrontendScripts() {
        wp_enqueue_script('cmp-frontend', CMP_PLUGIN_URL . 'assets/frontend.js', array('jquery'), CMP_VERSION, true);
        wp_enqueue_style('cmp-frontend', CMP_PLUGIN_URL . 'assets/frontend.css', array(), CMP_VERSION);
    }

    public function renderDashboard() {
        echo '<div class="wrap"><h1>ContentMonetize Pro Dashboard</h1>';
        echo '<div class="cmp-dashboard">';
        
        $stats = $this->db->getStats();
        echo '<div class="cmp-stats-grid">';
        echo '<div class="cmp-stat-card"><h3>Total Clicks</h3><p>' . $stats['total_clicks'] . '</p></div>';
        echo '<div class="cmp-stat-card"><h3>Total Donations</h3><p>$' . number_format($stats['total_donations'], 2) . '</p></div>';
        echo '<div class="cmp-stat-card"><h3>Active Subscribers</h3><p>' . $stats['active_subscribers'] . '</p></div>';
        echo '</div>';
        
        echo '</div></div>';
    }

    public function renderAffiliatesPage() {
        echo '<div class="wrap"><h1>Manage Affiliate Links</h1>';
        echo '<form method="post" action="">';
        echo '<table class="wp-list-table widefat"><thead><tr><th>Program</th><th>Link</th><th>Clicks</th><th>Action</th></tr></thead><tbody>';
        
        $affiliates = $this->db->getAffiliateLinks();
        foreach ($affiliates as $affiliate) {
            echo '<tr>';
            echo '<td>' . esc_html($affiliate->program) . '</td>';
            echo '<td><code>' . esc_html($affiliate->url) . '</code></td>';
            echo '<td>' . $affiliate->clicks . '</td>';
            echo '<td><button class="button button-small">Edit</button></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<br><button type="submit" class="button button-primary">Add New Affiliate Link</button>';
        echo '</form></div>';
    }

    public function renderDonationsPage() {
        echo '<div class="wrap"><h1>Donations Setup</h1>';
        echo '<p>Configure your donation settings and payment methods.</p>';
        echo '</div>';
    }

    public function renderSubscriptionsPage() {
        echo '<div class="wrap"><h1>Manage Subscriptions</h1>';
        echo '<p>Set up and manage subscription tiers for your content.</p>';
        echo '</div>';
    }

    public function renderAnalyticsPage() {
        echo '<div class="wrap"><h1>Monetization Analytics</h1>';
        echo '<p>Track your revenue streams and performance metrics.</p>';
        echo '</div>';
    }

    public function donationButtonShortcode($atts) {
        $atts = shortcode_atts(array(
            'amount' => '5',
            'text' => 'Donate'
        ), $atts);
        
        return '<button class="cmp-donation-btn" data-amount="' . esc_attr($atts['amount']) . '">' . esc_html($atts['text']) . '</button>';
    }

    public function affiliateLinkShortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
            'text' => 'Click here'
        ), $atts);
        
        $link = $this->db->getAffiliateLink($atts['id']);
        if (!$link) return '';
        
        return '<a href="#" class="cmp-affiliate-link" data-link-id="' . esc_attr($atts['id']) . '">' . esc_html($atts['text']) . '</a>';
    }

    public function subscriptionFormShortcode($atts) {
        return '<div class="cmp-subscription-form"><h3>Subscribe for Premium Content</h3><form><input type="email" placeholder="Enter email"><button type="submit">Subscribe</button></form></div>';
    }

    public function trackAffiliateClick() {
        if (!isset($_POST['link_id'])) wp_die();
        
        $link_id = intval($_POST['link_id']);
        $this->db->recordClick($link_id);
        
        wp_die();
    }

    public function deactivate() {
        // Cleanup code
    }
}

class CMP_Database {
    private $wpdb;
    private $table_affiliates;
    private $table_clicks;
    private $table_donations;

    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_affiliates = $wpdb->prefix . 'cmp_affiliates';
        $this->table_clicks = $wpdb->prefix . 'cmp_clicks';
        $this->table_donations = $wpdb->prefix . 'cmp_donations';
    }

    public function createTables() {
        $charset_collate = $this->wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_affiliates} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            program varchar(100) NOT NULL,
            url text NOT NULL,
            clicks int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$this->table_clicks} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id mediumint(9) NOT NULL,
            clicked_at datetime DEFAULT CURRENT_TIMESTAMP,
            ip_address varchar(50),
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$this->table_donations} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            amount decimal(10,2) NOT NULL,
            donor_email varchar(100),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function getStats() {
        $total_clicks = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table_clicks}");
        $total_donations = $this->wpdb->get_var("SELECT SUM(amount) FROM {$this->table_donations}");
        $active_subscribers = 0; // Placeholder
        
        return array(
            'total_clicks' => $total_clicks,
            'total_donations' => $total_donations ?: 0,
            'active_subscribers' => $active_subscribers
        );
    }

    public function getAffiliateLinks() {
        return $this->wpdb->get_results("SELECT * FROM {$this->table_affiliates}");
    }

    public function getAffiliateLink($id) {
        return $this->wpdb->get_row($this->wpdb->prepare("SELECT * FROM {$this->table_affiliates} WHERE id = %d", $id));
    }

    public function recordClick($link_id) {
        $this->wpdb->insert(
            $this->table_clicks,
            array(
                'link_id' => $link_id,
                'ip_address' => $_SERVER['REMOTE_ADDR']
            ),
            array('%d', '%s')
        );
        
        $this->wpdb->query($this->wpdb->prepare(
            "UPDATE {$this->table_affiliates} SET clicks = clicks + 1 WHERE id = %d",
            $link_id
        ));
    }
}

// Initialize plugin
ContentMonetizePro::getInstance();
?>