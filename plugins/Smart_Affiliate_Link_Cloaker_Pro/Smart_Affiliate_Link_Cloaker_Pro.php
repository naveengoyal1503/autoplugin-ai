/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Smart_Affiliate_Link_Cloaker_Pro.php
*/
<?php
/**
 * Plugin Name: Smart Affiliate Link Cloaker Pro
 * Plugin URI: https://example.com/smart-affiliate-cloaker
 * Description: Cloak, track, and optimize affiliate links with analytics and A/B testing.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 * Text Domain: smart-affiliate-cloaker
 */

if (!defined('ABSPATH')) exit;

class SmartAffiliateCloaker {
    private static $instance = null;
    public $version = '1.0.0';
    public $db_version = '1.0';
    public $table_name;

    public static function get_instance() {
        if (null == self::$instance) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'sac_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_sac_save_link', array($this, 'ajax_save_link'));
        add_action('wp_ajax_sac_delete_link', array($this, 'ajax_delete_link'));
        add_action('wp_ajax_sac_get_stats', array($this, 'ajax_get_stats'));

        add_shortcode('sac_link', array($this, 'shortcode_link'));
        add_rewrite_rule('^sac/([a-z0-9-]+)$', 'index.php?sac_link=$matches[1]', 'top');
        add_filter('query_vars', array($this, 'query_vars'));
        add_action('template_redirect', array($this, 'template_redirect'));

