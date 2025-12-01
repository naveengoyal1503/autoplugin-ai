<?php
/*
Plugin Name: ContentMoner Pro
Plugin URI: https://contentmoner.com
Description: All-in-one WordPress monetization hub combining affiliate links, sponsored content, ad optimization, and digital products
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMoner_Pro.php
License: GPL v2 or later
Text Domain: contentmoner-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('CONTENTMONER_VERSION', '1.0.0');
define('CONTENTMONER_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMONER_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMoner {
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
        add_shortcode('contentmoner_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_shortcode('contentmoner_digital_product', array($this, 'digital_product_shortcode'));
        add_action('wp_ajax_contentmoner_create_affiliate', array($this, 'create_affiliate_link'));
        add_action('wp_ajax_contentmoner_track_click', array($this, 'track_affiliate_click'));
        add_action('init', array($this, 'register_post_types'));
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
    }

    public function activate_plugin() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoner_affiliates';
        $table_name_clicks = $wpdb->prefix . 'contentmoner_clicks';
        $table_name_products = $wpdb->prefix . 'contentmoner_products';

        $charset_collate = $wpdb->get_charset_collate();

        $sql_affiliates = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            program_name varchar(255) NOT NULL,
            affiliate_url varchar(500) NOT NULL,
            commission_rate float NOT NULL,
            custom_link varchar(255) UNIQUE,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_clicks = "CREATE TABLE IF NOT EXISTS $table_name_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            affiliate_id mediumint(9) NOT NULL,
            clicks int DEFAULT 0,
            conversions int DEFAULT 0,
            revenue float DEFAULT 0,
            click_date date NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (affiliate_id) REFERENCES $table_name(id)
        ) $charset_collate;";

        $sql_products = "CREATE TABLE IF NOT EXISTS $table_name_products (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            product_name varchar(255) NOT NULL,
            price decimal(10, 2) NOT NULL,
            description longtext,
            download_url varchar(500),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_affiliates);
        dbDelta($sql_clicks);
        dbDelta($sql_products);

        add_option('contentmoner_db_version', CONTENTMONER_VERSION);
        add_option('contentmoner_license_type', 'free');
    }

    public function deactivate_plugin() {
        // Cleanup if necessary
    }

    public function register_post_types() {
        register_post_type('contentmoner_sponsored', array(
            'labels' => array('name' => 'Sponsored Content', 'singular_name' => 'Sponsored Post'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'contentmoner_dashboard',
            'supports' => array('title', 'editor', 'thumbnail')
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            'ContentMoner Pro',
            'ContentMoner Pro',
            'manage_options',
            'contentmoner_dashboard',
            array($this, 'render_dashboard'),
            'dashicons-chart-line',
            30
        );

        add_submenu_page(
            'contentmoner_dashboard',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'contentmoner_dashboard',
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            'contentmoner_dashboard',
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            'contentmoner_affiliates',
            array($this, 'render_affiliates_page')
        );

        add_submenu_page(
            'contentmoner_dashboard',
            'Digital Products',
            'Digital Products',
            'manage_options',
            'contentmoner_products',
            array($this, 'render_products_page')
        );

        add_submenu_page(
            'contentmoner_dashboard',
            'Analytics',
            'Analytics',
            'manage_options',
            'contentmoner_analytics',
            array($this, 'render_analytics_page')
        );

        add_submenu_page(
            'contentmoner_dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'contentmoner_settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'contentmoner') !== false) {
            wp_enqueue_style('contentmoner-admin-style', CONTENTMONER_PLUGIN_URL . 'css/admin-style.css', array(), CONTENTMONER_VERSION);
            wp_enqueue_script('contentmoner-admin-script', CONTENTMONER_PLUGIN_URL . 'js/admin-script.js', array('jquery'), CONTENTMONER_VERSION, true);
            wp_localize_script('contentmoner-admin-script', 'contentmonerAjax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('contentmoner_nonce')
            ));
        }
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_script('contentmoner-frontend', CONTENTMONER_PLUGIN_URL . 'js/frontend.js', array('jquery'), CONTENTMONER_VERSION, true);
        wp_localize_script('contentmoner-frontend', 'contentmonerFrontend', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('contentmoner_frontend_nonce')
        ));
    }

    public function render_dashboard() {
        $this->check_user_capability();
        global $wpdb;
        $table_clicks = $wpdb->prefix . 'contentmoner_clicks';
        $total_clicks = $wpdb->get_var("SELECT SUM(clicks) FROM $table_clicks");
        $total_revenue = $wpdb->get_var("SELECT SUM(revenue) FROM $table_clicks");
        ?>
        <div class="wrap">
            <h1>ContentMoner Pro Dashboard</h1>
            <div class="contentmoner-dashboard">
                <div class="contentmoner-stat-box">
                    <h3>Total Affiliate Clicks</h3>
                    <p class="stat-value"><?php echo intval($total_clicks); ?></p>
                </div>
                <div class="contentmoner-stat-box">
                    <h3>Total Revenue</h3>
                    <p class="stat-value">$<?php echo number_format(floatval($total_revenue), 2); ?></p>
                </div>
                <div class="contentmoner-stat-box">
                    <h3>Active Affiliate Programs</h3>
                    <p class="stat-value"><?php echo intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentmoner_affiliates")); ?></p>
                </div>
                <div class="contentmoner-stat-box">
                    <h3>Digital Products</h3>
                    <p class="stat-value"><?php echo intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}contentmoner_products")); ?></p>
                </div>
            </div>
            <h2>Recent Activity</h2>
            <p>Track your monetization performance in real-time from this dashboard.</p>
        </div>
        <?php
    }

    public function render_affiliates_page() {
        $this->check_user_capability();
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoner_affiliates';
        ?>
        <div class="wrap">
            <h1>Affiliate Links Management</h1>
            <form method="POST" class="contentmoner-form">
                <h2>Add New Affiliate Program</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="program_name">Program Name</label></th>
                        <td><input type="text" id="program_name" name="program_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" id="affiliate_url" name="affiliate_url" required></td>
                    </tr>
                    <tr>
                        <th><label for="commission_rate">Commission Rate (%)</label></th>
                        <td><input type="number" id="commission_rate" name="commission_rate" step="0.01" required></td>
                    </tr>
                </table>
                <?php submit_button('Add Affiliate Program'); ?>
            </form>
            <h2>Your Affiliate Programs</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Program Name</th>
                        <th>Commission Rate</th>
                        <th>Custom Link</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $affiliates = $wpdb->get_results("SELECT * FROM $table_name");
                    foreach ($affiliates as $affiliate) {
                        echo '<tr>';
                        echo '<td>' . esc_html($affiliate->program_name) . '</td>';
                        echo '<td>' . esc_html($affiliate->commission_rate) . '%</td>';
                        echo '<td><code>' . esc_html($affiliate->custom_link ?: 'Not set') . '</code></td>';
                        echo '<td>' . esc_html(substr($affiliate->created_at, 0, 10)) . '</td>';
                        echo '<td><a href="#" class="button">Edit</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_products_page() {
        $this->check_user_capability();
        ?>
        <div class="wrap">
            <h1>Digital Products</h1>
            <form method="POST" class="contentmoner-form">
                <h2>Add New Digital Product</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="product_name">Product Name</label></th>
                        <td><input type="text" id="product_name" name="product_name" required></td>
                    </tr>
                    <tr>
                        <th><label for="product_price">Price</label></th>
                        <td><input type="number" id="product_price" name="product_price" step="0.01" required></td>
                    </tr>
                    <tr>
                        <th><label for="product_description">Description</label></th>
                        <td><textarea id="product_description" name="product_description" rows="5"></textarea></td>
                    </tr>
                </table>
                <?php submit_button('Add Product'); ?>
            </form>
        </div>
        <?php
    }

    public function render_analytics_page() {
        $this->check_user_capability();
        ?>
        <div class="wrap">
            <h1>Analytics & Reports</h1>
            <p>Track clicks, conversions, and revenue across all your monetization channels.</p>
        </div>
        <?php
    }

    public function render_settings_page() {
        $this->check_user_capability();
        ?>
        <div class="wrap">
            <h1>ContentMoner Settings</h1>
            <form method="POST" class="contentmoner-form">
                <h2>License Information</h2>
                <p>Current License: <strong><?php echo esc_html(get_option('contentmoner_license_type', 'free')); ?></strong></p>
                <p><a href="#" class="button button-primary">Upgrade to Premium</a></p>
            </form>
        </div>
        <?php
    }

    public function create_affiliate_link() {
        check_ajax_referer('contentmoner_nonce');
        $this->check_user_capability();
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoner_affiliates';
        
        $program_name = sanitize_text_field($_POST['program_name'] ?? '');
        $affiliate_url = esc_url_raw($_POST['affiliate_url'] ?? '');
        $commission_rate = floatval($_POST['commission_rate'] ?? 0);
        
        $wpdb->insert($table_name, array(
            'program_name' => $program_name,
            'affiliate_url' => $affiliate_url,
            'commission_rate' => $commission_rate
        ));
        
        wp_send_json_success(array('message' => 'Affiliate link created'));
    }

    public function track_affiliate_click() {
        check_ajax_referer('contentmoner_frontend_nonce');
        global $wpdb;
        $table_clicks = $wpdb->prefix . 'contentmoner_clicks';
        $affiliate_id = intval($_POST['affiliate_id'] ?? 0);
        
        $today = current_time('Y-m-d');
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_clicks WHERE affiliate_id = %d AND click_date = %s",
            $affiliate_id,
            $today
        ));
        
        if ($existing) {
            $wpdb->update($table_clicks, array('clicks' => $existing->clicks + 1), array('id' => $existing->id));
        } else {
            $wpdb->insert($table_clicks, array('affiliate_id' => $affiliate_id, 'clicks' => 1, 'click_date' => $today));
        }
        
        wp_send_json_success(array('tracked' => true));
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0, 'text' => 'Click here'), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoner_affiliates';
        $affiliate = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($atts['id'])));
        
        if (!$affiliate) return '';
        
        $link = '<a href="' . esc_url($affiliate->affiliate_url) . '" class="contentmoner-affiliate-link" data-affiliate-id="' . intval($affiliate->id) . '">' . esc_html($atts['text']) . '</a>';
        return $link;
    }

    public function digital_product_shortcode($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $table_name = $wpdb->prefix . 'contentmoner_products';
        $product = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", intval($atts['id'])));
        
        if (!$product) return '';
        
        $html = '<div class="contentmoner-product">';
        $html .= '<h3>' . esc_html($product->product_name) . '</h3>';
        $html .= '<p>' . wp_kses_post($product->description) . '</p>';
        $html .= '<p class="price">$' . number_format($product->price, 2) . '</p>';
        $html .= '<a href="#" class="button button-primary">Buy Now</a>';
        $html .= '</div>';
        return $html;
    }

    private function check_user_capability() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
    }
}

ContentMoner::get_instance();
?>
