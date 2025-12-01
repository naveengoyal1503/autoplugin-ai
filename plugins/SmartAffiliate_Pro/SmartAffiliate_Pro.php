<?php
/*
Plugin Name: SmartAffiliate Pro
Description: Intelligent affiliate link management and optimization for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=SmartAffiliate_Pro.php
License: GPL v2 or later
*/

if (!defined('ABSPATH')) exit;

class SmartAffiliatePro {
    private $plugin_dir;
    private $plugin_url;
    private $db_version = '1.0.0';

    public function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        add_action('plugins_loaded', array($this, 'init'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    public function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_footer', array($this, 'auto_inject_affiliate_links'));
        add_shortcode('affiliate_link', array($this, 'affiliate_link_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                keyword VARCHAR(255) NOT NULL,
                affiliate_url VARCHAR(500) NOT NULL,
                commission_rate DECIMAL(5,2),
                clicks INT DEFAULT 0,
                conversions INT DEFAULT 0,
                created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
            
            add_option('smartaffiliate_db_version', $this->db_version);
        }
    }

    public function deactivate() {
        // Cleanup on deactivation
    }

    public function add_admin_menu() {
        add_menu_page(
            'SmartAffiliate Pro',
            'SmartAffiliate',
            'manage_options',
            'smartaffiliate',
            array($this, 'admin_page'),
            'dashicons-attach'
        );
        
        add_submenu_page(
            'smartaffiliate',
            'Affiliate Links',
            'Links',
            'manage_options',
            'smartaffiliate',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'smartaffiliate',
            'Settings',
            'Settings',
            'manage_options',
            'smartaffiliate-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'smartaffiliate',
            'Analytics',
            'Analytics',
            'manage_options',
            'smartaffiliate-analytics',
            array($this, 'analytics_page')
        );
    }

    public function register_settings() {
        register_setting('smartaffiliate_options', 'smartaffiliate_settings');
        register_setting('smartaffiliate_options', 'smartaffiliate_auto_inject');
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        if (isset($_POST['add_link']) && check_admin_referer('smartaffiliate_add')) {
            $keyword = sanitize_text_field($_POST['keyword']);
            $url = esc_url($_POST['affiliate_url']);
            $commission = floatval($_POST['commission_rate']);
            
            $wpdb->insert(
                $table_name,
                array(
                    'keyword' => $keyword,
                    'affiliate_url' => $url,
                    'commission_rate' => $commission
                )
            );
            echo '<div class="notice notice-success"><p>Affiliate link added successfully!</p></div>';
        }
        
        if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
            $wpdb->delete($table_name, array('id' => intval($_GET['delete'])));
            echo '<div class="notice notice-success"><p>Affiliate link deleted.</p></div>';
        }
        
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_date DESC");
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro - Manage Links</h1>
            
            <form method="POST">
                <?php wp_nonce_field('smartaffiliate_add'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="keyword">Keyword</label></th>
                        <td><input type="text" name="keyword" id="keyword" required></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="affiliate_url" required></td>
                    </tr>
                    <tr>
                        <th><label for="commission_rate">Commission Rate (%)</label></th>
                        <td><input type="number" name="commission_rate" id="commission_rate" step="0.01"></td>
                    </tr>
                </table>
                <p><input type="submit" name="add_link" class="button button-primary" value="Add Affiliate Link"></p>
            </form>
            
            <h2>Your Affiliate Links</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>URL</th>
                        <th>Commission</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo esc_html($link->keyword); ?></td>
                        <td><a href="<?php echo esc_url($link->affiliate_url); ?>" target="_blank"><?php echo esc_html(substr($link->affiliate_url, 0, 50)); ?>...</a></td>
                        <td><?php echo esc_html($link->commission_rate); ?>%</td>
                        <td><?php echo esc_html($link->clicks); ?></td>
                        <td><?php echo esc_html($link->conversions); ?></td>
                        <td><a href="?page=smartaffiliate&delete=<?php echo $link->id; ?>" onclick="return confirm('Delete this link?')">Delete</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function settings_page() {
        $settings = get_option('smartaffiliate_settings', array());
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro - Settings</h1>
            <form method="POST" action="options.php">
                <?php settings_fields('smartaffiliate_options'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="auto_inject">Auto-inject Affiliate Links</label></th>
                        <td>
                            <input type="checkbox" name="smartaffiliate_auto_inject" id="auto_inject" value="1" <?php checked(get_option('smartaffiliate_auto_inject'), 1); ?>> Enable automatic link injection
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function analytics_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC LIMIT 10");
        ?>
        <div class="wrap">
            <h1>SmartAffiliate Pro - Analytics</h1>
            <h2>Top Performing Links</h2>
            <table class="widefat">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Total Clicks</th>
                        <th>Conversions</th>
                        <th>Conversion Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): 
                        $rate = $link->clicks > 0 ? round(($link->conversions / $link->clicks) * 100, 2) : 0;
                    ?>
                    <tr>
                        <td><?php echo esc_html($link->keyword); ?></td>
                        <td><?php echo esc_html($link->clicks); ?></td>
                        <td><?php echo esc_html($link->conversions); ?></td>
                        <td><?php echo esc_html($rate); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function affiliate_link_shortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        
        $atts = shortcode_atts(array('keyword' => ''), $atts);
        $keyword = sanitize_text_field($atts['keyword']);
        
        $link = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE keyword = %s",
            $keyword
        ));
        
        if ($link) {
            $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
            return '<a href="' . esc_url($link->affiliate_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($keyword) . '</a>';
        }
        return '';
    }

    public function auto_inject_affiliate_links($content) {
        if (!get_option('smartaffiliate_auto_inject')) return;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'smartaffiliate_links';
        $links = $wpdb->get_results("SELECT keyword, affiliate_url FROM $table_name");
        
        foreach ($links as $link) {
            $pattern = '/\b' . preg_quote($link->keyword, '/') . '\b/i';
            $replacement = '<a href="' . esc_url($link->affiliate_url) . '" target="_blank" rel="noopener noreferrer">' . esc_html($link->keyword) . '</a>';
            $content = preg_replace($pattern, $replacement, $content, 1);
        }
        
        return $content;
    }
}

new SmartAffiliatePro();
?>