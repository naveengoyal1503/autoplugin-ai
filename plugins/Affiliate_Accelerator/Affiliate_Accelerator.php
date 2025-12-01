/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Accelerator.php
*/
<?php
/**
 * Plugin Name: Affiliate Accelerator
 * Description: Manage and boost your affiliate links with AI-powered link recommendations and conversion tracking.
 * Version: 1.0
 * Author: PluginDev
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class AffiliateAccelerator {

    private $option_name = 'affiliate_accelerator_links';

    public function __construct() {
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('admin_post_save_affiliate_link', array($this, 'save_link'));
        add_shortcode('affiliate_accelerator_recommendations', array($this, 'show_recommendations'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_track_affiliate_click', array($this, 'track_click'));
        add_action('wp_ajax_nopriv_track_affiliate_click', array($this, 'track_click'));
    }

    public function admin_menu() {
        add_menu_page('Affiliate Accelerator', 'Affiliate Accelerator', 'manage_options', 'affiliate-accelerator', array($this, 'admin_page'), 'dashicons-admin-links');
    }

    public function admin_page() {
        if ( ! current_user_can('manage_options') ) {
            wp_die('Unauthorized user');
        }
        $links = get_option($this->option_name, array());
        ?>
        <div class="wrap">
            <h1>Affiliate Accelerator Links</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="save_affiliate_link" />
                <?php wp_nonce_field('save_affiliate_link_nonce'); ?>
                <table class="form-table">
                    <tr><th><label for="affiliate_name">Affiliate Name</label></th>
                        <td><input type="text" name="affiliate_name" id="affiliate_name" class="regular-text" required/></td></tr>
                    <tr><th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input type="url" name="affiliate_url" id="affiliate_url" class="regular-text" pattern="https?://.+" required/></td></tr>
                    <tr><th><label for="affiliate_keywords">Target Keywords (comma separated)</label></th>
                        <td><input type="text" name="affiliate_keywords" id="affiliate_keywords" class="regular-text" placeholder="example, product, buy" /></td></tr>
                </table>
                <?php submit_button('Add Affiliate Link'); ?>
            </form>
            <h2>Existing Affiliate Links</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Name</th><th>URL</th><th>Keywords</th></tr></thead>
                <tbody>
                <?php foreach ( $links as $link ) : ?>
                    <tr>
                        <td><?php echo esc_html($link['name']); ?></td>
                        <td><a href="<?php echo esc_url($link['url']); ?>" target="_blank"><?php echo esc_url($link['url']); ?></a></td>
                        <td><?php echo esc_html(implode(', ', $link['keywords'])); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_link() {
        if ( ! current_user_can('manage_options') ) {
            wp_die('Unauthorized user');
        }

        check_admin_referer('save_affiliate_link_nonce');

        $name = sanitize_text_field($_POST['affiliate_name']);
        $url = esc_url_raw($_POST['affiliate_url']);
        $keywords = !empty($_POST['affiliate_keywords']) ? array_map('trim', explode(',', sanitize_text_field($_POST['affiliate_keywords']))) : array();

        $links = get_option($this->option_name, array());
        $links[] = array('name' => $name, 'url' => $url, 'keywords' => $keywords);
        update_option($this->option_name, $links);

        wp_redirect(admin_url('admin.php?page=affiliate-accelerator'));
        exit;
    }

    public function show_recommendations($atts) {
        $content = get_post_field('post_content', get_the_ID());
        $links = get_option($this->option_name, array());

        $found_links = array();
        foreach ($links as $link) {
            foreach ($link['keywords'] as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $found_links[] = $link;
                    break;
                }
            }
        }

        if (empty($found_links)) {
            return '<p>No affiliate recommendations available.</p>';
        }

        $html = '<ul class="affiliate-accelerator-list">';
        foreach ($found_links as $fl) {
            $html .= '<li><a href="#" class="affiliate-accelerator-link" data-url="'.esc_url($fl['url']).'" target="_blank" rel="noopener">Buy '.esc_html($fl['name']).'</a></li>';
        }
        $html .= '</ul>';
        return $html;
    }

    public function enqueue_scripts() {
        wp_enqueue_script('affiliate-accelerator-js', plugin_dir_url(__FILE__) . 'affiliate-accelerator.js', array('jquery'), '1.0', true);
        wp_localize_script('affiliate-accelerator-js', 'affiliateAccel', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('affiliate_accel_nonce'),
        ));
        wp_add_inline_script('affiliate-accelerator-js', "
            jQuery(document).on('click', '.affiliate-accelerator-link', function(e) {
                var url = jQuery(this).data('url');
                jQuery.post(affiliateAccel.ajax_url, {action: 'track_affiliate_click', url: url, _ajax_nonce: affiliateAccel.nonce});
            });
        ");
    }

    public function track_click() {
        check_ajax_referer('affiliate_accel_nonce');
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        if (empty($url)) {
            wp_send_json_error('Missing URL');
        }
        global $wpdb;
        $table = $wpdb->prefix . 'affiliate_accel_clicks';
        $wpdb->query($wpdb->prepare("INSERT INTO $table (url, click_time) VALUES (%s, NOW())", $url));
        wp_send_json_success('Click tracked');
    }

    public function create_db_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'affiliate_accel_clicks';
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            click_time datetime NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
}

$affiliate_accelerator = new AffiliateAccelerator();
register_activation_hook(__FILE__, array($affiliate_accelerator, 'create_db_table'));