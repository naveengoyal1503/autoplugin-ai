/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Rotates high-converting affiliate links, coupons, and sponsored banners.
 * Version: 1.0
 * Author: Revenue Labs
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_shortcode('revenue_booster', array($this, 'render_shortcode'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'affiliate',
            'context' => 'post',
        ), $atts, 'revenue_booster');

        $items = get_option('wp_revenue_booster_items', array());
        $filtered = array_filter($items, function($item) use ($atts) {
            return $item['type'] === $atts['type'] && in_array($atts['context'], $item['contexts']);
        });

        if (empty($filtered)) return '';

        $item = $filtered[array_rand($filtered)];
        $link = $item['link'];
        $text = $item['text'];
        $image = $item['image'];

        $output = '<div class="wp-revenue-booster">
            <a href="' . esc_url($link) . '" target="_blank">
                ' . ($image ? '<img src="' . esc_url($image) . '" alt="' . esc_attr($text) . '" />' : '') . '
                <span>' . esc_html($text) . '</span>
            </a>
        </div>';

        return $output;
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
        register_setting('wpRevenueBooster', 'wp_revenue_booster_items');
    }

    public function options_page() {
        $items = get_option('wp_revenue_booster_items', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" action="options.php">
                <?php settings_fields('wpRevenueBooster'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Items</th>
                        <td>
                            <div id="items-container">
                                <?php foreach ($items as $item): ?>
                                    <div class="item-row">
                                        <input type="text" name="wp_revenue_booster_items[][link]" value="<?php echo esc_attr($item['link']); ?>" placeholder="Link" />
                                        <input type="text" name="wp_revenue_booster_items[][text]" value="<?php echo esc_attr($item['text']); ?>" placeholder="Text" />
                                        <input type="text" name="wp_revenue_booster_items[][image]" value="<?php echo esc_attr($item['image']); ?>" placeholder="Image URL" />
                                        <select name="wp_revenue_booster_items[][type]">
                                            <option value="affiliate" <?php selected($item['type'], 'affiliate'); ?>>Affiliate</option>
                                            <option value="coupon" <?php selected($item['type'], 'coupon'); ?>>Coupon</option>
                                            <option value="sponsored" <?php selected($item['type'], 'sponsored'); ?>>Sponsored</option>
                                        </select>
                                        <input type="text" name="wp_revenue_booster_items[][contexts]" value="<?php echo esc_attr(implode(',', $item['contexts'])); ?>" placeholder="Contexts (comma-separated)" />
                                        <button type="button" class="remove-item">Remove</button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" id="add-item">Add Item</button>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
            document.getElementById('add-item').addEventListener('click', function() {
                const container = document.getElementById('items-container');
                const row = document.createElement('div');
                row.className = 'item-row';
                row.innerHTML = `<input type="text" name="wp_revenue_booster_items[][link]" placeholder="Link" />
                    <input type="text" name="wp_revenue_booster_items[][text]" placeholder="Text" />
                    <input type="text" name="wp_revenue_booster_items[][image]" placeholder="Image URL" />
                    <select name="wp_revenue_booster_items[][type]">
                        <option value="affiliate">Affiliate</option>
                        <option value="coupon">Coupon</option>
                        <option value="sponsored">Sponsored</option>
                    </select>
                    <input type="text" name="wp_revenue_booster_items[][contexts]" placeholder="Contexts (comma-separated)" />
                    <button type="button" class="remove-item">Remove</button>`;
                container.appendChild(row);
            });
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-item')) {
                    e.target.closest('.item-row').remove();
                }
            });
        </script>
        <?php
    }
}

new WP_Revenue_Booster();
?>