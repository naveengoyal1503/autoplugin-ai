<?php
/*
Plugin Name: WP Revenue Tracker
Description: Track and optimize your WordPress site's monetization efforts.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_footer', array($this, 'track_clicks'));
        add_action('rest_api_init', array($this, 'register_api_routes'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'Revenue Tracker',
            'Revenue Tracker',
            'manage_options',
            'wp-revenue-tracker',
            array($this, 'render_admin_page'),
            'dashicons-chart-bar',
            6
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <div id="wp-revenue-tracker-app"></div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const app = document.getElementById('wp-revenue-tracker-app');
                app.innerHTML = '<h2>Revenue Dashboard</h2><p>Track clicks, conversions, and earnings from ads, affiliate links, and digital products.</p><p><strong>Free Version:</strong> Basic click tracking. <a href="https://example.com/premium" target="_blank">Upgrade to Premium</a> for advanced analytics and integrations.</p>';
            });
        </script>
        <?php
    }

    public function track_clicks() {
        if (is_admin()) return;
        ?>
        <script>
            document.addEventListener('click', function(e) {
                if (e.target.tagName === 'A') {
                    const url = e.target.href;
                    if (url.includes('amazon') || url.includes('affiliate') || url.includes('ads')) {
                        fetch('<?php echo rest_url('wp-revenue-tracker/v1/click'); ?>', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ url: url, type: 'affiliate' })
                        });
                    }
                }
            });
        </script>
        <?php
    }

    public function register_api_routes() {
        register_rest_route('wp-revenue-tracker/v1', '/click', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_click'),
            'permission_callback' => '__return_true'
        ));
    }

    public function handle_click($request) {
        $data = $request->get_json_params();
        $url = sanitize_text_field($data['url']);
        $type = sanitize_text_field($data['type']);
        $log = array(
            'url' => $url,
            'type' => $type,
            'timestamp' => current_time('mysql'),
            'ip' => $_SERVER['REMOTE_ADDR']
        );
        // In a real plugin, you'd save this to the database
        return rest_ensure_response(array('status' => 'logged', 'data' => $log));
    }
}

new WP_Revenue_Tracker();
