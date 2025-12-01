<?php
/*
Plugin Name: Affiliate Link Optimizer
Description: Automatically detects, cloaks, and optimizes affiliate links in WordPress posts.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Link_Optimizer.php
*/

if(!defined('ABSPATH')) exit;

class Affiliate_Link_Optimizer {

    public function __construct() {
        add_filter('the_content', array($this, 'process_affiliate_links'));
        add_action('wp_footer', array($this, 'add_redirect_handler'));
        add_action('init', array($this, 'handle_redirect'));
    }

    // Detect affiliate links and replace with cloaked URLs
    public function process_affiliate_links($content) {
        $pattern = '/https?:\/\/(?:www\.)?(amazon\.com|clickbank\.net|shareasale\.com)\/[^\s"\'<>]+/i';
        if(preg_match_all($pattern, $content, $matches)) {
            foreach($matches as $url) {
                $cloaked_url = $this->get_cloaked_url($url);
                // Replace original URL with cloaked URL
                $content = str_replace($url, $cloaked_url, $content);
            }
        }
        return $content;
    }

    // Generate a cloaked URL under site domain
    private function get_cloaked_url($url) {
        $hash = md5($url);
        return home_url('/alo-redirect/' . $hash . '/');
    }

    // Handle cloaked URL redirects
    public function handle_redirect() {
        $request_uri = $_SERVER['REQUEST_URI'];
        if (preg_match('#/alo-redirect/([a-f0-9]{32})/?#', $request_uri, $matches)) {
            $hash = $matches[1];
            // Map hash back to original URL from transient cache or db
            $original_url = get_transient('alo_' . $hash);
            if(!$original_url) {
                // If not in cache, try to find matching URL in post content
                $original_url = $this->find_url_by_hash($hash);
                if($original_url) {
                    // Store transient for 1 day
                    set_transient('alo_' . $hash, $original_url, DAY_IN_SECONDS);
                }
            }
            if($original_url) {
                // Add click tracking here if desired
                wp_redirect($original_url, 301);
                exit;
            } else {
                // Not found
                status_header(404);
                echo 'Affiliate link not found.';
                exit;
            }
        }
    }

    // Try to find affiliate URL by hash scanning post contents
    private function find_url_by_hash($hash) {
        $args = array(
            'post_type' => 'post',
            'posts_per_page' => 100,
            'post_status' => 'publish',
            's' => '',
        );
        $posts = get_posts($args);
        foreach($posts as $post) {
            preg_match_all('/https?:\/\/(?:www\.)?(amazon\.com|clickbank\.net|shareasale\.com)\/[^\s"\'<>]+/i', $post->post_content, $matches);
            foreach($matches as $url) {
                if(md5($url) === $hash) return $url;
            }
        }
        return false;
    }

    // Add HTML fallback for cloaked URL visits without redirect
    public function add_redirect_handler() {
        ?><script>
        // JS fallback redirect can be added here if needed
        </script><?php
    }
}

new Affiliate_Link_Optimizer();