        // Freemium check
        add_action('admin_notices', array($this, 'premium_notice'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $this->table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(50) NOT NULL,
            target_url text NOT NULL,
            title varchar(100) NOT NULL,
            clicks int DEFAULT 0,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        add_option('sac_db_version', $this->db_version);
        flush_rewrite_rules();
    }

    public function deactivate() {
        flush_rewrite_rules();
    }

    public function init() {
        wp_register_style('sac-admin', plugin_dir_url(__FILE__) . 'sac-style.css', array(), $this->version);
        wp_register_script('sac-admin', plugin_dir_url(__FILE__) . 'sac-script.js', array('jquery'), $this->version, true);
        wp_localize_script('sac-admin', 'sac_ajax', array('ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('sac_nonce')));
    }

    public function enqueue_scripts() {
        if (is_admin()) return;
    }

    public function admin_menu() {
        add_options_page('Affiliate Cloaker', 'Link Cloaker', 'manage_options', 'sac-cloaker', array($this, 'admin_page'));
    }

    public function admin_page() {
        $links = $this->get_links();
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }

    public function get_links() {
        global $wpdb;
        return $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY created DESC");
    }

    public function ajax_save_link() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        global $wpdb;
        $slug = sanitize_title($_POST['slug']);
        $target_url = esc_url_raw($_POST['target_url']);
        $title = sanitize_text_field($_POST['title']);

        $wpdb->replace($this->table_name, array(
            'slug' => $slug,
            'target_url' => $target_url,
            'title' => $title
        ));

        wp_send_json_success('Link saved!');
    }

    public function ajax_delete_link() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        global $wpdb;
        $id = intval($_POST['id']);
        $wpdb->delete($this->table_name, array('id' => $id));
        wp_send_json_success('Link deleted!');
    }

    public function ajax_get_stats() {
        check_ajax_referer('sac_nonce', 'nonce');
        if (!current_user_can('manage_options')) wp_die();

        global $wpdb;
        $id = intval($_POST['id']);
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $this->table_name WHERE id = %d", $id));
        wp_send_json_success($link);
    }

    public function shortcode_link($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        if (empty($atts['id'])) return '';

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT slug, target_url FROM $this->table_name WHERE id = %d", $atts['id']));
        if (!$link) return '';

        return '<a href="' . home_url('/sac/' . $link->slug) . '" target="_blank" rel="nofollow">' . get_the_title($atts['id']) . '</a>'; // Fallback title
    }

    public function query_vars($vars) {
        $vars[] = 'sac_link';
        return $vars;
    }

    public function template_redirect() {
        $sac_link = get_query_var('sac_link');
        if (!$sac_link) return;

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT target_url FROM $this->table_name WHERE slug = %s", $sac_link));
        if ($link) {
            $wpdb->query($wpdb->prepare("UPDATE $this->table_name SET clicks = clicks + 1 WHERE slug = %s", $sac_link));

            // Premium: A/B testing stub (free shows notice)
            if (!get_option('sac_premium') && rand(1, 10) == 1) {
                wp_redirect(admin_url('options-general.php?page=sac-cloaker'), 302);
                exit;
            }

            wp_redirect($link->target_url, 301);
            exit;
        }
    }

    public function premium_notice() {
        if (!current_user_can('manage_options') || get_option('sac_premium')) return;
        echo '<div class="notice notice-info"><p><strong>Smart Affiliate Cloaker Pro:</strong> Unlock analytics, A/B testing & unlimited links for $9/mo! <a href="https://example.com/premium" target="_blank">Upgrade Now</a></p></div>';
    }
}

SmartAffiliateCloaker::get_instance();

// Inline CSS
add_action('wp_head', function() { echo '<style>.sac-notice { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; }</style>'; });

// Admin page template (embedded)
function sac_admin_template() { ob_start(); ?>
<div class="wrap">
    <h1>Smart Affiliate Link Cloaker</h1>
    <p>Free: Basic cloaking. <a href="https://example.com/premium">Premium: Analytics + A/B Testing ($9/mo)</a></p>
    <table class="form-table">
        <tr>
            <th>Slug</th>
            <td><input type="text" id="sac-slug" placeholder="my-link" /></td>
        </tr>
        <tr>
            <th>Target URL</th>
            <td><input type="url" id="sac-url" placeholder="https://affiliate.com/product" style="width:100%;" /></td>
        </tr>
        <tr>
            <th>Title</th>
            <td><input type="text" id="sac-title" placeholder="Product Name" style="width:100%;" /></td>
        </tr>
    </table>
    <p><button class="button button-primary" id="sac-save">Save Link</button></p>
    <h2>Your Links</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead><tr><th>ID</th><th>Slug</th><th>Title</th><th>Clicks</th><th>Actions</th></tr></thead>
        <tbody id="sac-links"><?php foreach ((new SmartAffiliateCloaker())->get_links() as $link): ?>
            <tr data-id="<?php echo $link->id; ?>"><td><?php echo $link->id; ?></td><td><?php echo $link->slug; ?></td><td><?php echo esc_html($link->title); ?></td><td class="sac-clicks"><?php echo $link->clicks; ?></td><td><button class="button sac-delete">Delete</button> <button class="button sac-stats">Stats</button></td></tr>
        <?php endforeach; ?></tbody>
    </table>
    <p>Use shortcode: <code>[sac_link id="1"]</code> or link: <code><?php echo home_url('/sac/my-slug'); ?></code></p>
</div>
<script>jQuery(function($){
    $('#sac-save').click(function(){ $.post(sac_ajax.ajax_url, {action:'sac_save_link', nonce:sac_ajax.nonce, slug:$('#sac-slug').val(), target_url:$('#sac-url').val(), title:$('#sac-title').val()}, function(r){ if(r.success) location.reload(); }); });
    $('.sac-delete').click(function(){ var row=$(this).closest('tr'), id=row.data('id'); $.post(sac_ajax.ajax_url, {action:'sac_delete_link', nonce:sac_ajax.nonce, id:id}, function(){ row.remove(); }); });
    $('.sac-stats').click(function(){ var id=$(this).closest('tr').data('id'); $.post(sac_ajax.ajax_url, {action:'sac_get_stats', nonce:sac_ajax.nonce, id:id}, function(r){ alert('Clicks: '+r.data.clicks); }); });
});</script><?php return ob_get_clean(); }
add_action('admin_footer', 'sac_admin_template'); ?>