/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Coupon___Deal_Aggregator.php
*/
<?php
/**
 * Plugin Name: Affiliate Coupon & Deal Aggregator
 * Plugin URI: https://example.com/plugins/affiliate-coupon-deal-aggregator
 * Description: Aggregates coupon/deal feeds with affiliate link integration to monetize via affiliate commissions.
 * Version: 1.0
 * Author: Generated
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class AffiliateCouponDealAggregator {
    private $option_name = 'acda_coupon_feeds';
    private $affiliate_id_option = 'acda_affiliate_id';

    public function __construct() {
        add_action('admin_menu',array($this,'add_admin_menu'));
        add_action('admin_init',array($this,'settings_init'));
        add_shortcode('acda_coupons',array($this,'render_coupons_shortcode'));
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Coupons','Affiliate Coupons','manage_options','acda_affiliate_coupons',array($this,'options_page'),'dashicons-tickets-alt',26);
    }

    public function settings_init() {
        register_setting('acda_settings','acda_coupon_feeds');
        register_setting('acda_settings','acda_affiliate_id');

        add_settings_section('acda_section','Coupon Feeds Settings',null,'acda_settings');

        add_settings_field(
            'acda_coupon_feeds',
            'Coupon Feed URLs (one per line)',
            array($this,'coupon_feeds_render'),
            'acda_settings',
            'acda_section'
        );

        add_settings_field(
            'acda_affiliate_id',
            'Affiliate ID / Tracking Param',
            array($this,'affiliate_id_render'),
            'acda_settings',
            'acda_section'
        );
    }

    public function coupon_feeds_render() {
        $value = get_option('acda_coupon_feeds','');
        echo '<textarea cols="50" rows="8" name="acda_coupon_feeds">'.esc_textarea($value).'</textarea>';
        echo '<p class="description">Enter one or more RSS feed URLs for coupon/deal feeds.</p>';
    }

    public function affiliate_id_render() {
        $value = get_option('acda_affiliate_id','');
        echo '<input type="text" name="acda_affiliate_id" value="'.esc_attr($value).'" />';
        echo '<p class="description">Your affiliate tracking parameter to append to links, e.g., ?affid=1234</p>';
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Coupon & Deal Aggregator Settings</h1>
            <form action="options.php" method="post">
                <?php
                settings_fields('acda_settings');
                do_settings_sections('acda_settings');
                submit_button();
                ?>
            </form>
            <h2>Usage</h2>
            <p>Place the shortcode <code>[acda_coupons]</code> in any post or page to display aggregated coupons with affiliate links.</p>
        </div>
        <?php
    }

    public function render_coupons_shortcode() {
        $feeds_text = get_option('acda_coupon_feeds','');
        if (empty($feeds_text)) {
            return '<p>No coupon feeds configured.</p>';
        }

        $feeds = array_filter(array_map('trim',explode("\n",$feeds_text)));
        if (empty($feeds)) {
            return '<p>No valid coupon feeds configured.</p>';
        }

        $affiliate_id = get_option('acda_affiliate_id','');

        // Collect coupons from all feeds
        $coupons = array();

        foreach ($feeds as $feed_url) {
            $feed_data = $this->fetch_feed_data($feed_url);
            if ($feed_data) {
                foreach ($feed_data as $item) {
                    $coupons[] = $item;
                    if(count($coupons) >= 20) break; // limit
                }
            }
            if(count($coupons) >= 20) break;
        }

        if (empty($coupons)) {
            return '<p>No coupons found in feeds.</p>';
        }

        // Render coupon list
        $output = '<div class="acda-coupon-list" style="font-family:Arial,sans-serif;">';
        foreach ($coupons as $c) {
            $title = esc_html($c['title']);
            $desc = esc_html($c['description']);
            $link = esc_url($c['link']);
            // Append affiliate id if set
            if ($affiliate_id) {
                $separator = (strpos($link,'?') === false) ? '?' : '&';
                $link .= $separator . urlencode($affiliate_id);
            }
            $output .= '<div style="border:1px solid #ddd; margin:10px 0; padding:10px; border-radius:5px;">';
            $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener" style="font-weight:bold; font-size:16px; color:#0073aa;">' . $title . '</a>';
            if ($desc) {
                $output .= '<p style="margin:5px 0;">' . $desc . '</p>';
            }
            $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener" style="display:inline-block; background:#28a745; color:#fff; padding:5px 10px; text-decoration:none; border-radius:3px; font-weight:bold;">Get Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    }

    private function fetch_feed_data($feed_url) {
        $rss = @simplexml_load_file($feed_url);
        if (!$rss) return false;
        $items = [];
        foreach ($rss->channel->item as $item) {
            $title = (string)$item->title;
            $link = (string)$item->link;
            $description = (string)$item->description;
            $items[] = [
                'title' => $title,
                'link' => $link,
                'description' => $this->strip_tags_custom($description)
            ];
            if(count($items) >= 10) break;
        }
        return $items;
    }

    private function strip_tags_custom($text) {
        // Basic cleanup removing html entities and tags for safe display
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $text;
    }
}

new AffiliateCouponDealAggregator();
