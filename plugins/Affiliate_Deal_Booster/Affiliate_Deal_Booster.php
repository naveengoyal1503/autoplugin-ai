<?php
/*
Plugin Name: Affiliate Deal Booster
Description: Auto-curates and displays affiliate coupons with tracking to boost affiliate earnings.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/

if (!defined('ABSPATH')) exit;

class Affiliate_Deal_Booster {

    private $options;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('affiliateDealBooster', 'affiliate_deal_booster_options');

        add_settings_section(
            'affiliate_deal_booster_section',
            __('Settings for fetching and displaying affiliate deals', 'affiliate-deal-booster'),
            null,
            'affiliateDealBooster'
        );

        add_settings_field(
            'affiliate_networks',
            __('Affiliate Network APIs (JSON URLs)', 'affiliate-deal-booster'),
            array($this, 'affiliate_networks_render'),
            'affiliateDealBooster',
            'affiliate_deal_booster_section'
        );
    }

    public function affiliate_networks_render() {
        $options = get_option('affiliate_deal_booster_options');
        ?>
        <textarea cols='60' rows='5' name='affiliate_deal_booster_options[affiliate_networks]'><?php echo isset($options['affiliate_networks']) ? esc_textarea($options['affiliate_networks']) : ''; ?></textarea>
        <p class='description'>Enter one JSON API URL per line providing coupons/deals data. Example format: [{"title":"10% Off","code":"SAVE10","url":"https://example.com"}, ...]</p>
        <?php
    }

    // Fetch and merge coupons from APIs
    private function fetch_coupons() {
        $options = get_option('affiliate_deal_booster_options');
        if(empty($options['affiliate_networks'])) return [];

        $urls = preg_split('/\r?\n/', $options['affiliate_networks']);
        $all_coupons = [];

        foreach($urls as $url) {
            $url = trim($url);
            if(empty($url)) continue;

            $response = wp_remote_get($url, ['timeout' => 5]);
            if (is_wp_error($response)) continue;

            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) continue;

            foreach ($data as $item) {
                if (isset($item['title'], $item['code'], $item['url'])) {
                    $all_coupons[] = [
                        'title' => sanitize_text_field($item['title']),
                        'code' => sanitize_text_field($item['code']),
                        'url' => esc_url_raw($item['url'])
                    ];
                }
            }
        }

        return $all_coupons;
    }

    public function display_deals_shortcode($atts) {
        $coupons = $this->fetch_coupons();
        if (empty($coupons)) return '<p>No affiliate deals available now.</p>';

        $output = '<div class="aff-deal-booster">';
        foreach ($coupons as $index => $coupon) {
            $title = esc_html($coupon['title']);
            $code = esc_html($coupon['code']);
            $url = esc_url($coupon['url']);

            // Track clicks via redirect (basic example)
            $redirect_url = add_query_arg([
                'affdb_redirect' => '1',
                'target' => urlencode($url),
                'coupon' => urlencode($code),
            ], site_url());

            $output .= "<div class='aff-deal-item' style='margin-bottom:10px;padding:10px;border:1px solid #ddd;'>";
            $output .= "<strong>$title</strong><br>";
            $output .= "<button class='copy-code' data-code='$code' style='cursor:pointer;margin:5px 0;padding:5px;'>Copy Code: $code</button><br>";
            $output .= "<a href='$redirect_url' target='_blank' rel='nofollow noopener'>Get Deal</a>";
            $output .= "</div>";
        }
        $output .= '</div>';

        $output .= "<script>document.addEventListener('click',function(e){if(e.target.classList.contains('copy-code')){var code=e.target.getAttribute('data-code');navigator.clipboard.writeText(code).then(()=>{alert('Copied code '+code);});}});</script>";

        return $output;
    }

    public function handle_redirect() {
        if (isset($_GET['affdb_redirect'], $_GET['target'])) {
            $target = esc_url_raw($_GET['target']);
            // Here you can implement click tracking logic, e.g. store in DB or external analytics
            // For simplicity redirect directly

            wp_redirect($target);
            exit;
        }
    }

    public function enqueue_scripts() {
        // No additional scripts for now (inline JS used)
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Deal Booster Settings</h2>
            <?php
            settings_fields('affiliateDealBooster');
            do_settings_sections('affiliateDealBooster');
            submit_button('Save Settings');
            ?>
            <h3>Usage</h3>
            <p>Place the shortcode <code>[affiliate_deals]</code> in any post or page to display the curated affiliate deals.</p>
        </form>
        <?php
    }
}

// Initialize
$affdb = new Affiliate_Deal_Booster();
add_action('init', array($affdb, 'handle_redirect'));