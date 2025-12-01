/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: WP Smart Affiliate Link Manager
 * Description: Automatically manage, track, and optimize affiliate links across your WordPress site with smart rotation, geo-targeting, and performance analytics.
 * Version: 1.0
 * Author: WP Innovate
 */

define('WP_SMART_AFFILIATE_VERSION', '1.0');
define('WP_SMART_AFFILIATE_PLUGIN_DIR', plugin_dir_path(__FILE__));

class WPSmartAffiliateLinkManager {

    public function __construct() {
        add_action('init', array($this, 'init'));        
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('smart_affiliate_link', array($this, 'shortcode_handler'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                url varchar(512) NOT NULL,
                slug varchar(100) NOT NULL,
                clicks int(11) DEFAULT 0,
                country varchar(100),
                created_at datetime DEFAULT '0000-00-00 00:00:00',
                PRIMARY KEY (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    public function admin_menu() {
        add_menu_page(
            'Smart Affiliate Links',
            'Smart Affiliate Links',
            'manage_options',
            'wp-smart-affiliate-links',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label for="url">Affiliate URL</label></th>
                        <td><input type="url" name="url" id="url" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="slug">Slug (optional)</label></th>
                        <td><input type="text" name="slug" id="slug" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="country">Country (optional)</label></th>
                        <td><input type="text" name="country" id="country" class="regular-text" placeholder="e.g., US, UK" /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="submit_link" id="submit_link" class="button button-primary" value="Add Link" />
                </p>
            </form>
            <h2>Existing Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>Slug</th>
                        <th>Clicks</th>
                        <th>Country</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($links as $link): ?>
                    <tr>
                        <td><?php echo $link->id; ?></td>
                        <td><?php echo esc_url($link->url); ?></td>
                        <td><?php echo $link->slug; ?></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><?php echo $link->country; ?></td>
                        <td><?php echo $link->created_at; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        if(isset($_POST['submit_link'])) {
            $url = sanitize_text_field($_POST['url']);
            $slug = sanitize_text_field($_POST['slug']);
            $country = sanitize_text_field($_POST['country']);
            $now = current_time('mysql');
            $wpdb->insert(
                $table_name,
                array(
                    'url' => $url,
                    'slug' => $slug,
                    'country' => $country,
                    'created_at' => $now
                )
            );
            wp_redirect(admin_url('admin.php?page=wp-smart-affiliate-links'));
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('jquery');
    }

    public function shortcode_handler($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'smart_affiliate_links';
        $atts = shortcode_atts(array(
            'id' => '',
            'slug' => '',
            'country' => '',
            'random' => false
        ), $atts);

        $query = "SELECT * FROM $table_name WHERE 1=1";
        if($atts['id']) {
            $query .= " AND id = " . intval($atts['id']);
        }
        if($atts['slug']) {
            $query .= " AND slug = '" . esc_sql($atts['slug']) . "'";
        }
        if($atts['country']) {
            $country = esc_sql($atts['country']);
            $query .= " AND (country = '$country' OR country IS NULL)";
        }
        if($atts['random'] == 'true') {
            $query .= " ORDER BY RAND() LIMIT 1";
        } else {
            $query .= " ORDER BY id ASC LIMIT 1";
        }

        $link = $wpdb->get_row($query);
        if(!$link) return '';

        $url = $link->url;
        $clicks = $link->clicks + 1;
        $wpdb->update($table_name, array('clicks' => $clicks), array('id' => $link->id));

        return '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow">Visit Link</a>';
    }
}

new WPSmartAffiliateLinkManager();
?>