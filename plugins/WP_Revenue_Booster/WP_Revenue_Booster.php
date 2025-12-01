/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Rotate affiliate links, coupons, and sponsored content for maximum revenue.
 * Version: 1.0
 * Author: WP Dev Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('init', array($this, 'init'));
        add_shortcode('revenue_booster', array($this, 'shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function init() {
        if (!get_option('wp_revenue_booster_options')) {
            add_option('wp_revenue_booster_options', array(
                'links' => array(
                    array('url' => 'https://example.com/affiliate1', 'weight' => 50, 'type' => 'affiliate'),
                    array('url' => 'https://example.com/coupon1', 'weight' => 30, 'type' => 'coupon'),
                    array('url' => 'https://example.com/sponsored1', 'weight' => 20, 'type' => 'sponsored')
                )
            ));
        }
    }

    public function shortcode($atts) {
        $options = get_option('wp_revenue_booster_options');
        $links = $options['links'];

        if (empty($links)) return '';

        $total_weight = array_sum(array_column($links, 'weight'));
        $rand = mt_rand(1, $total_weight);
        $current_weight = 0;

        foreach ($links as $link) {
            $current_weight += $link['weight'];
            if ($rand <= $current_weight) {
                $selected = $link;
                break;
            }
        }

        $label = $selected['type'] === 'affiliate' ? 'Affiliate Link' : ($selected['type'] === 'coupon' ? 'Coupon Code' : 'Sponsored Content');
        return '<div class="wp-revenue-booster">
                    <p><strong>' . $label . ':</strong> <a href="' . esc_url($selected['url']) . '" target="_blank" rel="nofollow">Click here</a></p>
                </div>';
    }

    public function admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page')
        );
    }

    public function admin_page() {
        if (isset($_POST['wp_revenue_booster_save'])) {
            $links = array();
            if (isset($_POST['link_url']) && is_array($_POST['link_url'])) {
                foreach ($_POST['link_url'] as $index => $url) {
                    if (!empty($url)) {
                        $links[] = array(
                            'url' => sanitize_text_field($url),
                            'weight' => intval($_POST['link_weight'][$index]),
                            'type' => sanitize_text_field($_POST['link_type'][$index])
                        );
                    }
                }
            }
            update_option('wp_revenue_booster_options', array('links' => $links));
            echo '<div class="updated"><p>Settings saved.</p></div>';
        }

        $options = get_option('wp_revenue_booster_options');
        $links = $options['links'];
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>URL</th>
                        <th>Weight</th>
                        <th>Type</th>
                    </tr>
                    <?php for ($i = 0; $i < 5; $i++): ?>
                    <tr>
                        <td><input type="text" name="link_url[]" value="<?php echo isset($links[$i]) ? esc_attr($links[$i]['url']) : ''; ?>" class="regular-text" /></td>
                        <td><input type="number" name="link_weight[]" value="<?php echo isset($links[$i]) ? esc_attr($links[$i]['weight']) : '10'; ?>" min="1" max="100" /></td>
                        <td>
                            <select name="link_type[]">
                                <option value="affiliate" <?php selected(isset($links[$i]) ? $links[$i]['type'] : 'affiliate', 'affiliate'); ?>>Affiliate</option>
                                <option value="coupon" <?php selected(isset($links[$i]) ? $links[$i]['type'] : 'affiliate', 'coupon'); ?>>Coupon</option>
                                <option value="sponsored" <?php selected(isset($links[$i]) ? $links[$i]['type'] : 'affiliate', 'sponsored'); ?>>Sponsored</option>
                            </select>
                        </td>
                    </tr>
                    <?php endfor; ?>
                </table>
                <?php submit_button('Save Settings', 'primary', 'wp_revenue_booster_save'); ?>
            </form>
            <p>Use <code>[revenue_booster]</code> shortcode to display a rotating link.</p>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugins_url('style.css', __FILE__));
    }
}

new WP_Revenue_Booster;

// style.css
// .wp-revenue-booster { padding: 10px; background: #f0f0f0; border: 1px solid #ccc; margin: 10px 0; }
