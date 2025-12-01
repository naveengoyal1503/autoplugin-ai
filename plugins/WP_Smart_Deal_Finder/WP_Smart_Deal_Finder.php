<?php
/*
Plugin Name: WP Smart Deal Finder
Plugin URI: https://example.com/wp-smart-deal-finder
Description: Aggregates and displays personalized coupon codes and discounts based on user behavior and site content to boost affiliate revenue.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Deal_Finder.php
License: GPL2
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WPSmartDealFinder {
    public function __construct() {
        add_shortcode('smart_deals', array($this, 'render_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'insert_script'));
        add_action('wp_ajax_get_user_interest', array($this, 'ajax_get_user_interest'));
        add_action('wp_ajax_nopriv_get_user_interest', array($this, 'ajax_get_user_interest'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpsdf-style', plugin_dir_url(__FILE__) . 'style.css');
        wp_enqueue_script('wpsdf-script', plugin_dir_url(__FILE__) . 'wpsdf.js', array('jquery'), '1.0', true);
        wp_localize_script('wpsdf-script', 'wpsdf_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function insert_script() {
        ?>
        <script>
        // Use localStorage to track user interests with simple keywords
        (function($) {
            $(document).ready(function() {
                var interests = localStorage.getItem('wpsdf_user_interests');
                if(!interests) {
                    // Get keywords from post tags or categories
                    var keywords = [];
                    $('.post .entry-content').each(function() {
                        var text = $(this).text();
                        keywords.push(text);
                    });
                    keywords = keywords.join(' ').toLowerCase();
                    // Send AJAX to store interests
                    $.post(wpsdf_ajax.ajax_url, {action: 'get_user_interest', keywords: keywords}, function(response) {
                        localStorage.setItem('wpsdf_user_interests', JSON.stringify(response));
                    });
                }
            });
        })(jQuery);
        </script>
        <?php
    }

    public function ajax_get_user_interest() {
        $keywords = sanitize_text_field($_POST['keywords'] ?? '');
        $interests = $this->extract_keywords($keywords);
        wp_send_json($interests);
    }

    private function extract_keywords($text) {
        // Very simple extraction: most common words excluding stopwords
        $stopwords = array('the','and','or','to','a','of','in','for','on','with','as','by');
        $words = array_filter(explode(' ', strtolower($text)));
        $filtered = array_diff($words, $stopwords);
        $counts = array_count_values($filtered);
        arsort($counts);
        return array_slice(array_keys($counts), 0, 5);
    }

    public function render_deals() {
        $user_interests = isset($_COOKIE['wpsdf_user_interests']) ? json_decode(stripslashes($_COOKIE['wpsdf_user_interests']), true) : array();
        if (empty($user_interests)) {
            $user_interests = array('general');
        }

        $deals = $this->get_deals_for_interests($user_interests);

        ob_start();
        echo '<div class="wpsdf-deals-widget">';
        echo '<h3>Recommended Deals for You</h3>';
        if (empty($deals)) {
            echo '<p>No current deals available. Check back soon!</p>';
        } else {
            echo '<ul class="wpsdf-deals-list">';
            foreach($deals as $deal) {
                echo '<li><a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener">';
                echo esc_html($deal['title']);
                if (!empty($deal['code'])) {
                    echo ' - Use Code: <strong>' . esc_html($deal['code']) . '</strong>';
                }
                echo '</a></li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    private function get_deals_for_interests($interests) {
        // Example static deals mapping - in real scenarios a remote API call or DB lookup should be here
        $all_deals = array(
            'tech' => array(
                array('title' => '10% Off Tech Gadgets', 'url' => 'https://example.com/tech-deal', 'code' => 'TECH10'),
                array('title' => 'Free Shipping on Electronics', 'url' => 'https://example.com/electronics-shipping', 'code' => '')
            ),
            'fashion' => array(
                array('title' => '20% Off Fashion Apparel', 'url' => 'https://example.com/fashion-deal', 'code' => 'FASHION20')
            ),
            'general' => array(
                array('title' => '5% Off Sitewide', 'url' => 'https://example.com/general-deal', 'code' => 'SAVE5')
            )
        );

        $matched_deals = array();
        foreach ($interests as $interest) {
            if (isset($all_deals[$interest])) {
                $matched_deals = array_merge($matched_deals, $all_deals[$interest]);
            }
        }
        if (empty($matched_deals)) {
            $matched_deals = $all_deals['general'];
        }
        return $matched_deals;
    }
}

new WPSmartDealFinder();

?>