/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Boost_Manager.php
*/
<?php
/**
 * Plugin Name: Affiliate Boost Manager
 * Description: Manage and boost your affiliate marketing with link cloaking, tracking, and coupon features.
 * Version: 1.0
 * Author: YourName
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class AffiliateBoostManager {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_abm_add_affiliate_link', array($this, 'handle_add_affiliate_link'));
        add_shortcode('abm_affiliate_links', array($this, 'render_affiliate_links'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('template_redirect', array($this, 'redirect_affiliate_link'));
        add_action('wp_ajax_abm_generate_coupon', array($this, 'generate_coupon_ajax'));
        add_action('wp_ajax_nopriv_abm_generate_coupon', array($this, 'generate_coupon_ajax'));
    }

    // Enqueue frontend scripts
    public function enqueue_scripts() {
        if (is_page() || is_single()) {
            wp_enqueue_script('abm-script', plugin_dir_url(__FILE__) . 'abm-script.js', array('jquery'), '1.0', true);
            wp_localize_script('abm-script', 'abm_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
        }
    }

    // Add admin menu
    public function add_admin_menu() {
        add_menu_page(
            'Affiliate Boost Manager',
            'Affiliate Boost',
            'manage_options',
            'affiliate-boost-manager',
            array($this, 'admin_page'),
            'dashicons-admin-links'
        );
    }

    // Render admin page
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        $links = get_option('abm_affiliate_links', array());
        ?>
        <div class="wrap">
            <h1>Affiliate Boost Manager</h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="abm_add_affiliate_link">
                <?php wp_nonce_field('abm_add_affiliate_link_nonce', 'abm_nonce_field'); ?>
                <table class="form-table" style="max-width:600px;">
                    <tr>
                        <th scope="row"><label for="abm_name">Link Name</label></th>
                        <td><input name="abm_name" id="abm_name" type="text" required class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="abm_url">Affiliate URL</label></th>
                        <td><input name="abm_url" id="abm_url" type="url" required class="regular-text"/></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="abm_coupon">Coupon Code (optional)</label></th>
                        <td><input name="abm_coupon" id="abm_coupon" type="text" class="regular-text"/></td>
                    </tr>
                </table>
                <?php submit_button('Add Affiliate Link'); ?>
            </form>

            <h2>Existing Affiliate Links</h2>
            <table class="widefat fixed" cellspacing="0" style="max-width:600px;">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>URL</th>
                        <th>Coupon</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($links as $id => $link): ?>
                    <tr>
                        <td><?php echo esc_html($link['name']); ?></td>
                        <td><?php echo esc_html($link['url']); ?></td>
                        <td><?php echo esc_html($link['coupon']); ?></td>
                        <td><?php echo isset($link['clicks']) ? intval($link['clicks']) : 0; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>

            <h2>Generate Coupon via AJAX</h2>
            <button id="abm-generate-coupon-btn" class="button button-primary">Generate Coupon</button>
            <p id="abm-coupon-result"></p>
        </div>
        <?php
    }

    // Handle adding affiliate link
    public function handle_add_affiliate_link() {
        if (!isset($_POST['abm_nonce_field']) || !wp_verify_nonce($_POST['abm_nonce_field'], 'abm_add_affiliate_link_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        $links = get_option('abm_affiliate_links', array());
        $id = uniqid('abm_');

        $links[$id] = array(
            'name' => sanitize_text_field($_POST['abm_name']),
            'url' => esc_url_raw($_POST['abm_url']),
            'coupon' => sanitize_text_field($_POST['abm_coupon']),
            'clicks' => 0
        );
        update_option('abm_affiliate_links', $links);

        wp_redirect(admin_url('admin.php?page=affiliate-boost-manager'));
        exit;
    }

    // Handle redirect and tracking
    public function redirect_affiliate_link() {
        if (isset($_GET['abm_redirect'])) {
            $id = sanitize_text_field($_GET['abm_redirect']);
            $links = get_option('abm_affiliate_links', array());
            if (isset($links[$id])) {
                $links[$id]['clicks'] = isset($links[$id]['clicks']) ? intval($links[$id]['clicks']) + 1 : 1;
                update_option('abm_affiliate_links', $links);

                wp_redirect($links[$id]['url']);
                exit;
            }
        }
    }

    // Shortcode to list links
    public function render_affiliate_links() {
        $links = get_option('abm_affiliate_links', array());
        if (empty($links)) {
            return '<p>No affiliate links available.</p>';
        }

        $output = '<ul class="abm-affiliate-links">';
        foreach ($links as $id => $link) {
            $url = esc_url(add_query_arg('abm_redirect', $id, home_url()));
            $coupon_text = $link['coupon'] ? ' (Coupon: ' . esc_html($link['coupon']) . ')' : '';
            $output .= '<li><a href="' . $url . '" target="_blank" rel="nofollow noopener">' . esc_html($link['name']) . '</a>' . $coupon_text . '</li>';
        }
        $output .= '</ul>';

        return $output;
    }

    // AJAX coupon generator (dummy example)
    public function generate_coupon_ajax() {
        $coupon = 'ABM' . wp_rand(1000, 9999);
        wp_send_json_success(array('coupon' => $coupon));
    }
}

new AffiliateBoostManager();

// Minimal JS for coupon generation button
add_action('admin_footer', function() {
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('#abm-generate-coupon-btn').click(function(e) {
            e.preventDefault();
            $('#abm-coupon-result').text('Generating coupon...');
            $.post(ajaxurl, { action: 'abm_generate_coupon' }, function(response) {
                if(response.success) {
                    $('#abm-coupon-result').text('Generated Coupon: ' + response.data.coupon);
                } else {
                    $('#abm-coupon-result').text('Coupon generation failed.');
                }
            });
        });
    });
    </script>
    <?php
});
