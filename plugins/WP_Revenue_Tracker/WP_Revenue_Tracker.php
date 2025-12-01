/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Tracker.php
*/
<?php
/**
 * Plugin Name: WP Revenue Tracker
 * Description: Track, analyze, and optimize your WordPress site's revenue streams.
 * Version: 1.0
 * Author: WP Dev Team
 */

class WP_Revenue_Tracker {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_save_revenue_data', array($this, 'save_revenue_data'));
        add_action('wp_ajax_get_revenue_stats', array($this, 'get_revenue_stats'));
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

    public function register_settings() {
        register_setting('wp_revenue_tracker_group', 'wp_revenue_tracker_data');
    }

    public function render_dashboard() {
        $data = get_option('wp_revenue_tracker_data', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Tracker</h1>
            <form method="post" action="">
                <?php settings_fields('wp_revenue_tracker_group'); ?>
                <table class="form-table">
                    <tr>
                        <th><label>Revenue Source</label></th>
                        <td><input type="text" name="wp_revenue_tracker_data[source]" value="<?php echo esc_attr($data['source'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label>Amount ($)</label></th>
                        <td><input type="number" step="0.01" name="wp_revenue_tracker_data[amount]" value="<?php echo esc_attr($data['amount'] ?? ''); ?>" /></td>
                    </tr>
                    <tr>
                        <th><label>Date</label></th>
                        <td><input type="date" name="wp_revenue_tracker_data[date]" value="<?php echo esc_attr($data['date'] ?? ''); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button('Save Revenue'); ?>
            </form>
            <div id="revenue-stats">
                <h2>Revenue Stats</h2>
                <p>Loading stats...</p>
            </div>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $.post(ajaxurl, {action: 'get_revenue_stats'}, function(response) {
                    $('#revenue-stats').html('<h2>Revenue Stats</h2><p>Total: $' + response.total + '</p><p>Average: $' + response.average + '</p>');
                });
            });
        </script>
        <?php
    }

    public function save_revenue_data() {
        if (!current_user_can('manage_options')) wp_die('Unauthorized');
        $data = $_POST['data'];
        $existing = get_option('wp_revenue_tracker_data_history', array());
        $existing[] = $data;
        update_option('wp_revenue_tracker_data_history', $existing);
        update_option('wp_revenue_tracker_data', $data);
        wp_die('Saved');
    }

    public function get_revenue_stats() {
        $history = get_option('wp_revenue_tracker_data_history', array());
        $total = 0;
        foreach ($history as $entry) {
            $total += (float)$entry['amount'];
        }
        $average = count($history) > 0 ? round($total / count($history), 2) : 0;
        wp_send_json(array('total' => round($total, 2), 'average' => $average));
    }
}

new WP_Revenue_Tracker();
