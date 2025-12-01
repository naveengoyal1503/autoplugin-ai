/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Plugin URI: https://example.com/wp-revenue-booster
 * Description: Maximize revenue by rotating monetization methods based on visitor behavior and content context.
 * Version: 1.0
 * Author: Revenue Labs
 * Author URI: https://example.com
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_monetization')); 
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function render_monetization() {
        if (is_admin() || !is_main_query()) return;

        $methods = get_option('wp_revenue_booster_methods', array());
        if (empty($methods)) return;

        $method = $this->select_method($methods);
        if (!$method) return;

        echo '<div class="wp-revenue-booster">';
        switch ($method['type']) {
            case 'ad':
                echo '<div class="ad-unit">' . esc_html($method['content']) . '</div>';
                break;
            case 'affiliate':
                echo '<div class="affiliate-link"><a href="' . esc_url($method['url']) . '" target="_blank">' . esc_html($method['label']) . '</a></div>';
                break;
            case 'donation':
                echo '<div class="donation-button"><button onclick="alert(\'Donate now!\')">' . esc_html($method['label']) . '</button></div>';
                break;
            case 'membership':
                echo '<div class="membership-offer">' . esc_html($method['content']) . '</div>';
                break;
        }
        echo '</div>';
    }

    private function select_method($methods) {
        $weights = array();
        foreach ($methods as $method) {
            $weights[] = $method['weight'];
        }
        $total = array_sum($weights);
        $rand = mt_rand(1, $total);
        $current = 0;
        foreach ($methods as $method) {
            $current += $method['weight'];
            if ($rand <= $current) {
                return $method;
            }
        }
        return null;
    }

    public function add_admin_menu() {
        add_options_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'options_page')
        );
    }

    public function settings_init() {
        register_setting('wp_revenue_booster', 'wp_revenue_booster_methods');
    }

    public function options_page() {
        $methods = get_option('wp_revenue_booster_methods', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wp_revenue_booster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Monetization Methods</th>
                        <td>
                            <div id="methods-container">
                                <?php foreach ($methods as $method): ?>
                                <div class="method-row">
                                    <select name="wp_revenue_booster_methods[][type]">
                                        <option value="ad" <?php selected($method['type'], 'ad'); ?>>Ad</option>
                                        <option value="affiliate" <?php selected($method['type'], 'affiliate'); ?>>Affiliate</option>
                                        <option value="donation" <?php selected($method['type'], 'donation'); ?>>Donation</option>
                                        <option value="membership" <?php selected($method['type'], 'membership'); ?>>Membership</option>
                                    </select>
                                    <input type="text" name="wp_revenue_booster_methods[][label]" value="<?php echo esc_attr($method['label']); ?>" placeholder="Label">
                                    <input type="text" name="wp_revenue_booster_methods[][url]" value="<?php echo esc_attr($method['url']); ?>" placeholder="URL (if affiliate)">
                                    <textarea name="wp_revenue_booster_methods[][content]" placeholder="Content (if ad or membership)"><?php echo esc_textarea($method['content']); ?></textarea>
                                    <input type="number" name="wp_revenue_booster_methods[][weight]" value="<?php echo esc_attr($method['weight']); ?>" placeholder="Weight" min="1">
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addMethodRow()">Add Method</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            function addMethodRow() {
                const container = document.getElementById('methods-container');
                const row = document.createElement('div');
                row.className = 'method-row';
                row.innerHTML = ` 
                    <select name="wp_revenue_booster_methods[][type]">
                        <option value="ad">Ad</option>
                        <option value="affiliate">Affiliate</option>
                        <option value="donation">Donation</option>
                        <option value="membership">Membership</option>
                    </select>
                    <input type="text" name="wp_revenue_booster_methods[][label]" placeholder="Label">
                    <input type="text" name="wp_revenue_booster_methods[][url]" placeholder="URL (if affiliate)">
                    <textarea name="wp_revenue_booster_methods[][content]" placeholder="Content (if ad or membership)"></textarea>
                    <input type="number" name="wp_revenue_booster_methods[][weight]" placeholder="Weight" min="1">
                `;
                container.appendChild(row);
            }
        </script>
        <?php
    }
}

new WP_Revenue_Booster();
?>