<?php
/*
Plugin Name: Affiliate Deal & Coupon Aggregator
Description: Aggregates and displays affiliate deals and coupons to boost affiliate earnings.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal___Coupon_Aggregator.php
*/

if (!defined('ABSPATH')) {
    exit;
}

class Affiliate_Deal_Coupon_Aggregator {
    private $option_name = 'adca_settings';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('affiliate_deals', array($this, 'display_deals_shortcode'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_adca_fetch_deals', array($this, 'ajax_fetch_deals'));
        add_action('wp_ajax_nopriv_adca_fetch_deals', array($this, 'ajax_fetch_deals'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Deals',
            'Affiliate Deals',
            'manage_options',
            'adca-settings',
            array($this, 'settings_page'),
            'dashicons-cart',
            60
        );
    }

    public function register_settings() {
        register_setting($this->option_name, $this->option_name);

        add_settings_section(
            'adca_api_section',
            'Affiliate API Settings',
            null,
            'adca-settings'
        );

        add_settings_field(
            'amazon_tag',
            'Amazon Associates Tag',
            array($this, 'amazon_tag_callback'),
            'adca-settings',
            'adca_api_section'
        );

        add_settings_field(
            'coupon_source_url',
            'Coupon Source URL (JSON feed)',
            array($this, 'coupon_source_callback'),
            'adca-settings',
            'adca_api_section'
        );
    }

    public function amazon_tag_callback() {
        $options = get_option($this->option_name);
        $value = isset($options['amazon_tag']) ? esc_attr($options['amazon_tag']) : '';
        echo "<input type='text' name='{$this->option_name}[amazon_tag]' value='{$value}' size='50' placeholder='yourtag-20' />";
    }

    public function coupon_source_callback() {
        $options = get_option($this->option_name);
        $value = isset($options['coupon_source_url']) ? esc_url($options['coupon_source_url']) : '';
        echo "<input type='url' name='{$this->option_name}[coupon_source_url]' value='{$value}' size='70' placeholder='https://example.com/coupons.json' />";
        echo '<p class="description">Provide a JSON feed URL with coupon data containing title, link, and expiry.</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal & Coupon Aggregator Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields($this->option_name);
                do_settings_sections('adca-settings');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Use the shortcode <code>[affiliate_deals]</code> to display the latest deals and coupons on any page or post.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('adca-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('adca-ajax', plugin_dir_url(__FILE__) . 'adca.js', array('jquery'), false, true);
        wp_localize_script('adca-ajax', 'adca_ajax_obj', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function display_deals_shortcode($atts) {
        $atts = shortcode_atts(array('count' => 5), $atts, 'affiliate_deals');
        ob_start();
        ?>
        <div id="adca-deals-container">
            <p>Loading deals...</p>
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function($){
            $.post(adca_ajax_obj.ajax_url, { action: 'adca_fetch_deals', count: <?php echo intval($atts['count']); ?> }, function(response){
                if(response.success) {
                    var html = '<ul class="adca-deals-list">';
                    $.each(response.data, function(i, deal){
                        html += '<li><a href="' + deal.link + '" target="_blank" rel="nofollow noopener">' + deal.title + '</a>';
                        if(deal.expiry) {
                            html += ' <small>(Expires: ' + deal.expiry + ')</small>';
                        }
                        html += '</li>';
                    });
                    html += '</ul>';
                    $('#adca-deals-container').html(html);
                } else {
                    $('#adca-deals-container').html('<p>No deals found.</p>');
                }
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_fetch_deals() {
        $options = get_option($this->option_name);
        $count = isset($_POST['count']) ? intval($_POST['count']) : 5;
        $deals = array();

        // Sample Amazon Affiliate link deal (static for demo)
        if (!empty($options['amazon_tag'])) {
            $deals[] = array(
                'title' => 'Amazon Special Deal Example',
                'link' => 'https://www.amazon.com/dp/B079QHML21?tag=' . urlencode($options['amazon_tag']),
                'expiry' => ''
            );
        }

        // Fetch coupons from external JSON feed if URL provided
        if (!empty($options['coupon_source_url'])) {
            $response = wp_remote_get($options['coupon_source_url']);
            if (!is_wp_error($response)) {
                $body = wp_remote_retrieve_body($response);
                $json = json_decode($body, true);
                if (is_array($json)) {
                    foreach ($json as $item) {
                        if (count($deals) >= $count) break;
                        if (!empty($item['title']) && !empty($item['link'])) {
                            $deal = array(
                                'title' => sanitize_text_field($item['title']),
                                'link' => esc_url($item['link']),
                                'expiry' => isset($item['expiry']) ? sanitize_text_field($item['expiry']) : ''
                            );
                            $deals[] = $deal;
                        }
                    }
                }
            }
        }

        // Return up to requested count
        $deals = array_slice($deals, 0, $count);

        if (!empty($deals)) {
            wp_send_json_success($deals);
        } else {
            wp_send_json_error('No deals found');
        }
    }
}

new Affiliate_Deal_Coupon_Aggregator();
