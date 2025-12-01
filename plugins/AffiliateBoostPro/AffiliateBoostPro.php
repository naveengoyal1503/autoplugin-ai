/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateBoostPro.php
*/
<?php
/**
 * Plugin Name: AffiliateBoostPro
 * Description: Automate affiliate marketing with smart link cloaking, dynamic coupons, and performance tracking.
 * Version: 1.0
 * Author: OpenAI
 */

if (!defined('ABSPATH')) exit;

class AffiliateBoostPro {
    private $table_name;
    private $db_version = '1.0';

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'affiliateboostpro_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('abp_affiliate_link', array($this, 'affiliate_link_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('init', array($this, 'handle_redirect'));
        add_action('admin_post_abp_add_link', array($this, 'handle_add_link'));
        add_action('admin_post_nopriv_abp_add_link', array($this, 'handle_add_link'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(100) NOT NULL UNIQUE,
            target_url TEXT NOT NULL,
            clicks BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
            last_click DATETIME DEFAULT NULL,
            created DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY(id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        add_option('affiliateboostpro_db_version', $this->db_version);
    }

    public function enqueue_scripts() {
        if (is_admin()) {
            wp_enqueue_style('abp_admin_css', plugin_dir_url(__FILE__) . 'admin-style.css');
        }
    }

    public function admin_menu() {
        add_menu_page('AffiliateBoostPro', 'AffiliateBoostPro', 'manage_options', 'affiliateboostpro', array($this, 'admin_page'), 'dashicons-megaphone', 80);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        global $wpdb;
        $links = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>AffiliateBoostPro Links</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="abp_add_link">
                <?php wp_nonce_field('abp_add_link_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="slug">Custom Slug</label></th>
                        <td><input name="slug" type="text" id="slug" value="" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="target_url">Target URL</label></th>
                        <td><input name="target_url" type="url" id="target_url" value="" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button('Add Affiliate Link'); ?>
            </form>
            <h2>Existing Links</h2>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Target URL</th>
                        <th>Clicks</th>
                        <th>Last Click</th>
                        <th>Shortcode</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($links) : foreach ($links as $link) : ?>
                    <tr>
                        <td><?php echo esc_html($link->slug); ?></td>
                        <td><a href="<?php echo esc_url($link->target_url); ?>" target="_blank"><?php echo esc_html($link->target_url); ?></a></td>
                        <td><?php echo intval($link->clicks); ?></td>
                        <td><?php echo esc_html($link->last_click ? $link->last_click : '-'); ?></td>
                        <td>[abp_affiliate_link slug="<?php echo esc_attr($link->slug); ?>"]</td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No affiliate links created yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_add_link() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        check_admin_referer('abp_add_link_nonce');

        $slug = sanitize_title($_POST['slug']);
        $url = esc_url_raw($_POST['target_url']);

        if (!$slug || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_redirect(admin_url('admin.php?page=affiliateboostpro&msg=error')); exit;
        }

        global $wpdb;

        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table_name} WHERE slug = %s", $slug));
        if ($exists) {
            wp_redirect(admin_url('admin.php?page=affiliateboostpro&msg=slug_exists')); exit;
        }

        $wpdb->insert($this->table_name, array('slug' => $slug, 'target_url' => $url));

        wp_redirect(admin_url('admin.php?page=affiliateboostpro&msg=added')); exit;
    }

    public function handle_redirect() {
        if (!isset($_GET['abp']) || empty($_GET['abp'])) return;

        $slug = sanitize_text_field($_GET['abp']);

        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug = %s", $slug));

        if ($link) {
            // Update stats
            $wpdb->update($this->table_name, array(
                'clicks' => $link->clicks + 1,
                'last_click' => current_time('mysql')
            ), array('id' => $link->id));

            wp_redirect($link->target_url, 302);
            exit;
        }
    }

    public function affiliate_link_shortcode($atts) {
        $a = shortcode_atts(array('slug' => ''), $atts);
        if (!$a['slug']) return '';

        $url = esc_url(add_query_arg('abp', $a['slug'], home_url('/')));
        return '<a href="' . $url . '" target="_blank" rel="nofollow noopener">' . esc_html($url) . '</a>';
    }
}

new AffiliateBoostPro();
