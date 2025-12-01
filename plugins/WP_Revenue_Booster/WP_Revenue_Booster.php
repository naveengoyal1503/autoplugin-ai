/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Revenue_Booster.php
*/
<?php
/**
 * Plugin Name: WP Revenue Booster
 * Description: Maximize revenue by rotating affiliate links, displaying targeted ads, and promoting exclusive offers.
 * Version: 1.0
 * Author: WP Revenue Team
 */

define('WP_REVENUE_BOOSTER_VERSION', '1.0');

class WPRevenueBooster {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_footer', array($this, 'render_offer_bar'));
        add_shortcode('wp_revenue_offer', array($this, 'offer_shortcode'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action('wp_ajax_save_offer', array($this, 'save_offer'));
        add_action('wp_ajax_nopriv_save_offer', array($this, 'save_offer'));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/style.css');
        wp_enqueue_script('wp-revenue-booster', plugin_dir_url(__FILE__) . 'assets/script.js', array('jquery'), WP_REVENUE_BOOSTER_VERSION, true);
        wp_localize_script('wp-revenue-booster', 'wpRevenueBooster', array(
            'ajax_url' => admin_url('admin-ajax.php')
        ));
    }

    public function render_offer_bar() {
        if (is_admin()) return;
        $offers = get_option('wp_revenue_offers', array());
        if (empty($offers)) return;
        $offer = $offers[array_rand($offers)];
        echo '<div id="wp-revenue-offer-bar" style="display:none;">
            <span>' . esc_html($offer['title']) . '</span>
            <a href="' . esc_url($offer['link']) . '" target="_blank" class="wp-revenue-offer-cta">' . esc_html($offer['cta']) . '</a>
            <span class="wp-revenue-offer-close">×</span>
        </div>';
    }

    public function offer_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => '',
        ), $atts, 'wp_revenue_offer');

        $offers = get_option('wp_revenue_offers', array());
        if (empty($offers)) return '';

        $offer = null;
        if ($atts['id']) {
            foreach ($offers as $o) {
                if ($o['id'] == $atts['id']) {
                    $offer = $o;
                    break;
                }
            }
        } else {
            $offer = $offers[array_rand($offers)];
        }

        if (!$offer) return '';

        return '<div class="wp-revenue-offer">
            <h4>' . esc_html($offer['title']) . '</h4>
            <p>' . esc_html($offer['description']) . '</p>
            <a href="' . esc_url($offer['link']) . '" target="_blank" class="button">' . esc_html($offer['cta']) . '</a>
        </div>';
    }

    public function admin_menu() {
        add_menu_page(
            'WP Revenue Booster',
            'Revenue Booster',
            'manage_options',
            'wp-revenue-booster',
            array($this, 'admin_page'),
            'dashicons-chart-line'
        );
    }

    public function admin_page() {
        $offers = get_option('wp_revenue_offers', array());
        ?>
        <div class="wrap">
            <h1>WP Revenue Booster</h1>
            <form method="post" id="wp-revenue-offer-form">
                <table class="form-table">
                    <tr>
                        <th><label>Offer Title</label></th>
                        <td><input type="text" name="title" required /></td>
                    </tr>
                    <tr>
                        <th><label>Description</label></th>
                        <td><textarea name="description" rows="3" required></textarea></td>
                    </tr>
                    <tr>
                        <th><label>CTA Text</label></th>
                        <td><input type="text" name="cta" value="Learn More" required /></td>
                    </tr>
                    <tr>
                        <th><label>Link URL</label></th>
                        <td><input type="url" name="link" required /></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="hidden" name="action" value="save_offer" />
                    <?php wp_nonce_field('save_offer'); ?>
                    <input type="submit" class="button-primary" value="Add Offer" />
                </p>
            </form>
            <h2>Active Offers</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Link</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($offers as $offer): ?>
                    <tr>
                        <td><?php echo esc_html($offer['title']); ?></td>
                        <td><?php echo esc_html($offer['description']); ?></td>
                        <td><a href="<?php echo esc_url($offer['link']); ?>" target="_blank">Link</a></td>
                        <td><button class="button delete-offer" data-id="<?php echo esc_attr($offer['id']); ?>">Delete</button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <script>
            jQuery(document).ready(function($) {
                $('#wp-revenue-offer-form').on('submit', function(e) {
                    e.preventDefault();
                    $.post(wpRevenueBooster.ajax_url, $(this).serialize(), function(res) {
                        location.reload();
                    });
                });
                $('.delete-offer').on('click', function() {
                    if (confirm('Delete this offer?')) {
                        $.post(wpRevenueBooster.ajax_url, {
                            action: 'delete_offer',
                            id: $(this).data('id'),
                            nonce: '<?php echo wp_create_nonce('delete_offer'); ?>'
                        }, function() {
                            location.reload();
                        });
                    }
                });
            });
        </script>
        <?php
    }

    public function save_offer() {
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'save_offer')) {
            wp_die('Unauthorized');
        }

        $offers = get_option('wp_revenue_offers', array());
        $new_offer = array(
            'id' => uniqid(),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'cta' => sanitize_text_field($_POST['cta']),
            'link' => esc_url_raw($_POST['link'])
        );
        $offers[] = $new_offer;
        update_option('wp_revenue_offers', $offers);
        wp_die('Offer saved');
    }
}

new WPRevenueBooster();
?>