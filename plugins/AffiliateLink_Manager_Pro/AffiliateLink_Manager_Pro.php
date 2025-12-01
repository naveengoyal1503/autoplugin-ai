/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AffiliateLink_Manager_Pro.php
*/
<?php
/**
 * Plugin Name: AffiliateLink Manager Pro
 * Description: Manage, track, and optimize affiliate links with advanced analytics and automated link cloaking.
 * Version: 1.0
 * Author: Your Company
 */

define('ALMP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ALMP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'almp_activate');
register_deactivation_hook(__FILE__, 'almp_deactivate');

function almp_activate() {
    // Create table for storing affiliate links
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        url text NOT NULL,
        slug varchar(200) NOT NULL,
        clicks int(11) DEFAULT 0,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function almp_deactivate() {
    // Optional: Cleanup on deactivation
}

// Add admin menu
add_action('admin_menu', 'almp_add_admin_menu');
function almp_add_admin_menu() {
    add_menu_page(
        'AffiliateLink Manager Pro',
        'Affiliate Links',
        'manage_options',
        'affiliate-link-manager-pro',
        'almp_admin_page',
        'dashicons-admin-links'
    );
}

// Admin page
function almp_admin_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';

    if (isset($_POST['add_link'])) {
        $url = sanitize_text_field($_POST['url']);
        $slug = sanitize_title($_POST['slug']);
        $wpdb->insert($table_name, array('url' => $url, 'slug' => $slug));
    }

    if (isset($_GET['delete'])) {
        $id = intval($_GET['delete']);
        $wpdb->delete($table_name, array('id' => $id));
    }

    $links = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");
    ?>
    <div class="wrap">
        <h1>AffiliateLink Manager Pro</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th><label for="url">Affiliate URL</label></th>
                    <td><input type="text" name="url" id="url" class="regular-text" required></td>
                </tr>
                <tr>
                    <th><label for="slug">Slug (short name)</label></th>
                    <td><input type="text" name="slug" id="slug" class="regular-text" required></td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="add_link" class="button-primary" value="Add Link"></p>
        </form>
        <h2>Existing Links</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>URL</th>
                    <th>Slug</th>
                    <th>Clicks</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($links as $link): ?>
                <tr>
                    <td><?php echo $link->id; ?></td>
                    <td><?php echo esc_url($link->url); ?></td>
                    <td><?php echo $link->slug; ?></td>
                    <td><?php echo $link->clicks; ?></td>
                    <td><?php echo $link->created_at; ?></td>
                    <td><a href="?page=affiliate-link-manager-pro&delete=<?php echo $link->id; ?>" onclick="return confirm('Are you sure?');">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Shortcode to display affiliate link
add_shortcode('affiliate_link', 'almp_affiliate_link_shortcode');
function almp_affiliate_link_shortcode($atts) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'affiliate_links';
    $atts = shortcode_atts(array('slug' => ''), $atts);
    $slug = sanitize_title($atts['slug']);
    $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug));
    if ($link) {
        // Increment click count
        $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
        return '<a href="' . esc_url($link->url) . '" target="_blank">Visit Link</a>';
    }
    return 'Link not found.';
}

// Rewrite rule for cloaked links
add_action('init', 'almp_add_rewrite_rule');
function almp_add_rewrite_rule() {
    add_rewrite_rule('^go/([^/]+)/?', 'index.php?almp_slug=$matches[1]', 'top');
}

// Query vars
add_filter('query_vars', 'almp_add_query_vars');
function almp_add_query_vars($vars) {
    $vars[] = 'almp_slug';
    return $vars;
}

// Template redirect for cloaked links
add_action('template_redirect', 'almp_template_redirect');
function almp_template_redirect() {
    global $wp_query;
    $slug = get_query_var('almp_slug');
    if ($slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'affiliate_links';
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $slug));
        if ($link) {
            $wpdb->update($table_name, array('clicks' => $link->clicks + 1), array('id' => $link->id));
            wp_redirect($link->url, 301);
            exit;
        }
    }
}

// Flush rewrite rules on activation
function almp_activate() {
    almp_add_rewrite_rule();
    flush_rewrite_rules();
}

// Deactivate: flush rules
function almp_deactivate() {
    flush_rewrite_rules();
}
?>