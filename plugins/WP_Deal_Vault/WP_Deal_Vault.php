/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Deal_Vault.php
*/
<?php
/**
 * Plugin Name: WP Deal Vault
 * Description: Aggregates and displays affiliate coupons and flash deals with automated updates and scheduled deal expirations.
 * Version: 1.0
 * Author: Plugin Generator
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Deal_Vault {

    private $option_name = 'wp_deal_vault_deals';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('wp_deal_vault', array($this, 'display_deals_shortcode'));

        add_action('wp_deal_vault_expire_deals_cron', array($this, 'expire_deals'));

        register_activation_hook(__FILE__, array($this, 'plugin_activate'));
        register_deactivation_hook(__FILE__, array($this, 'plugin_deactivate'));
    }

    public function plugin_activate() {
        if (!wp_next_scheduled('wp_deal_vault_expire_deals_cron')) {
            wp_schedule_event(time(), 'hourly', 'wp_deal_vault_expire_deals_cron');
        }
    }

    public function plugin_deactivate() {
        wp_clear_scheduled_hook('wp_deal_vault_expire_deals_cron');
    }

    public function add_admin_menu() {
        add_menu_page('WP Deal Vault', 'WP Deal Vault', 'manage_options', 'wp_deal_vault', array($this, 'options_page'), 'dashicons-tag', 76);
    }

    public function settings_init() {
        register_setting('wpDealVault', $this->option_name);

        add_settings_section(
            'wp_deal_vault_section',
            __('Manage Deals', 'wp_deal_vault'),
            null,
            'wpDealVault'
        );

        add_settings_field(
            'wp_deal_vault_deals_field',
            __('Coupons / Deals', 'wp_deal_vault'),
            array($this, 'deals_field_render'),
            'wpDealVault',
            'wp_deal_vault_section'
        );
    }

    public function deals_field_render() {
        $deals = get_option($this->option_name, array());
        ?>
        <textarea style="width:100%;height:250px;" name="<?php echo esc_attr($this->option_name); ?>"><?php echo esc_textarea(json_encode($deals)); ?></textarea>
        <p class="description">Enter deals in JSON format. Each deal: {"title": "Deal Name", "link": "https://affiliate.link", "description": "Short desc", "expiry": "YYYY-MM-DD"}</p>
        <?php
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>

            <h2>WP Deal Vault - Deals & Coupons</h2>

            <?php
            settings_fields('wpDealVault');
            do_settings_sections('wpDealVault');
            submit_button();
            ?>

        </form>
        <?php
    }

    public function display_deals_shortcode($atts) {
        $deals = get_option($this->option_name, array());
        if (empty($deals)) {
            return '<p>No deals available at the moment. Please check back soon.</p>';
        }
        $output = '<div class="wp-deal-vault">';
        $today = current_time('Y-m-d');

        foreach ($deals as $deal) {
            if (empty($deal['expiry']) || $deal['expiry'] >= $today) {
                $title = esc_html($deal['title']);
                $link = esc_url($deal['link']);
                $desc = esc_html($deal['description']);
                $expiry = isset($deal['expiry']) ? esc_html($deal['expiry']) : 'No expiry';

                $output .= '<div class="wp-deal-item" style="border:1px solid #ddd;padding:10px;margin:10px 0;">';
                $output .= "<h3><a href='{$link}' target='_blank' rel='nofollow noopener'>{$title}</a></h3>";
                $output .= "<p>{$desc}</p>";
                $output .= "<small>Expires: {$expiry}</small>";
                $output .= '</div>';
            }
        }
        $output .= '</div>';
        return $output;
    }

    public function expire_deals() {
        $deals = get_option($this->option_name, array());
        if (empty($deals)) {
            return;
        }
        $today = current_time('Y-m-d');
        $changed = false;

        foreach ($deals as $key => $deal) {
            if (!empty($deal['expiry']) && $deal['expiry'] < $today) {
                unset($deals[$key]);
                $changed = true;
            }
        }
        if ($changed) {
            update_option($this->option_name, array_values($deals));
        }
    }

}

new WP_Deal_Vault();
