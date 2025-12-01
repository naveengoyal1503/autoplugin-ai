<?php
/*
Plugin Name: ContentMonetizerPro
Description: Unified monetization platform combining ads, affiliates, sponsorships, and memberships
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMonetizerPro.php
License: GPL-2.0
*/

if (!defined('ABSPATH')) exit;

class ContentMonetizerPro {
    private static $instance = null;
    private $db_version = '1.0.0';
    private $option_prefix = 'cmp_';

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'inject_affiliate_links'));
        add_shortcode('cmp_membership_form', array($this, 'render_membership_form'));
        add_shortcode('cmp_donation', array($this, 'render_donation_button'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_affiliates = $wpdb->prefix . 'cmp_affiliates';
        $table_campaigns = $wpdb->prefix . 'cmp_campaigns';
        $table_analytics = $wpdb->prefix . 'cmp_analytics';

        $sql = array(
            "CREATE TABLE IF NOT EXISTS $table_affiliates (
                id BIGINT NOT NULL AUTO_INCREMENT,
                product_name VARCHAR(255) NOT NULL,
                affiliate_url LONGTEXT NOT NULL,
                commission_rate DECIMAL(5,2),
                active TINYINT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS $table_campaigns (
                id BIGINT NOT NULL AUTO_INCREMENT,
                campaign_name VARCHAR(255) NOT NULL,
                sponsor_name VARCHAR(255),
                campaign_type ENUM('sponsored_post', 'banner', 'product_placement'),
                payment_amount DECIMAL(10,2),
                start_date DATE,
                end_date DATE,
                content LONGTEXT,
                active TINYINT DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;",
            
            "CREATE TABLE IF NOT EXISTS $table_analytics (
                id BIGINT NOT NULL AUTO_INCREMENT,
                event_type VARCHAR(100),
                revenue_amount DECIMAL(10,2),
                source VARCHAR(100),
                date_recorded DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;"
        );

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        foreach ($sql as $query) {
            dbDelta($query);
        }

        update_option($this->option_prefix . 'db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup on deactivation
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentMonetizerPro',
            'ContentMonetizerPro',
            'manage_options',
            'cmp-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-money-alt',
            80
        );

        add_submenu_page(
            'cmp-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'cmp-affiliates',
            array($this, 'render_affiliates')
        );

        add_submenu_page(
            'cmp-dashboard',
            'Sponsored Content',
            'Sponsored Content',
            'manage_options',
            'cmp-campaigns',
            array($this, 'render_campaigns')
        );

        add_submenu_page(
            'cmp-dashboard',
            'Memberships',
            'Memberships',
            'manage_options',
            'cmp-memberships',
            array($this, 'render_memberships')
        );

        add_submenu_page(
            'cmp-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'cmp-analytics',
            array($this, 'render_analytics')
        );

        add_submenu_page(
            'cmp-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cmp-settings',
            array($this, 'render_settings')
        );
    }

    public function register_settings() {
        register_setting('cmp_settings', $this->option_prefix . 'adsense_id');
        register_setting('cmp_settings', $this->option_prefix . 'paypal_email');
        register_setting('cmp_settings', $this->option_prefix . 'membership_price');
        register_setting('cmp_settings', $this->option_prefix . 'donation_enabled');
    }

    public function render_dashboard() {
        global $wpdb;
        $analytics_table = $wpdb->prefix . 'cmp_analytics';
        
        $total_revenue = $wpdb->get_var(
            "SELECT SUM(revenue_amount) FROM $analytics_table WHERE DATE(date_recorded) = CURDATE()"
        );
        
        echo '<div class="wrap">';
        echo '<h1>ContentMonetizerPro Dashboard</h1>';
        echo '<div class="postbox"><h2>Today\'s Revenue</h2>';
        echo '<p style="font-size: 24px; font-weight: bold;">' . ($total_revenue ? '$' . number_format($total_revenue, 2) : '$0.00') . '</p>';
        echo '</div>';
        echo '<p>Welcome to ContentMonetizerPro! Use the menu to manage your monetization strategies.</p>';
        echo '</div>';
    }

    public function render_affiliates() {
        global $wpdb;
        $table = $wpdb->prefix . 'cmp_affiliates';

        if (isset($_POST['action']) && $_POST['action'] === 'add_affiliate' && check_admin_referer('cmp_nonce')) {
            $product_name = sanitize_text_field($_POST['product_name']);
            $affiliate_url = esc_url($_POST['affiliate_url']);
            $commission_rate = floatval($_POST['commission_rate']);

            $wpdb->insert($table, array(
                'product_name' => $product_name,
                'affiliate_url' => $affiliate_url,
                'commission_rate' => $commission_rate
            ));
        }

        $affiliates = $wpdb->get_results("SELECT * FROM $table WHERE active = 1");

        echo '<div class="wrap">';
        echo '<h1>Manage Affiliate Links</h1>';
        echo '<form method="post" action="" class="cmp-form">';
        wp_nonce_field('cmp_nonce');
        echo '<input type="hidden" name="action" value="add_affiliate">';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Product</th><th>URL</th><th>Commission %</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($affiliates as $aff) {
            echo '<tr>';
            echo '<td>' . esc_html($aff->product_name) . '</td>';
            echo '<td><a href="' . esc_url($aff->affiliate_url) . '" target="_blank">View</a></td>';
            echo '<td>' . floatval($aff->commission_rate) . '%</td>';
            echo '<td><a href="#" class="button-link delete-affiliate" data-id="' . $aff->id . '">Delete</a></td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '<h2>Add New Affiliate Link</h2>';
        echo '<label>Product Name: <input type="text" name="product_name" required></label><br>';
        echo '<label>Affiliate URL: <input type="url" name="affiliate_url" required></label><br>';
        echo '<label>Commission Rate (%): <input type="number" name="commission_rate" step="0.01" required></label><br>';
        echo '<button type="submit" class="button button-primary">Add Affiliate</button>';
        echo '</form>';
        echo '</div>';
    }

    public function render_campaigns() {
        global $wpdb;
        $table = $wpdb->prefix . 'cmp_campaigns';

        echo '<div class="wrap">';
        echo '<h1>Manage Sponsored Content</h1>';
        echo '<p>Track and manage your sponsored content campaigns here.</p>';
        echo '</div>';
    }

    public function render_memberships() {
        echo '<div class="wrap">';
        echo '<h1>Membership Plans</h1>';
        echo '<p>Configure your membership subscription settings.</p>';
        echo '<form method="post" action="options.php">';
        settings_fields('cmp_settings');
        echo '<label>Membership Price: $<input type="number" name="' . $this->option_prefix . 'membership_price" step="0.01" value="' . get_option($this->option_prefix . 'membership_price', '9.99') . '"></label><br>';
        echo '<button type="submit" class="button button-primary">Save Settings</button>';
        echo '</form>';
        echo '</div>';
    }

    public function render_analytics() {
        global $wpdb;
        $table = $wpdb->prefix . 'cmp_analytics';
        
        $analytics = $wpdb->get_results(
            "SELECT * FROM $table ORDER BY date_recorded DESC LIMIT 50"
        );

        echo '<div class="wrap">';
        echo '<h1>Revenue Analytics</h1>';
        echo '<table class="widefat">';
        echo '<thead><tr><th>Date</th><th>Event Type</th><th>Amount</th><th>Source</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($analytics as $record) {
            echo '<tr>';
            echo '<td>' . esc_html($record->date_recorded) . '</td>';
            echo '<td>' . esc_html($record->event_type) . '</td>';
            echo '<td>$' . number_format($record->revenue_amount, 2) . '</td>';
            echo '<td>' . esc_html($record->source) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        echo '</div>';
    }

    public function render_settings() {
        echo '<div class="wrap">';
        echo '<h1>ContentMonetizerPro Settings</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('cmp_settings');
        echo '<table class="form-table">';
        echo '<tr><th scope="row"><label>AdSense ID</label></th><td><input type="text" name="' . $this->option_prefix . 'adsense_id" value="' . esc_attr(get_option($this->option_prefix . 'adsense_id')) . '"></td></tr>';
        echo '<tr><th scope="row"><label>PayPal Email</label></th><td><input type="email" name="' . $this->option_prefix . 'paypal_email" value="' . esc_attr(get_option($this->option_prefix . 'paypal_email')) . '"></td></tr>';
        echo '<tr><th scope="row"><label>Enable Donations</label></th><td><input type="checkbox" name="' . $this->option_prefix . 'donation_enabled" value="1" ' . checked(get_option($this->option_prefix . 'donation_enabled'), 1, false) . '></td></tr>';
        echo '</table>';
        submit_button();
        echo '</form>';
        echo '</div>';
    }

    public function inject_affiliate_links() {
        global $wpdb;
        if (is_single() || is_page()) {
            $table = $wpdb->prefix . 'cmp_affiliates';
            $affiliates = $wpdb->get_results("SELECT * FROM $table WHERE active = 1 LIMIT 1");
            
            if ($affiliates) {
                foreach ($affiliates as $aff) {
                    $this->log_analytics('affiliate_view', 0, 'affiliate', $aff->id);
                }
            }
        }
    }

    public function render_membership_form() {
        $price = get_option($this->option_prefix . 'membership_price', '9.99');
        $paypal_email = get_option($this->option_prefix . 'paypal_email');
        
        ob_start();
        echo '<div class="cmp-membership-form">';
        echo '<h3>Premium Membership</h3>';
        echo '<p>Join for just $' . number_format($price, 2) . '/month</p>';
        echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">';
        echo '<input type="hidden" name="cmd" value="_xclick-subscriptions">';
        echo '<input type="hidden" name="business" value="' . esc_attr($paypal_email) . '">';
        echo '<input type="hidden" name="item_name" value="Premium Membership">';
        echo '<input type="hidden" name="a3" value="' . esc_attr($price) . '">';
        echo '<input type="hidden" name="p3" value="1">';
        echo '<input type="hidden" name="t3" value="M">';
        echo '<button type="submit" class="button button-primary">Subscribe Now</button>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }

    public function render_donation_button() {
        $paypal_email = get_option($this->option_prefix . 'paypal_email');
        $enabled = get_option($this->option_prefix . 'donation_enabled');
        
        if (!$enabled) return '';
        
        ob_start();
        echo '<div class="cmp-donation-button">';
        echo '<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">';
        echo '<input type="hidden" name="cmd" value="_donations">';
        echo '<input type="hidden" name="business" value="' . esc_attr($paypal_email) . '">';
        echo '<input type="hidden" name="item_name" value="Support This Blog">';
        echo '<button type="submit" class="button">Buy Me a Coffee ☕</button>';
        echo '</form>';
        echo '</div>';
        return ob_get_clean();
    }

    private function log_analytics($event_type, $amount, $source, $ref_id = null) {
        global $wpdb;
        $table = $wpdb->prefix . 'cmp_analytics';
        
        $wpdb->insert($table, array(
            'event_type' => $event_type,
            'revenue_amount' => $amount,
            'source' => $source
        ));
    }
}

$cmp = ContentMonetizerPro::getInstance();
?>