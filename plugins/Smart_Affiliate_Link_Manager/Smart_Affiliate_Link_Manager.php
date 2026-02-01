/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Manager.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Manager
 * Plugin URI: https://example.com/smart-affiliate-link-manager
 * Description: Automatically cloaks, tracks clicks, and displays affiliate links with performance analytics to boost conversions and commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class SmartAffiliateLinkManager {
    private static $instance = null;
    private $db_version = '1.0';
    private $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'salml_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_filter('the_content', array($this, 'replace_links'));
        add_shortcode('salml_link', array($this, 'shortcode_link'));
        add_action('wp_ajax_salml_track_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_salml_track_click', array($this, 'track_click'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            shortcode varchar(50) NOT NULL,
            affiliate_url text NOT NULL,
            description varchar(255) DEFAULT '',
            clicks int DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY shortcode (shortcode)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('salml_db_version', $this->db_version);
    }

    public function deactivate() {
        // Cleanup optional
    }

    public function init() {
        if (get_option('salml_db_version') !== $this->db_version) {
            $this->activate();
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('salml-frontend', plugin_dir_url(__FILE__) . 'salml-frontend.js', array('jquery'), '1.0.0', true);
        wp_localize_script('salml-frontend', 'salml_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function admin_menu() {
        add_options_page('Affiliate Links', 'Affiliate Links', 'manage_options', 'salml', array($this, 'admin_page'));
    }

    public function admin_enqueue_scripts($hook) {
        if ('settings_page_salml' !== $hook) {
            return;
        }
        wp_enqueue_script('salml-admin', plugin_dir_url(__FILE__) . 'salml-admin.js', array('jquery'), '1.0.0', true);
    }

    public function admin_page() {
        global $wpdb;

        if (isset($_POST['add_link'])) {
            $shortcode = sanitize_text_field($_POST['shortcode']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $desc = sanitize_text_field($_POST['description']);

            $wpdb->insert(
                $this->table_name,
                array(
                    'shortcode' => $shortcode,
                    'affiliate_url' => $url,
                    'description' => $desc
                )
            );
            echo '<div class="notice notice-success"><p>Link added!</p></div>';
        }

        $links = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        ?>
        <div class="wrap">
            <h1>Smart Affiliate Link Manager</h1>
            <p><strong>Pro Version:</strong> Unlock advanced analytics, A/B testing, and unlimited links for $49/year!</p>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Shortcode</th>
                        <td><input type="text" name="shortcode" placeholder="[salml id=1]" required pattern="\[salml id=\\d+\]" title="Use format [salml id=1]"></td>
                    </tr>
                    <tr>
                        <th>Affiliate URL</th>
                        <td><input type="url" name="affiliate_url" style="width: 400px;" required></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><input type="text" name="description" style="width: 400px;"></td>
                    </tr>
                </table>
                <?php submit_button('Add Link', 'primary', 'add_link'); ?>
            </form>
            <h2>Your Links (Free: Max 5)</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>ID</th><th>Shortcode</th><th>URL</th><th>Clicks</th><th>Created</th></tr></thead>
                <tbody>
        <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo $link->id; ?></td>
                        <td><code><?php echo $link->shortcode; ?></code></td>
                        <td><a href="<?php echo esc_url($link->affiliate_url); ?>" target="_blank"><?php echo esc_url($link->affiliate_url); ?></a></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><?php echo $link->created_at; ?></td>
                    </tr>
        <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function replace_links($content) {
        global $wpdb;
        preg_match_all('/\[salml id=(\d+)\]/', $content, $matches);
        if (!empty($matches[1])) {
            foreach ($matches[1] as $id) {
                $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
                if ($link) {
                    $cloaked = '[salml_link id="' . $id . '"]';
                    $content = str_replace("[salml id=$id]", $cloaked, $content);
                }
            }
        }
        return $content;
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => 0), $atts);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $atts['id']));
        if (!$link) return '';

        $domain = parse_url(home_url(), PHP_URL_HOST);
        $cloaked_url = "https://$domain/go/" . $link->id;

        return "<a href='$cloaked_url' class='salml-link' data-id='$link->id' onclick='salmlTrack(this); return false;'>" . esc_html($link->description ?: $cloaked_url) . '</a>';
    }

    public function track_click() {
        if (!isset($_POST['id'])) {
            wp_die();
        }
        $id = intval($_POST['id']);
        global $wpdb;
        $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE id = %d", $id));

        $link = $wpdb->get_row($wpdb->prepare("SELECT affiliate_url FROM $this->table_name WHERE id = %d", $id));
        if ($link) {
            wp_redirect($link->affiliate_url);
            exit;
        }
    }
}

// Enqueue JS files (inline for single file)
function salml_frontend_js() {
    ?>
    <script type='text/javascript'>
    jQuery(document).ready(function($) {
        window.salmlTrack = function(link) {
            var id = $(link).data('id');
            $.post(salml_ajax.ajax_url, {action: 'salml_track_click', id: id}, function() {
                window.location = link.href;
            });
        };
    });
    </script>
    <?php
}
add_action('wp_footer', 'salml_frontend_js');

SmartAffiliateLinkManager::get_instance();