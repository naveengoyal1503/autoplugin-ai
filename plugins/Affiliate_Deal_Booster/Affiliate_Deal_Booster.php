/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Aggregates and displays affiliate coupons and deals dynamically to boost conversions.
 * Version: 1.0
 * Author: YourName
 * Text Domain: affiliate-deal-booster
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $option_name = 'adb_deals_cache';
    private $nonce_action = 'adb_save_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'deals_shortcode'));
        add_action('wp_ajax_adb_refresh_deals', array($this, 'ajax_refresh_deals'));
        add_action('wp_ajax_nopriv_adb_refresh_deals', array($this, 'ajax_refresh_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'admin_page'), 'dashicons-megaphone', 60);
    }

    public function settings_init() {
        register_setting('adb_settings', 'adb_settings', array($this, 'validate_settings'));

        add_settings_section('adb_section_main', 'Settings', null, 'affiliate_deal_booster');

        add_settings_field(
            'adb_field_affiliate_sources',
            'Affiliate Sources (JSON URLs, one per line)',
            array($this, 'field_affiliate_sources_render'),
            'affiliate_deal_booster',
            'adb_section_main'
        );

        add_settings_field(
            'adb_field_cache_duration',
            'Cache Duration (minutes)',
            array($this, 'field_cache_duration_render'),
            'affiliate_deal_booster',
            'adb_section_main'
        );
    }

    public function field_affiliate_sources_render() {
        $options = get_option('adb_settings');
        $sources = isset($options['affiliate_sources']) ? $options['affiliate_sources'] : '';
        echo "<textarea style='width:100%;height:100px;' name='adb_settings[affiliate_sources]'>{$sources}</textarea>";
        echo "<p class='description'>Enter URLs that return JSON arrays of deals. One URL per line.</p>";
    }

    public function field_cache_duration_render() {
        $options = get_option('adb_settings');
        $duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
        echo "<input type='number' min='1' max='1440' name='adb_settings[cache_duration]' value='{$duration}' />";
        echo "<p class='description'>How long to cache deals before refreshing (in minutes).</p>";
    }

    public function validate_settings($input) {
        $validated = array();
        if (!empty($input['affiliate_sources'])) {
            $lines = explode("\n", $input['affiliate_sources']);
            $urls = array();
            foreach ($lines as $line) {
                $line = trim($line);
                if (filter_var($line, FILTER_VALIDATE_URL)) {
                    $urls[] = $line;
                }
            }
            $validated['affiliate_sources'] = implode("\n", $urls);
        }
        $validated['cache_duration'] = max(1, intval($input['cache_duration']));
        return $validated;
    }

    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('adb_settings');
                do_settings_sections('affiliate_deal_booster');
                submit_button();
                ?>
            </form>
            <hr>
            <h2>Current Cached Deals</h2>
            <div id="adb-deals-display">
                <?php echo $this->render_deal_list(); ?>
            </div>
            <button id="adb-refresh-btn" class="button">Refresh Deals Now</button>
            <div id="adb-refresh-msg" style="margin-top:10px;color:green;display:none;">Deals refreshed!</div>
        </div>
        <script>
            document.getElementById('adb-refresh-btn').addEventListener('click', function() {
                var btn = this;
                btn.disabled = true;
                var msg = document.getElementById('adb-refresh-msg');
                msg.style.display = 'none';

                fetch(ajaxurl + '?action=adb_refresh_deals&_wpnonce=' + '<?php echo wp_create_nonce('adb_refresh_nonce'); ?>')
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        document.getElementById('adb-deals-display').innerHTML = data.html;
                        msg.style.display = 'block';
                    }
                    btn.disabled = false;
                });
            });
        </script>
        <?php
    }

    // Fetch deals from all affiliate sources configured
    private function fetch_deals() {
        $options = get_option('adb_settings');
        $sources = isset($options['affiliate_sources']) ? explode("\n", $options['affiliate_sources']) : array();
        $all_deals = array();
        foreach ($sources as $url) {
            $url = trim($url);
            if (!$url) continue;
            $response = wp_remote_get($url, array('timeout' => 5));
            if (is_wp_error($response)) continue;
            $code = wp_remote_retrieve_response_code($response);
            if ($code !== 200) continue;
            $body = wp_remote_retrieve_body($response);
            $deals = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE) continue;
            if (is_array($deals)) {
                foreach ($deals as $deal) {
                    if (isset($deal['title'], $deal['url'], $deal['description'])) {
                        $all_deals[] = $deal;
                    }
                }
            }
        }
        return $all_deals;
    }

    // Cache deals transient
    private function cache_deals() {
        $deals = $this->fetch_deals();
        $options = get_option('adb_settings');
        $cache_duration = isset($options['cache_duration']) ? intval($options['cache_duration']) : 60;
        set_transient($this->option_name, $deals, $cache_duration * 60);
        return $deals;
    }

    private function get_cached_deals() {
        $deals = get_transient($this->option_name);
        if ($deals === false) {
            $deals = $this->cache_deals();
        }
        return $deals;
    }

    public function render_deal_list() {
        $deals = $this->get_cached_deals();
        if (empty($deals)) return '<p>No deals found.</p>';
        $html = '<ul class="adb-deal-list" style="list-style:none;padding-left:0;">
';
        foreach ($deals as $deal) {
            $title = esc_html($deal['title']);
            $url = esc_url($deal['url']);
            $desc = esc_html($deal['description']);
            $html .= "<li style='margin-bottom:15px; padding:10px; border:1px solid #ddd; border-radius:5px;'>";
            $html .= "<a href='{$url}' target='_blank' rel='nofollow noopener' style='font-weight:bold; font-size:1.1em;'>{$title}</a><br>";
            $html .= "<span style='color:#555;'>{$desc}</span>";
            $html .= "</li>\n";
        }
        $html .= '</ul>';
        return $html;
    }

    public function deals_shortcode() {
        return $this->render_deal_list();
    }

    public function ajax_refresh_deals() {
        check_ajax_referer('adb_refresh_nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
        $deals = $this->cache_deals();
        wp_send_json_success(array('html' => $this->render_deal_list()));
        wp_die();
    }

    public function enqueue_assets() {
        if (is_admin()) return;
        wp_enqueue_style('adb-style', plugin_dir_url(__FILE__) . 'adb-style.css');
    }
}

new AffiliateDealBooster();
