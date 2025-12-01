<?php
/*
Plugin Name: ContentMoat
Plugin URI: https://contentmoat.local
Description: Create exclusive subscriber-only content and manage tiered memberships with recurring payments
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentMoat.php
License: GPL v2 or later
Text Domain: contentmoat
*/

if (!defined('ABSPATH')) exit;

define('CONTENTMOAT_VERSION', '1.0.0');
define('CONTENTMOAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CONTENTMOAT_PLUGIN_URL', plugin_dir_url(__FILE__));

class ContentMoat {
    public function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('init', [$this, 'register_taxonomy']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post_contentmoat', [$this, 'save_meta_boxes']);
        add_filter('the_content', [$this, 'filter_exclusive_content']);
        add_shortcode('contentmoat_login_form', [$this, 'render_login_form']);
        add_shortcode('contentmoat_member_dashboard', [$this, 'render_member_dashboard']);
        add_shortcode('contentmoat_subscription_plans', [$this, 'render_subscription_plans']);
        add_action('wp_ajax_nopriv_cm_login', [$this, 'handle_login']);
        add_action('wp_ajax_cm_cancel_subscription', [$this, 'handle_cancel_subscription']);
        register_activation_hook(__FILE__, [$this, 'activate_plugin']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate_plugin']);
    }

    public function register_post_type() {
        register_post_type('contentmoat', [
            'labels' => ['name' => 'Exclusive Content', 'singular_name' => 'Exclusive Post'],
            'public' => true,
            'show_in_menu' => true,
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail'],
            'menu_icon' => 'dashicons-lock',
            'has_archive' => true,
            'rewrite' => ['slug' => 'exclusive']
        ]);
    }

    public function register_taxonomy() {
        register_taxonomy('subscription_tier', 'contentmoat', [
            'labels' => ['name' => 'Subscription Tiers'],
            'public' => true,
            'show_in_menu' => true
        ]);
    }

    public function add_admin_menu() {
        add_submenu_page('edit.php?post_type=contentmoat', 'Settings', 'Settings', 'manage_options', 'contentmoat-settings', [$this, 'render_settings_page']);
        add_submenu_page('edit.php?post_type=contentmoat', 'Members', 'Members', 'manage_options', 'contentmoat-members', [$this, 'render_members_page']);
        add_submenu_page('edit.php?post_type=contentmoat', 'Revenue', 'Revenue', 'manage_options', 'contentmoat-revenue', [$this, 'render_revenue_page']);
    }

    public function add_meta_boxes() {
        add_meta_box('contentmoat_settings', 'ContentMoat Settings', [$this, 'render_meta_box'], 'contentmoat', 'normal');
    }

    public function render_meta_box($post) {
        $is_exclusive = get_post_meta($post->ID, '_contentmoat_exclusive', true);
        $required_tier = get_post_meta($post->ID, '_contentmoat_tier', true);
        $tiers = get_terms(['taxonomy' => 'subscription_tier', 'hide_empty' => false]);
        ?>
        <label><input type="checkbox" name="contentmoat_exclusive" value="1" <?php checked($is_exclusive, 1); ?>> Make this content exclusive</label>
        <div style="margin-top: 10px;">
            <label>Required Tier: 
            <select name="contentmoat_tier">
                <option value="">-- None --</option>
                <?php foreach ($tiers as $tier): ?>
                    <option value="<?php echo $tier->term_id; ?>" <?php selected($required_tier, $tier->term_id); ?>><?php echo $tier->name; ?></option>
                <?php endforeach; ?>
            </select>
            </label>
        </div>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (isset($_POST['contentmoat_exclusive'])) {
            update_post_meta($post_id, '_contentmoat_exclusive', 1);
        } else {
            update_post_meta($post_id, '_contentmoat_exclusive', 0);
        }
        if (isset($_POST['contentmoat_tier'])) {
            update_post_meta($post_id, '_contentmoat_tier', $_POST['contentmoat_tier']);
        }
    }

    public function filter_exclusive_content($content) {
        if (get_post_type() !== 'contentmoat') return $content;
        $is_exclusive = get_post_meta(get_the_ID(), '_contentmoat_exclusive', true);
        if (!$is_exclusive) return $content;
        
        if (is_user_logged_in()) {
            $required_tier = get_post_meta(get_the_ID(), '_contentmoat_tier', true);
            $user_tiers = wp_get_post_terms(get_current_user_id(), 'subscription_tier');
            if (empty($required_tier) || in_array($required_tier, wp_list_pluck($user_tiers, 'term_id'))) {
                return $content;
            }
        }
        return '<div class="contentmoat-locked"><p>This content is exclusive to members. <a href="#" class="contentmoat-login-btn">Sign in or subscribe</a> to access.</p></div>';
    }

    public function render_login_form() {
        ob_start();
        ?>
        <form class="contentmoat-login-form" id="cm-login-form">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <p><a href="#">Create account</a></p>
        </form>
        <script>
        document.getElementById('cm-login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            jQuery.post(ajaxurl, {action: 'cm_login', email: this.email.value, password: this.password.value}, function(r) {
                if (r.success) location.reload();
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function render_member_dashboard() {
        if (!is_user_logged_in()) return '<p>Please log in.</p>';
        $user = wp_get_current_user();
        $tiers = wp_get_post_terms($user->ID, 'subscription_tier');
        return '<div class="contentmoat-dashboard"><h3>Welcome, ' . $user->display_name . '</h3><p>Active Subscriptions: ' . implode(', ', wp_list_pluck($tiers, 'name')) . '</p></div>';
    }

    public function render_subscription_plans() {
        $options = get_option('contentmoat_options', []);
        $plans = isset($options['plans']) ? $options['plans'] : [];
        ob_start();
        ?>
        <div class="contentmoat-plans">
            <?php foreach ($plans as $plan): ?>
                <div class="contentmoat-plan">
                    <h4><?php echo $plan['name']; ?></h4>
                    <p class="price"><?php echo $plan['price']; ?>/month</p>
                    <p><?php echo $plan['description']; ?></p>
                    <button class="contentmoat-subscribe-btn" data-plan="<?php echo $plan['id']; ?>">Subscribe</button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_login() {
        $email = $_POST['email'];
        $password = $_POST['password'];
        $user = wp_authenticate($email, $password);
        if (is_wp_error($user)) {
            wp_send_json_error(['message' => 'Invalid credentials']);
        }
        wp_set_current_user($user->ID);
        wp_set_auth_cookie($user->ID);
        wp_send_json_success();
    }

    public function handle_cancel_subscription() {
        if (!is_user_logged_in()) wp_send_json_error();
        $tier_id = $_POST['tier_id'];
        wp_remove_object_terms(get_current_user_id(), (int)$tier_id, 'subscription_tier');
        wp_send_json_success();
    }

    public function render_settings_page() {
        if (isset($_POST['contentmoat_save'])) {
            update_option('contentmoat_options', $_POST['contentmoat_options']);
        }
        $options = get_option('contentmoat_options', []);
        ?>
        <div class="wrap">
            <h1>ContentMoat Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th>Payment Provider</th>
                        <td><input type="text" name="contentmoat_options[payment_provider]" value="<?php echo isset($options['payment_provider']) ? $options['payment_provider'] : ''; ?>"></td>
                    </tr>
                    <tr>
                        <th>API Key</th>
                        <td><input type="password" name="contentmoat_options[api_key]" value="<?php echo isset($options['api_key']) ? $options['api_key'] : ''; ?>"></td>
                    </tr>
                </table>
                <button type="submit" name="contentmoat_save" class="button button-primary">Save Settings</button>
            </form>
        </div>
        <?php
    }

    public function render_members_page() {
        $subscribers = get_users(['role' => 'subscriber']);
        ?>
        <div class="wrap">
            <h1>Members</h1>
            <table class="widefat">
                <thead><tr><th>Member</th><th>Email</th><th>Tiers</th><th>Actions</th></tr></thead>
                <tbody>
                    <?php foreach ($subscribers as $sub): 
                        $tiers = wp_get_post_terms($sub->ID, 'subscription_tier');
                    ?>
                    <tr>
                        <td><?php echo $sub->display_name; ?></td>
                        <td><?php echo $sub->user_email; ?></td>
                        <td><?php echo implode(', ', wp_list_pluck($tiers, 'name')); ?></td>
                        <td><a href="#">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_revenue_page() {
        ?>
        <div class="wrap">
            <h1>Revenue Dashboard</h1>
            <p>Revenue analytics coming soon.</p>
        </div>
        <?php
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style('contentmoat-frontend', CONTENTMOAT_PLUGIN_URL . 'assets/frontend.css');
        wp_enqueue_script('contentmoat-frontend', CONTENTMOAT_PLUGIN_URL . 'assets/frontend.js', ['jquery']);
        wp_localize_script('contentmoat-frontend', 'ajaxurl', admin_url('admin-ajax.php'));
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('contentmoat-admin', CONTENTMOAT_PLUGIN_URL . 'assets/admin.css');
    }

    public function activate_plugin() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'contentmoat_subscriptions';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            tier_id bigint(20) unsigned NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function deactivate_plugin() {
        // Cleanup if needed
    }
}

new ContentMoat();
?>