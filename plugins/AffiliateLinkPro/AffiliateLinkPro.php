/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLinkPro.php
*/
<?php
/**
 * Plugin Name: AffiliateLinkPro
 * Plugin URI: https://example.com/affiliatelinkpro
 * Description: Smart affiliate link manager with click tracking, conversion analytics, and automated link rotation.
 * Version: 1.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL2
 */

define('AFFILIATELINKPRO_VERSION', '1.0');
define('AFFILIATELINKPRO_PLUGIN_DIR', plugin_dir_path(__FILE__));

class AffiliateLinkPro {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('aff_link', array($this, 'aff_link_shortcode'));
        add_action('wp_ajax_aff_link_click', array($this, 'record_click'));
        add_action('wp_ajax_nopriv_aff_link_click', array($this, 'record_click'));
    }

    public function init() {
        $this->create_table();
    }

    private function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatelinkpro_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            url text NOT NULL,
            slug varchar(200) NOT NULL,
            clicks int(11) DEFAULT 0,
            conversions int(11) DEFAULT 0,
            revenue decimal(10,2) DEFAULT 0.00,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'AffiliateLinkPro',
            'AffiliateLinkPro',
            'manage_options',
            'affiliatelinkpro',
            array($this, 'admin_page'),
            'dashicons-chart-bar'
        );
    }

    public function admin_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatelinkpro_links';
        $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY clicks DESC");
        ?>
        <div class="wrap">
            <h1>AffiliateLinkPro</h1>
            <h2>Add New Link</h2>
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th><label for="url">Affiliate URL</label></th>
                        <td><input name="url" id="url" type="text" class="regular-text" required /></td>
                    </tr>
                    <tr>
                        <th><label for="slug">Slug (optional)</label></th>
                        <td><input name="slug" id="slug" type="text" class="regular-text" /></td>
                    </tr>
                </table>
                <?php wp_nonce_field('affiliatelinkpro_add_link'); ?>
                <?php submit_button('Add Link'); ?>
            </form>
            <h2>Existing Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>URL</th>
                        <th>Slug</th>
                        <th>Clicks</th>
                        <th>Conversions</th>
                        <th>Revenue</th>
                        <th>Shortcode</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($links as $link): ?>
                    <tr>
                        <td><?php echo $link->id; ?></td>
                        <td><?php echo esc_url($link->url); ?></td>
                        <td><?php echo $link->slug; ?></td>
                        <td><?php echo $link->clicks; ?></td>
                        <td><?php echo $link->conversions; ?></td>
                        <td>$<?php echo number_format($link->revenue, 2); ?></td>
                        <td>[aff_link id="<?php echo $link->id; ?>"]</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        if (isset($_POST['url']) && wp_verify_nonce($_POST['_wpnonce'], 'affiliatelinkpro_add_link')) {
            $url = sanitize_text_field($_POST['url']);
            $slug = sanitize_text_field($_POST['slug']);
            $wpdb->insert($table_name, array(
                'url' => $url,
                'slug' => $slug
            ));
            wp_redirect(admin_url('admin.php?page=affiliatelinkpro'));
            exit;
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliatelinkpro-ajax', plugin_dir_url(__FILE__) . 'js/ajax.js', array('jquery'), AFFILIATELINKPRO_VERSION, true);
        wp_localize_script('affiliatelinkpro-ajax', 'affiliatelinkpro_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function aff_link_shortcode($atts) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatelinkpro_links';
        $atts = shortcode_atts(array('id' => 0), $atts, 'aff_link');
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $atts['id']));
        if (!$link) return '';
        $url = add_query_arg('ref', $link->id, home_url('/aff_click'));
        return '<a href="' . esc_url($url) . '" class="affiliatelinkpro-link" data-id="' . $link->id . '" target="_blank">Visit Link</a>';
    }

    public function record_click() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatelinkpro_links';
        $id = intval($_POST['id']);
        $wpdb->query($wpdb->prepare("UPDATE $table_name SET clicks = clicks + 1 WHERE id = %d", $id));
        wp_die('ok');
    }
}

new AffiliateLinkPro();

// JavaScript for tracking clicks
add_action('wp_footer', function() {
    if (is_user_logged_in()) return; // Don't track admin
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.affiliatelinkpro-link').forEach(function(el) {
            el.addEventListener('click', function(e) {
                e.preventDefault();
                var id = this.getAttribute('data-id');
                jQuery.post(affiliatelinkpro_ajax.ajax_url, {
                    action: 'aff_link_click',
                    id: id
                });
                setTimeout(function() {
                    window.open(el.href, '_blank');
                }, 500);
            });
        });
    });
    </script>
    <?php
});

// Handle redirect for affiliate links
add_action('template_redirect', function() {
    if (isset($_GET['ref'])) {
        $id = intval($_GET['ref']);
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliatelinkpro_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT url FROM $table_name WHERE id = %d", $id));
        if ($link) {
            wp_redirect($link->url);
            exit;
        }
    }
});
?>