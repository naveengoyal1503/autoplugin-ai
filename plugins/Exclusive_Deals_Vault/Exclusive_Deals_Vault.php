/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Exclusive_Deals_Vault.php
*/
<?php
/**
 * Plugin Name: Exclusive Deals Vault
 * Plugin URI: https://example.com/deals-vault
 * Description: Create exclusive coupon and deal vaults to monetize your WordPress site with personalized discounts and affiliate links.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) exit;

class DealsVault {
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('deals_vault', array($this, 'deals_shortcode'));
        register_activation_hook(__FILE__, array($this, 'activate'));
    }

    public function init() {
        $this->create_post_type();
        flush_rewrite_rules();
    }

    public function create_post_type() {
        register_post_type('deal', array(
            'labels' => array('name' => 'Deals', 'singular_name' => 'Deal'),
            'public' => true,
            'has_archive' => true,
            'supports' => array('title', 'editor', 'thumbnail'),
            'menu_icon' => 'dashicons-cart',
            'rewrite' => array('slug' => 'deals'),
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('deals-vault-css', plugin_dir_url(__FILE__) . 'style.css', array(), '1.0.0');
        wp_enqueue_script('deals-vault-js', plugin_dir_url(__FILE__) . 'script.js', array('jquery'), '1.0.0', true);
    }

    public function admin_menu() {
        add_submenu_page('edit.php?post_type=deal', 'Deals Settings', 'Settings', 'manage_options', 'deals-settings', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (isset($_POST['save'])) {
            update_option('deals_vault_settings', $_POST['settings']);
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        $settings = get_option('deals_vault_settings', array('cta_text' => 'Grab Exclusive Deal!', 'affiliate_tracking' => 'yes'));
        ?>
        <div class="wrap">
            <h1>Deals Vault Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>CTA Button Text</th>
                        <td><input type="text" name="settings[cta_text]" value="<?php echo esc_attr($settings['cta_text']); ?>" /></td>
                    </tr>
                    <tr>
                        <th>Affiliate Tracking</th>
                        <td><input type="checkbox" name="settings[affiliate_tracking]" <?php checked($settings['affiliate_tracking'], 'yes'); ?> value="yes" /></td>
                    </tr>
                </table>
                <p><input type="submit" name="save" class="button-primary" value="Save Settings" /></p>
            </form>
        </div>
        <?php
    }

    public function deals_shortcode($atts) {
        $atts = shortcode_atts(array('limit' => 5), $atts);
        $settings = get_option('deals_vault_settings', array());
        $deals = get_posts(array(
            'post_type' => 'deal',
            'posts_per_page' => $atts['limit'],
            'post_status' => 'publish',
        ));
        ob_start();
        ?>
        <div class="deals-vault">
            <h3>Exclusive Deals Vault</h3>
            <?php foreach ($deals as $deal): 
                $aff_link = get_post_meta($deal->ID, 'affiliate_link', true);
                $code = get_post_meta($deal->ID, 'coupon_code', true);
            ?>
            <div class="deal-item">
                <h4><?php echo $deal->post_title; ?></h4>
                <div><?php echo $deal->post_content; ?></div>
                <?php if ($code): ?><strong>Coupon: <?php echo $code; ?></strong><?php endif; ?>
                <?php if ($aff_link): ?>
                    <a href="<?php echo esc_url($aff_link); ?>" class="deal-button" target="_blank"><?php echo $settings['cta_text'] ?? 'Grab Deal!'; ?></a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <style>
        .deals-vault { max-width: 800px; margin: 20px 0; }
        .deal-item { border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .deal-button { background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; }
        .deal-button:hover { background: #005a87; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function activate() {
        $this->create_post_type();
        flush_rewrite_rules();
    }
}

new DealsVault();

// Add meta boxes
function deals_add_meta_boxes() {
    add_meta_box('deal_details', 'Deal Details', 'deal_meta_box_callback', 'deal', 'normal', 'high');
}
add_action('add_meta_boxes', 'deals_add_meta_boxes');

function deal_meta_box_callback($post) {
    wp_nonce_field('deal_meta_nonce', 'deal_nonce');
    $link = get_post_meta($post->ID, 'affiliate_link', true);
    $code = get_post_meta($post->ID, 'coupon_code', true);
    echo '<p><label>Affiliate Link: <input type="url" name="affiliate_link" value="' . esc_attr($link) . '" style="width:100%;" /></label></p>';
    echo '<p><label>Coupon Code: <input type="text" name="coupon_code" value="' . esc_attr($code) . '" style="width:100%;" /></label></p>';
}

function deals_save_meta($post_id) {
    if (!isset($_POST['deal_nonce']) || !wp_verify_nonce($_POST['deal_nonce'], 'deal_meta_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    update_post_meta($post_id, 'affiliate_link', sanitize_url($_POST['affiliate_link'] ?? ''));
    update_post_meta($post_id, 'coupon_code', sanitize_text_field($_POST['coupon_code'] ?? ''));
}
add_action('save_post', 'deals_save_meta');