<?php
/*
Plugin Name: Affiliate Deal Enhancer
Description: Automatically displays affiliate coupons, deals, and cashback offers matching your posts to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Enhancer.php
*/

if (!defined('ABSPATH')) exit;

class AffiliateDealEnhancer {
    public function __construct() {
        add_filter('the_content', array($this, 'inject_affiliate_deals'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function inject_affiliate_deals($content) {
        if (!is_single() || is_admin()) return $content;

        $post_id = get_the_ID();
        $keywords = $this->extract_keywords(get_post_field('post_title', $post_id) . ' ' . get_post_field('post_content', $post_id), 5);

        $deals_html = $this->get_deals_html($keywords);

        return $content . $deals_html;
    }

    private function extract_keywords($text, $limit = 5) {
        $text = strtolower(strip_tags($text));
        $words = str_word_count($text, 1);
        $stop_words = array('the','and','for','with','that','from','this','your','using','have','you','are','was','but','not','can');
        $filtered = array_diff($words, $stop_words);
        $freq = array_count_values($filtered);
        arsort($freq);
        return array_slice(array_keys($freq), 0, $limit);
    }

    private function get_deals_html($keywords) {
        $cached = get_transient('adeals_cached_deals_' . md5(json_encode($keywords)));
        if ($cached !== false) return $cached;

        $deals = [];

        // Simulate fetching deals from an external free API or static dataset (for demo here, we simulate)
        $static_deals = array(
            array('title' => '10% Off Sitewide at ExampleStore', 'link' => 'https://example.com/affiliate?deal=10off', 'keywords' => array('store','example','discount')),
            array('title' => 'Save $20 on Your First Purchase', 'link' => 'https://example.com/affiliate?deal=20usd', 'keywords' => array('purchase','money','save')),
            array('title' => '15% Cashback on Electronics', 'link' => 'https://example.com/affiliate?deal=cashback', 'keywords' => array('electronics','cashback','tech')),
            array('title' => 'Free Shipping on Orders Over $50', 'link' => 'https://example.com/affiliate?deal=freeship', 'keywords' => array('shipping','free','order'))
        );

        foreach ($static_deals as $deal) {
            if (count(array_intersect($keywords, $deal['keywords'])) > 0) {
                $deals[] = $deal;
            }
        }

        if (empty($deals)) return '';

        ob_start();
        echo '<div class="affiliate-deal-enhancer"><h3>Exclusive Deals & Coupons</h3><ul>';
        foreach ($deals as $d) {
            echo '<li><a href="' . esc_url($d['link']) . '" target="_blank" rel="nofollow noopener">' . esc_html($d['title']) . '</a></li>';
        }
        echo '</ul></div>';

        $html = ob_get_clean();

        set_transient('adeals_cached_deals_' . md5(json_encode($keywords)), $html, 12 * HOUR_IN_SECONDS);
        return $html;
    }

    public function add_admin_menu() {
        add_options_page('Affiliate Deal Enhancer', 'Affiliate Deal Enhancer', 'manage_options', 'affiliate-deal-enhancer', array($this, 'settings_page'));
    }

    public function register_settings() {
        register_setting('ade_settings_group', 'ade_enabled');
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Enhancer Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('ade_settings_group'); ?>
                <?php do_settings_sections('ade_settings_group'); ?>
                <table class="form-table">
                    <tr valign="top">
                    <th scope="row">Enable Plugin</th>
                    <td><input type="checkbox" name="ade_enabled" value="1" <?php checked(1, get_option('ade_enabled', 1)); ?> /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new AffiliateDealEnhancer();
