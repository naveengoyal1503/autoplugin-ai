/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Booster.php
*/
<?php
/**
 * Plugin Name: Affiliate Deal Booster
 * Description: Automates coupon/deal aggregation with affiliate link management and displays them in a customizable widget.
 * Version: 1.0
 * Author: PluginDev
 */

if (!defined('ABSPATH')) { exit; }

class AffiliateDealBooster {
    private $deals_option = 'adb_deals';

    public function __construct() {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_post_adb_save_deal', [$this, 'save_deal']);
        add_action('widgets_init', function() {
            register_widget('ADB_Deals_Widget');
        });
        add_shortcode('adb_deals', [$this, 'shortcode']);
    }

    public function admin_menu() {
        add_menu_page('Affiliate Deal Booster', 'Affiliate Deals', 'manage_options', 'adb_deals', [$this, 'admin_page']);
    }

    public function admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $deals = get_option($this->deals_option, []);
        ?>
        <div class="wrap">
            <h1>Affiliate Deal Booster</h1>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="adb_save_deal">
                <?php wp_nonce_field('adb_save_deal_nonce'); ?>

                <h2>Add New Deal</h2>
                <table class="form-table">
                    <tr><th><label for="title">Title</label></th><td><input type="text" id="title" name="title" required class="regular-text"></td></tr>
                    <tr><th><label for="description">Description</label></th><td><textarea id="description" name="description" rows="3" class="large-text" required></textarea></td></tr>
                    <tr><th><label for="url">Affiliate URL</label></th><td><input type="url" id="url" name="url" required class="regular-text"></td></tr>
                    <tr><th><label for="expiration">Expiration Date</label></th><td><input type="date" id="expiration" name="expiration"></td></tr>
                </table>
                <input type="submit" class="button button-primary" value="Add Deal">
            </form>

            <h2>Existing Deals</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Description</th><th>URL</th><th>Expires</th><th>Actions</th></tr></thead>
                <tbody>
                <?php
                if(!empty($deals)) {
                    foreach($deals as $index => $deal) {
                        $exp = empty($deal['expiration']) ? 'None' : esc_html($deal['expiration']);
                        echo '<tr>' .
                             '<td>' . esc_html($deal['title']) . '</td>' .
                             '<td>' . esc_html($deal['description']) . '</td>' .
                             '<td><a href="' . esc_url($deal['url']) . '" target="_blank">Link</a></td>' .
                             '<td>' . $exp . '</td>' .
                             '<td><a href="' . esc_url(admin_url('admin-post.php?action=adb_delete_deal&index=' . $index)) . '">Delete</a></td>' .
                             '</tr>';
                    }
                } else {
                    echo '<tr><td colspan="5">No deals added yet.</td></tr>';
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function save_deal() {
        if (!current_user_can('manage_options') || !check_admin_referer('adb_save_deal_nonce')) {
            wp_die('Unauthorized');
        }

        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $url = esc_url_raw($_POST['url']);
        $expiration = sanitize_text_field($_POST['expiration']);

        $deals = get_option($this->deals_option, []);
        $deals[] = compact('title', 'description', 'url', 'expiration');

        update_option($this->deals_option, $deals);

        wp_redirect(admin_url('admin.php?page=adb_deals'));
        exit;
    }

    public function shortcode() {
        $deals = get_option($this->deals_option, []);
        if(empty($deals)) return '<p>No deals available now. Check back soon!</p>';

        $output = '<div class="adb-deals">';

        foreach($deals as $deal) {
            if (!empty($deal['expiration']) && strtotime($deal['expiration']) < time()) continue;
            $output .= '<div class="adb-deal" style="margin-bottom:15px; padding:10px; border:1px solid #ccc;">';
            $output .= '<h3>' . esc_html($deal['title']) . '</h3>';
            $output .= '<p>' . esc_html($deal['description']) . '</p>';
            $output .= '<a href="' . esc_url($deal['url']) . '" target="_blank" rel="nofollow noopener" style="background:#0073aa;color:#fff;padding:8px 12px;text-decoration:none;border-radius:3px;">Grab Deal</a>';
            $output .= '</div>';
        }

        $output .= '</div>';

        return $output;
    }
}

// Widget
class ADB_Deals_Widget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'adb_deals_widget',
            'Affiliate Deal Booster Widget',
            ['description' => 'Displays current affiliate deals and coupons']
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        echo do_shortcode('[adb_deals]');
        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Special Deals';
        ?>
        <p><label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Title:</label>
        <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>"></p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = sanitize_text_field($new_instance['title']);
        return $instance;
    }
}

new AffiliateDealBooster();
