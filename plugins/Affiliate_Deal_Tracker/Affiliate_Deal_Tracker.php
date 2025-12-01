/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Tracker.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Tracker
 * Description: Manage and display affiliate coupons and deals with tracking and analytics.
 * Version: 1.0
 * Author: Generated Plugin
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AffiliateDealTracker {
    private $plugin_slug = 'affiliate-deal-tracker';
    private $version = '1.0';
    private $option_name = 'adt_deals_data';

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_shortcode('adt_deals', array($this, 'shortcode_display_deals'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('init', array($this, 'track_affiliate_click'));
    }

    public function enqueue_styles() {
        wp_enqueue_style('adt_styles', plugin_dir_url(__FILE__) . 'style.css');
    }

    public function add_admin_menu() {
        add_menu_page('Affiliate Deal Tracker', 'Affiliate Deals', 'manage_options', $this->plugin_slug, array($this, 'admin_page'), 'dashicons-tag', 80);
    }

    public function register_settings() {
        register_setting($this->plugin_slug, $this->option_name);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) { wp_die('Unauthorized user'); }

        $deals = get_option($this->option_name, array());

        // Handle form submission
        if (isset($_POST['adt_submit'])) {
            check_admin_referer('adt_save_deal','adt_nonce');

            $id = sanitize_text_field($_POST['deal_id']);
            $title = sanitize_text_field($_POST['title']);
            $code = sanitize_text_field($_POST['coupon_code']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $desc = sanitize_textarea_field($_POST['description']);

            if (empty($id)) { // New deal
                $id = uniqid('deal_');
            }

            $deals[$id] = array(
                'title' => $title,
                'coupon_code' => $code,
                'affiliate_url' => $url,
                'description' => $desc,
                'clicks' => isset($deals[$id]['clicks']) ? $deals[$id]['clicks'] : 0
            );

            update_option($this->option_name, $deals);

            echo '<div class="updated"><p>Deal saved.</p></div>';
        }

        if (isset($_GET['delete']) && isset($deals[$_GET['delete']])) {
            check_admin_referer('adt_delete_deal','adt_delete_nonce');
            unset($deals[$_GET['delete']]);
            update_option($this->option_name, $deals);
            echo '<div class="updated"><p>Deal deleted.</p></div>';
        }

        // Edit deal if requested
        $edit_deal = null;
        if (isset($_GET['edit']) && isset($deals[$_GET['edit']])) {
            $edit_deal = $deals[$_GET['edit']];
            $edit_deal['id'] = $_GET['edit'];
        }

        ?>
        <div class="wrap">
            <h1>Affiliate Deal Tracker</h1>

            <h2><?php echo $edit_deal ? 'Edit Deal' : 'Add New Deal'; ?></h2>
            <form method="post" action="">
                <?php wp_nonce_field('adt_save_deal','adt_nonce'); ?>
                <input type="hidden" name="deal_id" value="<?php echo $edit_deal ? esc_attr($edit_deal['id']) : ''; ?>">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="title">Title</label></th>
                        <td><input name="title" type="text" value="<?php echo $edit_deal ? esc_attr($edit_deal['title']) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="coupon_code">Coupon Code</label></th>
                        <td><input name="coupon_code" type="text" value="<?php echo $edit_deal ? esc_attr($edit_deal['coupon_code']) : ''; ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input name="affiliate_url" type="url" value="<?php echo $edit_deal ? esc_url($edit_deal['affiliate_url']) : ''; ?>" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="description">Description</label></th>
                        <td><textarea name="description" rows="5" cols="50"><?php echo $edit_deal ? esc_textarea($edit_deal['description']) : ''; ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button($edit_deal ? 'Save Deal' : 'Add Deal', 'primary', 'adt_submit'); ?>
            </form>

            <h2>Existing Deals</h2>
            <table class="widefat fixed" cellspacing="0">
                <thead>
                    <tr>
                        <th>Title</th><th>Coupon Code</th><th>Description</th><th>Clicks</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (!empty($deals)) : foreach($deals as $id => $deal): ?>
                    <tr>
                        <td><?php echo esc_html($deal['title']); ?></td>
                        <td><?php echo esc_html($deal['coupon_code']); ?></td>
                        <td><?php echo esc_html(wp_trim_words($deal['description'],10,'...')); ?></td>
                        <td><?php echo intval($deal['clicks']); ?></td>
                        <td>
                            <a href="?page=<?php echo $this->plugin_slug; ?>&edit=<?php echo esc_attr($id); ?>">Edit</a> |
                            <a href="?page=<?php echo $this->plugin_slug; ?>&delete=<?php echo esc_attr($id); ?>&adt_delete_nonce=<?php echo wp_create_nonce('adt_delete_deal'); ?>" onclick="return confirm('Are you sure you want to delete this deal?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5">No deals added yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function shortcode_display_deals() {
        $deals = get_option($this->option_name, array());
        if (empty($deals)) return '<p>No deals available at this time.</p>';

        $output = '<div class="adt-deals-list">';
        foreach ($deals as $id => $deal) {
            $url = add_query_arg(array('adt_redirect' => $id), site_url('/')); // Redirect handler
            $output .= '<div class="adt-deal">';
            $output .= '<h3>' . esc_html($deal['title']) . '</h3>';
            if (!empty($deal['coupon_code'])) {
                $output .= '<p>Use Coupon Code: <strong>' . esc_html($deal['coupon_code']) . '</strong></p>';
            }
            if (!empty($deal['description'])) {
                $output .= '<p>' . esc_html($deal['description']) . '</p>';
            }
            $output .= '<a href="' . esc_url($url) . '" target="_blank" rel="nofollow noopener" class="adt-button">Redeem Deal</a>';
            $output .= '</div>';
        }
        $output .= '</div>';

        return $output;
    }

    public function track_affiliate_click() {
        if (isset($_GET['adt_redirect'])) {
            $deals = get_option($this->option_name, array());
            $id = sanitize_text_field($_GET['adt_redirect']);
            if (isset($deals[$id])) {
                $deals[$id]['clicks'] = isset($deals[$id]['clicks']) ? intval($deals[$id]['clicks']) + 1 : 1;
                update_option($this->option_name, $deals);
                wp_redirect($deals[$id]['affiliate_url']);
                exit;
            }
        }
    }
}

new AffiliateDealTracker();

// Minimal CSS for the plugin
add_action('wp_head', function() {
    echo '<style>.adt-deals-list { display: flex; flex-wrap: wrap; gap: 20px; }
          .adt-deal { border: 1px solid #ccc; padding: 15px; flex: 1 1 300px; background: #f9f9f9; border-radius: 8px; }
          .adt-button { display: inline-block; background: #0073aa; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; font-weight: bold; }
          .adt-button:hover { background: #005177; }</style>';
});