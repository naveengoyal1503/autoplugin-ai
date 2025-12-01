/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Booster.php
*/
<?php
/**
 * Plugin Name: WP Affiliate Booster
 * Plugin URI: https://example.com/wp-affiliate-booster
 * Description: Manage, cloak, and track affiliate links with ease to boost your referral commissions.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

class WPAffiliateBooster {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'wpab_links';

        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_wpab_add_link', array($this, 'handle_add_link'));
        add_action('template_redirect', array($this, 'redirect_handler'));

        add_shortcode('wpab_affiliate_link', array($this, 'affiliate_link_shortcode'));
    }

    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            slug varchar(100) NOT NULL,
            target_url text NOT NULL,
            clicks bigint(20) DEFAULT 0 NOT NULL,
            expiration datetime DEFAULT NULL,
            created datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate() {
        // Optional: clean up database on deactivation
    }

    public function admin_menu() {
        add_menu_page('Affiliate Booster', 'Affiliate Booster', 'manage_options', 'wp-affiliate-booster', array($this, 'admin_page'), 'dashicons-chart-line');
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        global $wpdb;

        $links = $wpdb->get_results("SELECT * FROM {$this->table_name} ORDER BY created DESC");
        ?>
        <div class="wrap">
            <h1>WP Affiliate Booster</h1>
            <h2>Add New Cloaked Affiliate Link</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="wpab_add_link">
                <?php wp_nonce_field('wpab_add_link_nonce'); ?>
                <table class="form-table">
                    <tr><th><label for="slug">Slug</label></th>
                    <td><input name="slug" type="text" id="slug" class="regular-text" required></td></tr>

                    <tr><th><label for="target_url">Target URL</label></th>
                    <td><input name="target_url" type="url" id="target_url" class="regular-text" required></td></tr>

                    <tr><th><label for="expiration">Expiration Date (optional)</label></th>
                    <td><input name="expiration" type="datetime-local" id="expiration"></td></tr>
                </table>
                <input type="submit" class="button button-primary" value="Add Link">
            </form>

            <h2>Existing Links</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Slug</th>
                        <th>Target URL</th>
                        <th>Clicks</th>
                        <th>Expiration</th>
                        <th>Cloaked URL</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($links): ?>
                    <?php foreach ($links as $link): ?>
                        <tr>
                            <td><?php echo esc_html($link->slug); ?></td>
                            <td><a href="<?php echo esc_url($link->target_url); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($link->target_url); ?></a></td>
                            <td><?php echo intval($link->clicks); ?></td>
                            <td><?php echo $link->expiration ? esc_html($link->expiration) : 'None'; ?></td>
                            <td><?php echo esc_url(site_url('/go/' . $link->slug)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No affiliate links added yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function handle_add_link() {
        if (!current_user_can('manage_options') || !check_admin_referer('wpab_add_link_nonce')) {
            wp_die('Insufficient permissions or invalid nonce.');
        }

        if (empty($_POST['slug']) || empty($_POST['target_url'])) {
            wp_redirect(admin_url('admin.php?page=wp-affiliate-booster&error=missing_fields'));
            exit;
        }

        $slug = sanitize_title($_POST['slug']);
        $target = esc_url_raw($_POST['target_url']);
        $expiration = !empty($_POST['expiration']) ? sanitize_text_field($_POST['expiration']) : null;

        if ($expiration) {
            $dt = date_create($expiration);
            if (!$dt) {
                $expiration = null;
            } else {
                $expiration = $dt->format('Y-m-d H:i:s');
            }
        }

        global $wpdb;
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->table_name} WHERE slug=%s", $slug));
        if ($exists) {
            wp_redirect(admin_url('admin.php?page=wp-affiliate-booster&error=slug_exists'));
            exit;
        }

        $wpdb->insert($this->table_name, [
            'slug' => $slug,
            'target_url' => $target,
            'expiration' => $expiration
        ]);

        wp_redirect(admin_url('admin.php?page=wp-affiliate-booster&success=1'));
        exit;
    }

    public function redirect_handler() {
        if (!isset($_GET['go'])) {
            return;
        }
        $slug = sanitize_title($_GET['go']);
        global $wpdb;
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->table_name} WHERE slug=%s", $slug));
        if (!$link) {
            wp_die('Affiliate link not found.', '404 Not Found', array('response' => 404));
        }

        if ($link->expiration && strtotime($link->expiration) < time()) {
            wp_die('This affiliate link has expired.', 'Link Expired', array('response' => 410));
        }

        // Increase click count
        $wpdb->query($wpdb->prepare("UPDATE {$this->table_name} SET clicks = clicks + 1 WHERE id = %d", $link->id));

        wp_redirect(esc_url_raw($link->target_url), 301);
        exit;
    }

    public function affiliate_link_shortcode($atts) {
        $atts = shortcode_atts(array('slug' => '', 'text' => ''), $atts, 'wpab_affiliate_link');
        $slug = sanitize_title($atts['slug']);
        $text = esc_html($atts['text']);

        if (!$slug) {
            return '';
        }

        $url = site_url('/go/' . $slug);

        if (!$text) {
            $text = $url;
        }

        return '<a href="' . esc_url($url) . '" rel="nofollow noopener noreferrer" target="_blank">' . $text . '</a>';
    }
}

new WPAffiliateBooster();