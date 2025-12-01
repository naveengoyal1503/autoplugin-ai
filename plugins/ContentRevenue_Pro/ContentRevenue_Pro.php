<?php
/*
Plugin Name: ContentRevenue Pro
Plugin URI: https://contentrevenuepro.local
Description: Maximize WordPress monetization through affiliate link management, sponsored content tracking, and revenue analytics
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentRevenue_Pro.php
License: GPL2
Text Domain: contentrevenue-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

define('CRP_VERSION', '1.0.0');
define('CRP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CRP_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentRevenuePro {
    private static $instance = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        add_action('admin_menu', array($this, 'addAdminMenu'));
        add_action('admin_init', array($this, 'registerSettings'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueScripts'));
        add_shortcode('crp_affiliate_link', array($this, 'affiliateLinkShortcode'));
        add_shortcode('crp_revenue_tracker', array($this, 'revenueTrackerShortcode'));
        add_action('wp_ajax_crp_log_click', array($this, 'logAffiliateClick'));
        add_action('wp_ajax_nopriv_crp_log_click', array($this, 'logAffiliateClick'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        global $wpdb;
        $this->table_links = $wpdb->prefix . 'crp_affiliate_links';
        $this->table_clicks = $wpdb->prefix . 'crp_click_logs';
        $this->table_campaigns = $wpdb->prefix . 'crp_campaigns';
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql_links = "CREATE TABLE IF NOT EXISTS {$this->table_links} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(100) NOT NULL UNIQUE,
            affiliate_url text NOT NULL,
            display_text varchar(255) NOT NULL,
            category varchar(50) NOT NULL,
            commission_rate float DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        $sql_clicks = "CREATE TABLE IF NOT EXISTS {$this->table_clicks} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id varchar(100) NOT NULL,
            post_id bigint(20),
            click_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            FOREIGN KEY (link_id) REFERENCES {$this->table_links}(link_id)
        ) $charset_collate;";
        
        $sql_campaigns = "CREATE TABLE IF NOT EXISTS {$this->table_campaigns} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            campaign_name varchar(255) NOT NULL,
            description text,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_links);
        dbDelta($sql_clicks);
        dbDelta($sql_campaigns);
        
        add_option('crp_version', CRP_VERSION);
    }
    
    public function addAdminMenu() {
        add_menu_page(
            'ContentRevenue Pro',
            'ContentRevenue Pro',
            'manage_options',
            'crp-dashboard',
            array($this, 'dashboardPage'),
            'dashicons-chart-line',
            30
        );
        
        add_submenu_page(
            'crp-dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'crp-links',
            array($this, 'affiliateLinksPage')
        );
        
        add_submenu_page(
            'crp-dashboard',
            'Campaigns',
            'Campaigns',
            'manage_options',
            'crp-campaigns',
            array($this, 'campaignsPage')
        );
        
        add_submenu_page(
            'crp-dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'crp-analytics',
            array($this, 'analyticsPage')
        );
        
        add_submenu_page(
            'crp-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'crp-settings',
            array($this, 'settingsPage')
        );
    }
    
    public function registerSettings() {
        register_setting('crp_settings_group', 'crp_settings');
    }
    
    public function dashboardPage() {
        global $wpdb;
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_clicks}");
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_links}");
        $this_month_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_clicks} WHERE MONTH(click_date) = MONTH(NOW()) AND YEAR(click_date) = YEAR(NOW())");
        
        ?>
        <div class="wrap">
            <h1>ContentRevenue Pro Dashboard</h1>
            <div class="crp-dashboard-cards">
                <div class="crp-card">
                    <h3>Total Clicks</h3>
                    <p class="crp-stat"><?php echo intval($total_clicks); ?></p>
                </div>
                <div class="crp-card">
                    <h3>Active Links</h3>
                    <p class="crp-stat"><?php echo intval($total_links); ?></p>
                </div>
                <div class="crp-card">
                    <h3>This Month</h3>
                    <p class="crp-stat"><?php echo intval($this_month_clicks); ?></p>
                </div>
            </div>
            <style>
                .crp-dashboard-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
                .crp-card { background: white; padding: 20px; border: 1px solid #ddd; border-radius: 4px; }
                .crp-stat { font-size: 32px; font-weight: bold; color: #0073aa; }
            </style>
        </div>
        <?php
    }
    
    public function affiliateLinksPage() {
        global $wpdb;
        
        if (isset($_POST['crp_add_link']) && check_admin_referer('crp_add_link_nonce')) {
            $link_id = sanitize_text_field($_POST['link_id']);
            $affiliate_url = esc_url($_POST['affiliate_url']);
            $display_text = sanitize_text_field($_POST['display_text']);
            $category = sanitize_text_field($_POST['category']);
            $commission_rate = floatval($_POST['commission_rate']);
            
            $wpdb->insert($this->table_links, array(
                'link_id' => $link_id,
                'affiliate_url' => $affiliate_url,
                'display_text' => $display_text,
                'category' => $category,
                'commission_rate' => $commission_rate
            ));
            
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }
        
        if (isset($_GET['delete']) && check_admin_referer('crp_delete_link')) {
            $link_id = sanitize_text_field($_GET['delete']);
            $wpdb->delete($this->table_links, array('link_id' => $link_id));
            echo '<div class="notice notice-success"><p>Link deleted successfully!</p></div>';
        }
        
        $links = $wpdb->get_results("SELECT * FROM {$this->table_links} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Manage Affiliate Links</h1>
            <h2>Add New Link</h2>
            <form method="post">
                <?php wp_nonce_field('crp_add_link_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="link_id">Link ID</label></th>
                        <td><input type="text" name="link_id" id="link_id" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="affiliate_url" required style="width: 100%; max-width: 400px;"></td>
                    </tr>
                    <tr>
                        <th><label for="display_text">Display Text</label></th>
                        <td><input type="text" name="display_text" id="display_text" required></td>
                    </tr>
                    <tr>
                        <th><label for="category">Category</label></th>
                        <td><input type="text" name="category" id="category"></td>
                    </tr>
                    <tr>
                        <th><label for="commission_rate">Commission Rate (%)</label></th>
                        <td><input type="number" step="0.1" name="commission_rate" id="commission_rate" value="0"></td>
                    </tr>
                </table>
                <?php submit_button('Add Link', 'primary', 'crp_add_link'); ?>
            </form>
            
            <h2>Active Links</h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Link ID</th>
                        <th>Display Text</th>
                        <th>Category</th>
                        <th>Commission %</th>
                        <th>Clicks</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): 
                        $clicks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$this->table_clicks} WHERE link_id = %s", $link->link_id));
                    ?>
                    <tr>
                        <td><?php echo esc_html($link->link_id); ?></td>
                        <td><?php echo esc_html($link->display_text); ?></td>
                        <td><?php echo esc_html($link->category); ?></td>
                        <td><?php echo esc_html($link->commission_rate); ?>%</td>
                        <td><?php echo intval($clicks); ?></td>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($link->created_at))); ?></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=crp-links&delete=' . urlencode($link->link_id)), 'crp_delete_link'); ?>" onclick="return confirm('Delete this link?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function campaignsPage() {
        global $wpdb;
        
        if (isset($_POST['crp_add_campaign']) && check_admin_referer('crp_add_campaign_nonce')) {
            $campaign_name = sanitize_text_field($_POST['campaign_name']);
            $description = sanitize_textarea_field($_POST['description']);
            
            $wpdb->insert($this->table_campaigns, array(
                'campaign_name' => $campaign_name,
                'description' => $description,
                'status' => 'active'
            ));
            
            echo '<div class="notice notice-success"><p>Campaign created successfully!</p></div>';
        }
        
        $campaigns = $wpdb->get_results("SELECT * FROM {$this->table_campaigns} ORDER BY created_at DESC");
        
        ?>
        <div class="wrap">
            <h1>Manage Campaigns</h1>
            <h2>Create New Campaign</h2>
            <form method="post">
                <?php wp_nonce_field('crp_add_campaign_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="campaign_name">Campaign Name</label></th>
                        <td><input type="text" name="campaign_name" id="campaign_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea name="description" id="description" rows="4" style="width: 100%; max-width: 400px;"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Create Campaign', 'primary', 'crp_add_campaign'); ?>
            </form>
            
            <h2>Active Campaigns</h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Campaign Name</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td><?php echo esc_html($campaign->campaign_name); ?></td>
                        <td><?php echo esc_html(substr($campaign->description, 0, 50)); ?></td>
                        <td><?php echo esc_html($campaign->status); ?></td>
                        <td><?php echo esc_html(date('Y-m-d', strtotime($campaign->created_at))); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function analyticsPage() {
        global $wpdb;
        
        $top_links = $wpdb->get_results("SELECT l.link_id, l.display_text, COUNT(c.id) as total_clicks FROM {$this->table_links} l LEFT JOIN {$this->table_clicks} c ON l.link_id = c.link_id GROUP BY l.link_id ORDER BY total_clicks DESC LIMIT 10");
        
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <h2>Top Performing Links</h2>
            <table class="wp-list-table widefat">
                <thead>
                    <tr>
                        <th>Link</th>
                        <th>Total Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->display_text); ?></td>
                        <td><?php echo intval($link->total_clicks); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function settingsPage() {
        ?>
        <div class="wrap">
            <h1>ContentRevenue Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('crp_settings_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>License Status</label></th>
                        <td>
                            <p>Free Version - Upgrade to Premium for advanced features</p>
                            <a href="#" class="button button-primary">Upgrade to Premium ($9.99/month)</a>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function affiliateLinkShortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => ''
        ), $atts, 'crp_affiliate_link');
        
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_links} WHERE link_id = %s", $atts['id']));
        
        if (!$link) {
            return '';
        }
        
        $nonce = wp_create_nonce('crp_click_nonce');
        return sprintf(
            '<a href="#" class="crp-affiliate-link" data-link-id="%s" data-nonce="%s" data-url="%s">%s</a>',
            esc_attr($link->link_id),
            esc_attr($nonce),
            esc_url($link->affiliate_url),
            esc_html($link->display_text)
        );
    }
    
    public function revenueTrackerShortcode($atts) {
        global $wpdb;
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_clicks}");
        return sprintf('<div class="crp-revenue-tracker">Total Clicks: %d</div>', intval($total_clicks));
    }
    
    public function logAffiliateClick() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'crp_click_nonce')) {
            wp_send_json_error('Invalid nonce');
        }
        
        global $wpdb;
        $link_id = sanitize_text_field($_POST['link_id']);
        $post_id = intval($_POST['post_id']) ?? 0;
        
        $wpdb->insert($this->table_clicks, array(
            'link_id' => $link_id,
            'post_id' => $post_id
        ));
        
        wp_send_json_success('Click logged');
    }
    
    public function enqueueScripts() {
        wp_enqueue_script('crp-frontend', CRP_PLUGIN_URL . 'js/frontend.js', array('jquery'), CRP_VERSION);
        wp_localize_script('crp-frontend', 'crpData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_id' => get_the_ID()
        ));
    }
}

ContentRevenuePro::getInstance();
?>