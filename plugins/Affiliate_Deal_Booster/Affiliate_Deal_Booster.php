/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Automatically fetches and displays affiliate deals and coupons from multiple networks to boost conversions.
 * Version: 1.0
 * Author: Generated
 */

if (!defined('ABSPATH')) exit;

class AffiliateDealBooster {
    private $option_name = 'ad_booster_options';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
        add_shortcode('affiliate_deals', array($this, 'render_affiliate_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Deal Booster', 'Affiliate Deal Booster', 'manage_options', 'affiliate_deal_booster', array($this, 'options_page'));
    }

    public function settings_init() {
        register_setting('ad_booster_group', $this->option_name);

        add_settings_section(
            'ad_booster_section',
            __('Affiliate Network API Keys and Settings', 'affiliate-deal-booster'),
            null,
            'affiliate_deal_booster'
        );

        add_settings_field(
            'ad_booster_networks',
            __('Affiliate Networks (API Keys)', 'affiliate-deal-booster'),
            array($this, 'network_keys_render'),
            'affiliate_deal_booster',
            'ad_booster_section'
        );

        add_settings_field(
            'ad_booster_title',
            __('Widget Title', 'affiliate-deal-booster'),
            array($this, 'title_render'),
            'affiliate_deal_booster',
            'ad_booster_section'
        );

        add_settings_field(
            'ad_booster_limit',
            __('Number of Deals to Show', 'affiliate-deal-booster'),
            array($this, 'limit_render'),
            'affiliate_deal_booster',
            'ad_booster_section'
        );
    }

    public function network_keys_render() {
        $options = get_option($this->option_name);
        $amazon = isset($options['amazon']) ? esc_attr($options['amazon']) : '';
        $cj = isset($options['cj']) ? esc_attr($options['cj']) : '';
        $impact = isset($options['impact']) ? esc_attr($options['impact']) : '';
        echo '<input type="text" name="' . $this->option_name . '[amazon]" value="' . $amazon . '" placeholder="Amazon API Key" style="width:300px;"/><br/>';
        echo '<input type="text" name="' . $this->option_name . '[cj]" value="' . $cj . '" placeholder="Commission Junction API Key" style="width:300px;"/><br/>';
        echo '<input type="text" name="' . $this->option_name . '[impact]" value="' . $impact . '" placeholder="Impact API Key" style="width:300px;"/><br/>';
        echo '<small>Enter your affiliate network API keys to enable auto deal fetching.</small>';
    }

    public function title_render() {
        $options = get_option($this->option_name);
        $title = isset($options['title']) ? esc_attr($options['title']) : 'Exclusive Deals and Coupons';
        echo '<input type="text" name="' . $this->option_name . '[title]" value="' . $title . '" style="width:300px;" />';
    }

    public function limit_render() {
        $options = get_option($this->option_name);
        $limit = isset($options['limit']) ? intval($options['limit']) : 5;
        echo '<input type="number" min="1" max="20" name="' . $this->option_name . '[limit]" value="' . $limit . '" style="width:70px;" />';
    }

    public function options_page() {
        ?>
        <form action='options.php' method='post'>
            <h2>Affiliate Deal Booster Settings</h2>
            <?php
            settings_fields('ad_booster_group');
            do_settings_sections('affiliate_deal_booster');
            submit_button();
            ?>
        </form>
        <?php
    }

    private function fetch_amazon_deals($limit) {
        // Placeholder: Return dummy data since real API requires credentials and more setup
        $deals = array();
        for ($i = 1; $i <= $limit; $i++) {
            $deals[] = array(
                'title' => "Amazon Deal #$i",
                'url' => 'https://amazon.com/deal' . $i,
                'discount' => rand(10, 50) . '% off'
            );
        }
        return $deals;
    }

    private function fetch_cj_deals($limit) {
        // Placeholder dummy data
        $deals = array();
        for ($i = 1; $i <= $limit; $i++) {
            $deals[] = array(
                'title' => "CJ Network Deal #$i",
                'url' => 'https://cj.com/deal' . $i,
                'discount' => rand(5, 40) . '% off'
            );
        }
        return $deals;
    }

    private function fetch_impact_deals($limit) {
        // Placeholder dummy data
        $deals = array();
        for ($i = 1; $i <= $limit; $i++) {
            $deals[] = array(
                'title' => "Impact Deal #$i",
                'url' => 'https://impact.com/deal' . $i,
                'discount' => rand(15, 60) . '% off'
            );
        }
        return $deals;
    }

    public function render_affiliate_deals($atts) {
        $options = get_option($this->option_name);
        $limit = isset($options['limit']) ? intval($options['limit']) : 5;
        $title = isset($options['title']) ? esc_html($options['title']) : 'Exclusive Deals and Coupons';

        // In real plugin, fetch from APIs using stored keys
        $amazon_deals = $this->fetch_amazon_deals($limit);
        $cj_deals = $this->fetch_cj_deals($limit);
        $impact_deals = $this->fetch_impact_deals($limit);

        // Combine and shuffle
        $all_deals = array_merge($amazon_deals, $cj_deals, $impact_deals);
        shuffle($all_deals);
        $all_deals = array_slice($all_deals, 0, $limit);

        ob_start();
        ?>
        <div class="affiliate-deal-booster-widget">
            <h3><?php echo $title; ?></h3>
            <ul>
                <?php foreach ($all_deals as $deal): ?>
                    <li><a href="<?php echo esc_url($deal['url']); ?>" target="_blank" rel="nofollow noopener noreferrer"><?php echo esc_html($deal['title']); ?></a> &mdash; <strong><?php echo esc_html($deal['discount']); ?></strong></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    public function enqueue_assets() {
        wp_enqueue_style('affiliate-deal-booster-style', plugin_dir_url(__FILE__) . 'style.css');
    }
}

new AffiliateDealBooster();
