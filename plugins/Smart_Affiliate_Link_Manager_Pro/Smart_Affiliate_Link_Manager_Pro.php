/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager Pro
 * Description: Advanced affiliate link management with geolocation targeting and analytics
 * Version: 1.0.0
 * Author: Plugin Developer
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateLinkManager {
    private $plugin_slug = 'salm-pro';
    private $db_version = '1.0';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate_plugin'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate_plugin'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'enqueue_tracking'));
        add_shortcode('affiliate_link', array($this, 'shortcode_affiliate_link'));
        add_action('wp_ajax_get_link_stats', array($this, 'ajax_get_link_stats'));
    }

    public function activate_plugin() {
        global $wpdb;
        $table_links = $wpdb->prefix . 'salm_links';
        $table_clicks = $wpdb->prefix . 'salm_clicks';
        $table_conversions = $wpdb->prefix . 'salm_conversions';

        $charset_collate = $wpdb->get_charset_collate();

        $sql_links = "CREATE TABLE IF NOT EXISTS $table_links (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_slug varchar(100) NOT NULL,
            destination_url varchar(500) NOT NULL,
            affiliate_code varchar(100),
            category varchar(100),
            geolocation varchar(50),
            created_date datetime DEFAULT CURRENT_TIMESTAMP,
            status varchar(20) DEFAULT 'active',
            PRIMARY KEY (id),
            UNIQUE KEY link_slug (link_slug)
        ) $charset_collate;";

        $sql_clicks = "CREATE TABLE IF NOT EXISTS $table_clicks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id mediumint(9) NOT NULL,
            ip_address varchar(45),
            country varchar(2),
            device_type varchar(20),
            click_timestamp datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_conversions = "CREATE TABLE IF NOT EXISTS $table_conversions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            link_id mediumint(9) NOT NULL,
            conversion_value decimal(10, 2),
            conversion_date datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_links);
        dbDelta($sql_clicks);
        dbDelta($sql_conversions);

        update_option('salm_pro_db_version', $this->db_version);
        update_option('salm_pro_license_type', 'free');
    }

    public function deactivate_plugin() {
        delete_option('salm_pro_db_version');
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Links',
            'Affiliate Links',
            'manage_options',
            $this->plugin_slug,
            array($this, 'display_dashboard'),
            'dashicons-link',
            20
        );

        add_submenu_page(
            $this->plugin_slug,
            'Create Link',
            'Create Link',
            'manage_options',
            $this->plugin_slug . '-create',
            array($this, 'display_create_link')
        );

        add_submenu_page(
            $this->plugin_slug,
            'Analytics',
            'Analytics',
            'manage_options',
            $this->plugin_slug . '-analytics',
            array($this, 'display_analytics')
        );

        add_submenu_page(
            $this->plugin_slug,
            'Settings',
            'Settings',
            'manage_options',
            $this->plugin_slug . '-settings',
            array($this, 'display_settings')
        );
    }

    public function display_dashboard() {
        global $wpdb;
        $table_links = $wpdb->prefix . 'salm_links';
        $total_links = $wpdb->get_var("SELECT COUNT(*) FROM $table_links");
        $table_clicks = $wpdb->prefix . 'salm_clicks';
        $total_clicks = $wpdb->get_var("SELECT COUNT(*) FROM $table_clicks");
        ?>
        <div class="wrap">
            <h1>Affiliate Link Manager - Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 20px 0;">
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                    <h3>Total Links</h3>
                    <p style="font-size: 28px; font-weight: bold;"><?php echo $total_links; ?></p>
                </div>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                    <h3>Total Clicks</h3>
                    <p style="font-size: 28px; font-weight: bold;"><?php echo $total_clicks; ?></p>
                </div>
                <div style="background: #f5f5f5; padding: 20px; border-radius: 5px;">
                    <h3>License</h3>
                    <p style="font-size: 18px; font-weight: bold;"><?php echo ucfirst(get_option('salm_pro_license_type', 'free')); ?></p>
                </div>
            </div>
            <p><a href="?page=salm-pro-create" class="button button-primary">Create New Link</a></p>
        </div>
        <?php
    }

    public function display_create_link() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salm_create_nonce']) && wp_verify_nonce($_POST['salm_create_nonce'], 'salm_create_link')) {
            $this->save_affiliate_link($_POST);
        }
        ?>
        <div class="wrap">
            <h1>Create Affiliate Link</h1>
            <form method="POST">
                <?php wp_nonce_field('salm_create_link', 'salm_create_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="link_slug">Link Slug</label></th>
                        <td><input type="text" id="link_slug" name="link_slug" required></td>
                    </tr>
                    <tr>
                        <th><label for="destination_url">Destination URL</label></th>
                        <td><input type="url" id="destination_url" name="destination_url" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_code">Affiliate Code</label></th>
                        <td><input type="text" id="affiliate_code" name="affiliate_code"></td>
                    </tr>
                    <tr>
                        <th><label for="category">Category</label></th>
                        <td><input type="text" id="category" name="category"></td>
                    </tr>
                    <tr>
                        <th><label for="geolocation">Geolocation Targeting (optional)</label></th>
                        <td><input type="text" id="geolocation" name="geolocation" placeholder="e.g., US, UK, AU"></td>
                    </tr>
                </table>
                <p><input type="submit" value="Create Link" class="button button-primary"></p>
            </form>
        </div>
        <?php
    }

    public function display_analytics() {
        global $wpdb;
        $table_links = $wpdb->prefix . 'salm_links';
        $table_clicks = $wpdb->prefix . 'salm_clicks';
        
        $links = $wpdb->get_results("SELECT * FROM $table_links ORDER BY id DESC");
        ?>
        <div class="wrap">
            <h1>Analytics</h1>
            <table class="widefat fixed">
                <thead>
                    <tr>
                        <th>Link Slug</th>
                        <th>Clicks</th>
                        <th>Category</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link) {
                        $clicks = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_clicks WHERE link_id = %d", $link->id));
                        ?>
                        <tr>
                            <td><?php echo esc_html($link->link_slug); ?></td>
                            <td><?php echo $clicks; ?></td>
                            <td><?php echo esc_html($link->category); ?></td>
                            <td><?php echo esc_html($link->created_date); ?></td>
                            <td><button class="button" onclick="salm_view_stats(<?php echo $link->id; ?>">View</button></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function display_settings() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salm_settings_nonce']) && wp_verify_nonce($_POST['salm_settings_nonce'], 'salm_settings')) {
            update_option('salm_pro_tracking_enabled', isset($_POST['tracking_enabled']) ? 1 : 0);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $tracking_enabled = get_option('salm_pro_tracking_enabled', 1);
        ?>
        <div class="wrap">
            <h1>Settings</h1>
            <form method="POST">
                <?php wp_nonce_field('salm_settings', 'salm_settings_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="tracking_enabled">Enable Click Tracking</label></th>
                        <td><input type="checkbox" id="tracking_enabled" name="tracking_enabled" <?php checked($tracking_enabled); ?> ></td>
                    </tr>
                </table>
                <p><input type="submit" value="Save Settings" class="button button-primary"></p>
            </form>
            <hr>
            <h2>Upgrade to Premium</h2>
            <p>Get advanced analytics, unlimited links, and priority support. <a href="#">Upgrade Now</a></p>
        </div>
        <?php
    }

    private function save_affiliate_link($data) {
        global $wpdb;
        $table_links = $wpdb->prefix . 'salm_links';
        
        $result = $wpdb->insert($table_links, array(
            'link_slug' => sanitize_text_field($data['link_slug']),
            'destination_url' => esc_url_raw($data['destination_url']),
            'affiliate_code' => sanitize_text_field($data['affiliate_code']),
            'category' => sanitize_text_field($data['category']),
            'geolocation' => sanitize_text_field($data['geolocation'])
        ));
        
        if ($result) {
            echo '<div class="notice notice-success"><p>Link created successfully!</p></div>';
        }
    }

    public function shortcode_affiliate_link($atts) {
        $atts = shortcode_atts(array('slug' => '', 'text' => 'Click here'), $atts);
        global $wpdb;
        $table_links = $wpdb->prefix . 'salm_links';
        
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_links WHERE link_slug = %s AND status = 'active'", $atts['slug']));
        
        if ($link) {
            return sprintf('<a href="%s" data-salm-link="%d" class="salm-affiliate-link">%s</a>', 
                esc_url($link->destination_url), 
                $link->id, 
                esc_html($atts['text'])
            );
        }
        return '';
    }

    public function enqueue_tracking() {
        if (get_option('salm_pro_tracking_enabled', 1)) {
            ?>
            <script>
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('salm-affiliate-link')) {
                    var link_id = e.target.getAttribute('data-salm-link');
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send('action=salm_track_click&link_id=' + link_id);
                }
            });
            </script>
            <?php
        }
    }

    public function ajax_get_link_stats() {
        global $wpdb;
        $link_id = intval($_POST['link_id']);
        $table_clicks = $wpdb->prefix . 'salm_clicks';
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT country, COUNT(*) as count FROM $table_clicks WHERE link_id = %d GROUP BY country",
            $link_id
        ));
        
        wp_send_json($stats);
    }
}

if (is_admin()) {
    new SmartAffiliateLinkManager();
}

add_action('wp_ajax_salm_track_click', function() {
    global $wpdb;
    $link_id = intval($_POST['link_id']);
    $table_clicks = $wpdb->prefix . 'salm_clicks';
    
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $device_type = wp_is_mobile() ? 'mobile' : 'desktop';
    $country = 'US';
    
    $wpdb->insert($table_clicks, array(
        'link_id' => $link_id,
        'ip_address' => $ip_address,
        'device_type' => $device_type,
        'country' => $country
    ));
    
    wp_die();
});
?>