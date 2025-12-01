<?php
/*
Plugin Name: Affiliate Campaign Booster
Plugin URI: https://example.com/affiliate-campaign-booster
Description: Automatically create and manage personalized, geo-targeted affiliate campaigns with scheduling and analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Campaign_Booster.php
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class AffiliateCampaignBooster {
    private $option_name = 'acb_affiliate_links';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('acb_affiliate_link', [$this, 'shortcode_affiliate_link']);
        add_action('wp_footer', [$this, 'embed_campaign_script']);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Campaign Booster', 'Affiliate Booster', 'manage_options', 'affiliate-campaign-booster', [$this, 'settings_page']);
    }

    public function register_settings() {
        register_setting('acb_settings_group', $this->option_name);
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) return;
        $links = get_option($this->option_name, []);
        ?>
        <div class="wrap">
            <h1>Affiliate Campaign Booster Settings</h1>
            <form method="post" action="options.php">
            <?php settings_fields('acb_settings_group'); ?>
            <?php do_settings_sections('acb_settings_group'); ?>
            <table class="form-table" id="acb-links-table">
                <thead><tr><th>Campaign Name</th><th>Affiliate URL</th><th>Geo Target (Country code)</th><th>Schedule (YYYY-MM-DD HH:MM)</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php if (!empty($links)):
                        foreach ($links as $index => $link): ?>
                        <tr>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][name]" value="<?php echo esc_attr($link['name']); ?>" required></td>
                            <td><input type="url" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][url]" value="<?php echo esc_url($link['url']); ?>" required></td>
                            <td><input type="text" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][geo]" value="<?php echo esc_attr($link['geo']); ?>"></td>
                            <td><input type="datetime-local" name="<?php echo $this->option_name; ?>[<?php echo $index; ?>][schedule]" value="<?php echo esc_attr($link['schedule']); ?>"></td>
                            <td><button class="button acb-remove-row">Remove</button></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="5">No campaigns yet. Add one below.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><button id="acb-add-row" class="button">Add Campaign</button></p>
            <?php submit_button(); ?>
            </form>
        </div>
        <script>
        (function(){
            const table = document.getElementById('acb-links-table').querySelector('tbody');
            const addBtn = document.getElementById('acb-add-row');
            addBtn.addEventListener('click', function(e){
                e.preventDefault();
                const numRows = table.children.length;
                const newRow = document.createElement('tr');
                newRow.innerHTML = `
                    <td><input type="text" name="<?php echo $this->option_name; ?>[${numRows}][name]" required></td>
                    <td><input type="url" name="<?php echo $this->option_name; ?>[${numRows}][url]" required></td>
                    <td><input type="text" name="<?php echo $this->option_name; ?>[${numRows}][geo]"></td>
                    <td><input type="datetime-local" name="<?php echo $this->option_name; ?>[${numRows}][schedule]"></td>
                    <td><button class="button acb-remove-row">Remove</button></td>
                `;
                table.appendChild(newRow);
            });

            table.addEventListener('click', function(e){
                if(e.target.classList.contains('acb-remove-row')){
                    e.preventDefault();
                    e.target.closest('tr').remove();
                }
            });
        })();
        </script>
        <?php
    }

    public function shortcode_affiliate_link($atts) {
        $atts = shortcode_atts(['campaign' => ''], $atts, 'acb_affiliate_link');
        $name = sanitize_text_field($atts['campaign']);
        if (!$name) return '';
        $links = get_option($this->option_name, []);

        foreach ($links as $link) {
            if ($link['name'] === $name && $this->is_campaign_active($link)) {
                // Check geo targeting
                if ($link['geo']) {
                    $user_country = $this->get_user_country_code();
                    if (strcasecmp($user_country, $link['geo']) !== 0) {
                        return '';// Geo mismatch hides link
                    }
                }
                $url_escaped = esc_url($link['url']);
                return "<a href='" . $url_escaped . "' target='_blank' rel='nofollow noopener'>Affiliate Link</a>";
            }
        }
        return '';// No active campaign found
    }

    private function is_campaign_active($link) {
        if (empty($link['schedule'])) return true; // No schedule means always active
        $timestamp = strtotime($link['schedule']);
        return ($timestamp !== false && time() >= $timestamp);
    }

    private function get_user_country_code() {
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            return strtoupper(sanitize_text_field($_SERVER['HTTP_CF_IPCOUNTRY'])); // Cloudflare header
        }
        // Fallback: Use IP Geolocation API (free tier, simple implementation) - For demonstration only
        $ip = $_SERVER['REMOTE_ADDR'];
        if(filter_var($ip, FILTER_VALIDATE_IP) === false) return '';
        $response = wp_remote_get('https://ipapi.co/' . $ip . '/country/');
        if (is_wp_error($response)) return '';
        $code = wp_remote_retrieve_body($response);
        return strtoupper(trim($code));
    }

    public function embed_campaign_script() {
        // Minimal placeholder for possible future client-side tracking or targeting
        echo "<script>console.log('Affiliate Campaign Booster active');</script>";
    }
}

new AffiliateCampaignBooster();
