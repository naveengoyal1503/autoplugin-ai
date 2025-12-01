<?php
/*
Plugin Name: ContentVault Pro
Plugin URI: https://contentvaultpro.com
Description: Premium membership and content gating plugin for WordPress
Version: 1.0.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=ContentVault_Pro.php
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: contentvault-pro
Domain Path: /languages
*/

if (!defined('ABSPATH')) exit;

define('CVP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CVP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CVP_VERSION', '1.0.0');

class ContentVaultPro {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('init', array($this, 'register_custom_post_types'));
        add_shortcode('cvp_membership_form', array($this, 'render_membership_form'));
        add_shortcode('cvp_gated_content', array($this, 'render_gated_content'));
        add_filter('the_content', array($this, 'gate_post_content'));
    }
    
    public function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cvp_members (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            tier_id bigint(20) NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'active',
            subscription_start datetime DEFAULT CURRENT_TIMESTAMP,
            subscription_end datetime,
            payment_method varchar(50),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY tier_id (tier_id)
        ) $charset_collate;";
        
        $sql .= "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}cvp_tiers (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description longtext,
            price decimal(10,2) NOT NULL,
            billing_period varchar(20) DEFAULT 'monthly',
            features longtext,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        add_option('cvp_db_version', CVP_VERSION);
        add_option('cvp_license', 'free');
    }
    
    public function deactivate() {
        wp_clear_scheduled_hook('cvp_check_subscriptions');
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'ContentVault Pro',
            'ContentVault Pro',
            'manage_options',
            'cvp-dashboard',
            array($this, 'render_dashboard'),
            'dashicons-lock'
        );
        
        add_submenu_page(
            'cvp-dashboard',
            'Membership Tiers',
            'Membership Tiers',
            'manage_options',
            'cvp-tiers',
            array($this, 'render_tiers_page')
        );
        
        add_submenu_page(
            'cvp-dashboard',
            'Members',
            'Members',
            'manage_options',
            'cvp-members',
            array($this, 'render_members_page')
        );
        
        add_submenu_page(
            'cvp-dashboard',
            'Settings',
            'Settings',
            'manage_options',
            'cvp-settings',
            array($this, 'render_settings_page')
        );
    }
    
    public function register_settings() {
        register_setting('cvp_settings', 'cvp_stripe_key');
        register_setting('cvp_settings', 'cvp_paypal_email');
        register_setting('cvp_settings', 'cvp_redirect_url');
    }
    
    public function register_custom_post_types() {
        register_post_type('cvp_gated_post', array(
            'label' => 'Gated Content',
            'public' => true,
            'supports' => array('title', 'editor', 'excerpt'),
            'has_archive' => false,
            'show_in_rest' => true
        ));
    }
    
    public function render_dashboard() {
        global $wpdb;
        $active_members = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cvp_members WHERE status = 'active'");
        $total_tiers = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}cvp_tiers");
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Dashboard</h1>
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-top: 20px;">
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>Active Members</h3>
                    <p style="font-size: 32px; font-weight: bold;"><?php echo $active_members; ?></p>
                </div>
                <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
                    <h3>Membership Tiers</h3>
                    <p style="font-size: 32px; font-weight: bold;"><?php echo $total_tiers; ?></p>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function render_tiers_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1>Membership Tiers</h1>
            <a href="<?php echo admin_url('admin.php?page=cvp-tiers&action=new'); ?>" class="button button-primary">Add New Tier</a>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Tier Name</th>
                        <th>Price</th>
                        <th>Billing Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $tiers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cvp_tiers ORDER BY id DESC");
                    foreach ($tiers as $tier) {
                        echo '<tr>';
                        echo '<td>' . esc_html($tier->name) . '</td>';
                        echo '<td>$' . esc_html($tier->price) . '</td>';
                        echo '<td>' . esc_html($tier->billing_period) . '</td>';
                        echo '<td><a href="#" class="button">Edit</a> <a href="#" class="button">Delete</a></td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function render_members_page() {
        global $wpdb;
        ?>
        <div class="wrap">
            <h1>Members</h1>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Tier</th>
                        <th>Status</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $members = $wpdb->get_results("
                        SELECT m.*, u.user_login, t.name as tier_name
                        FROM {$wpdb->prefix}cvp_members m
                        JOIN {$wpdb->prefix}users u ON m.user_id = u.ID
                        JOIN {$wpdb->prefix}cvp_tiers t ON m.tier_id = t.id
                        ORDER BY m.created_at DESC
                    ");
                    foreach ($members as $member) {
                        echo '<tr>';
                        echo '<td>' . esc_html($member->user_login) . '</td>';
                        echo '<td>' . esc_html($member->tier_name) . '</td>';
                        echo '<td>' . esc_html($member->status) . '</td>';
                        echo '<td>' . esc_html($member->subscription_start) . '</td>';
                        echo '<td>' . esc_html($member->subscription_end) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>ContentVault Pro Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('cvp_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="cvp_stripe_key">Stripe API Key</label></th>
                        <td><input type="password" id="cvp_stripe_key" name="cvp_stripe_key" value="<?php echo esc_attr(get_option('cvp_stripe_key')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cvp_paypal_email">PayPal Email</label></th>
                        <td><input type="email" id="cvp_paypal_email" name="cvp_paypal_email" value="<?php echo esc_attr(get_option('cvp_paypal_email')); ?>" class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="cvp_redirect_url">Redirect URL After Purchase</label></th>
                        <td><input type="url" id="cvp_redirect_url" name="cvp_redirect_url" value="<?php echo esc_attr(get_option('cvp_redirect_url')); ?>" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function render_membership_form() {
        global $wpdb;
        $tiers = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}cvp_tiers ORDER BY price ASC");
        
        ob_start();
        ?>
        <div class="cvp-membership-form">
            <h3>Choose Your Plan</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                <?php foreach ($tiers as $tier): ?>
                    <div style="border: 2px solid #ddd; padding: 20px; border-radius: 5px; text-align: center;">
                        <h4><?php echo esc_html($tier->name); ?></h4>
                        <p style="font-size: 24px; font-weight: bold;">$<?php echo esc_html($tier->price); ?>/<?php echo esc_html($tier->billing_period); ?></p>
                        <p><?php echo esc_html($tier->description); ?></p>
                        <button class="button button-primary cvp-subscribe-btn" data-tier-id="<?php echo esc_attr($tier->id); ?>">Subscribe Now</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.cvp-subscribe-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        var tierId = this.getAttribute('data-tier-id');
                        if (!<?php echo (is_user_logged_in() ? 'true' : 'false'); ?>) {
                            window.location.href = '<?php echo wp_login_url(); ?>';
                        } else {
                            alert('Subscription for tier ' + tierId + ' initiated');
                        }
                    });
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }
    
    public function render_gated_content($atts) {
        $atts = shortcode_atts(array('tier_id' => 0), $atts);
        
        if (!is_user_logged_in()) {
            return '<p><a href="' . wp_login_url() . '">Log in to access this content.</a></p>';
        }
        
        global $wpdb;
        $user_id = get_current_user_id();
        $tier_id = (int) $atts['tier_id'];
        
        $member = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}cvp_members WHERE user_id = %d AND tier_id = %d AND status = 'active'",
            $user_id,
            $tier_id
        ));
        
        if (!$member) {
            return '<p>You do not have access to this content. <a href="#">Subscribe now</a></p>';
        }
        
        return 'Content unlocked!';
    }
    
    public function gate_post_content($content) {
        if (get_post_type() === 'cvp_gated_post' && !is_user_logged_in()) {
            return '<p><a href="' . wp_login_url() . '">Log in to read this article.</a></p>';
        }
        return $content;
    }
}

ContentVaultPro::get_instance();
?>