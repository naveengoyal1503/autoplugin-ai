/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating affiliate links, displaying targeted ads, and promoting digital products.
 * Version: 1.0
 * Author: WP Revenue Team
 */

class WP_Revenue_Booster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'display_promo'));
        add_shortcode('wp_revenue_promo', array($this, 'promo_shortcode'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
    }

    public function display_promo() {
        if (is_user_logged_in()) return; // Don't show to logged-in users
        $promos = get_option('wp_revenue_promos', array());
        if (empty($promos)) return;

        $random_promo = $promos[array_rand($promos)];
        echo '<div class="wp-revenue-promo">';
        echo '<a href="' . esc_url($random_promo['url']) . '" target="_blank">';
        echo '<img src="' . esc_url($random_promo['image']) . '" alt="' . esc_attr($random_promo['title']) . '"/>';
        echo '<p>' . esc_html($random_promo['title']) . '</p>';
        echo '</a></div>';
    }

    public function promo_shortcode($atts) {
        $atts = shortcode_atts(array('id' => ''), $atts);
        $promos = get_option('wp_revenue_promos', array());
        if (empty($promos)) return '';

        $promo = null;
        foreach ($promos as $p) {
            if ($p['id'] == $atts['id']) {
                $promo = $p;
                break;
            }
        }

        if (!$promo) return '';

        return '<div class="wp-revenue-promo-shortcode">
            <a href="' . esc_url($promo['url']) . '" target="_blank">
                <img src="' . esc_url($promo['image']) . '" alt="' . esc_attr($promo['title']) . '"/>
                <p>' . esc_html($promo['title']) . '</p>
            </a>
        </div>';
    }
}

// Admin settings page
function wp_revenue_booster_settings_page() {
    add_options_page(
        'WP Revenue Booster',
        'Revenue Booster',
        'manage_options',
        'wp-revenue-booster',
        'wp_revenue_booster_settings_page_html'
    );
}
add_action('admin_menu', 'wp_revenue_booster_settings_page');

function wp_revenue_booster_settings_page_html() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['wp_revenue_promos'])) {
        update_option('wp_revenue_promos', $_POST['wp_revenue_promos']);
        echo '<div class="updated"><p>Promotions saved.</p></div>';
    }

    $promos = get_option('wp_revenue_promos', array());
    ?>
    <div class="wrap">
        <h1>WP Revenue Booster</h1>
        <form method="post">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Promotions</th>
                    <td>
                        <div id="promos-container">
                            <?php foreach ($promos as $promo): ?>
                                <div class="promo-item">
                                    <input type="text" name="wp_revenue_promos[][id]" value="<?php echo esc_attr($promo['id']); ?>" placeholder="ID" />
                                    <input type="text" name="wp_revenue_promos[][title]" value="<?php echo esc_attr($promo['title']); ?>" placeholder="Title" />
                                    <input type="text" name="wp_revenue_promos[][url]" value="<?php echo esc_url($promo['url']); ?>" placeholder="URL" />
                                    <input type="text" name="wp_revenue_promos[][image]" value="<?php echo esc_url($promo['image']); ?>" placeholder="Image URL" />
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" onclick="addPromo()">Add Promotion</button>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
        function addPromo() {
            const container = document.getElementById('promos-container');
            const div = document.createElement('div');
            div.className = 'promo-item';
            div.innerHTML = '<input type="text" name="wp_revenue_promos[][id]" placeholder="ID" />' +
                '<input type="text" name="wp_revenue_promos[][title]" placeholder="Title" />' +
                '<input type="text" name="wp_revenue_promos[][url]" placeholder="URL" />' +
                '<input type="text" name="wp_revenue_promos[][image]" placeholder="Image URL" />';
            container.appendChild(div);
        }
    </script>
    <?php
}

new WP_Revenue_Booster();
?>