<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and optimize your WordPress site's monetization streams.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('wp_ajax_track_revenue', array($this, 'track_revenue'));
        add_action('wp_ajax_nopriv_track_revenue', array($this, 'track_revenue'));
        add_action('wp_ajax_get_revenue_stats', array($this, 'get_revenue_stats'));
        add_action('wp_ajax_nopriv_get_revenue_stats', array($this, 'get_revenue_stats'));
    }

    public function add_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_dashboard'),
            'dashicons-chart-bar'
        );
    }

    public function render_dashboard() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="revenue-stats">
                <p>Loading stats...</p>
            </div>
            <form id="track-revenue-form">
                <input type="hidden" name="action" value="track_revenue">
                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('track_revenue_nonce'); ?>">
                <input type="text" name="source" placeholder="Source (e.g., AdSense, Affiliate)" required>
                <input type="number" name="amount" placeholder="Amount" required>
                <button type="submit">Track Revenue</button>
            </form>
        </div>
        <script>
            jQuery(document).ready(function($) {
                function loadStats() {
                    $.post(ajaxurl, { action: 'get_revenue_stats' }, function(response) {
                        $('#revenue-stats').html('<h2>Revenue Stats</h2><pre>' + JSON.stringify(response, null, 2) + '</pre>');
                    });
                }
                $('#track-revenue-form').on('submit', function(e) {
                    e.preventDefault();
                    $.post(ajaxurl, $(this).serialize(), function() {
                        loadStats();
                        $('#track-revenue-form').reset();
                    });
                });
                loadStats();
            });
        </script>
        <?php
    }

    public function track_revenue() {
        check_ajax_referer('track_revenue_nonce', 'nonce');
        $source = sanitize_text_field($_POST['source']);
        $amount = floatval($_POST['amount']);
        $data = get_option('wp_revenue_tracker_data', array());
        $data[] = array('source' => $source, 'amount' => $amount, 'date' => current_time('mysql'));
        update_option('wp_revenue_tracker_data', $data);
        wp_die();
    }

    public function get_revenue_stats() {
        $data = get_option('wp_revenue_tracker_data', array());
        $stats = array();
        foreach ($data as $entry) {
            $source = $entry['source'];
            if (!isset($stats[$source])) {
                $stats[$source] = 0;
            }
            $stats[$source] += $entry['amount'];
        }
        wp_die(json_encode($stats));
    }
}

new WP_Revenue_Tracker();
