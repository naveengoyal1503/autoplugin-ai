<?php
/*
Plugin Name: Affiliate Deal Aggregator
Plugin URI: https://example.com/affiliate-deal-aggregator
Description: Aggregate niche affiliate coupons with easy management and analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=Affiliate_Deal_Aggregator.php
License: GPL2
*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class AffiliateDealAggregator {
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'ada_deals';

        register_activation_hook(__FILE__, array($this, 'install'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_shortcode('affiliate_deals', array($this, 'shortcode_display'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function install() {
        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            affiliate_url varchar(255) NOT NULL,
            expiration_date date DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    public function admin_menu() {
        add_menu_page(
            'Affiliate Deals', 
            'Affiliate Deals', 
            'manage_options', 
            'ada_deals', 
            array($this, 'admin_page'), 
            'dashicons-tickets-alt', 
            80
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_style('ada_styles', plugin_dir_url(__FILE__) . 'ada-styles.css');
    }

    public function admin_page() {
        global $wpdb;
        $action = isset($_POST['ada_action']) ? sanitize_text_field($_POST['ada_action']) : '';

        if ($action === 'add_deal') {
            check_admin_referer('ada_add_deal_nonce');
            $title = sanitize_text_field($_POST['title']);
            $desc = sanitize_textarea_field($_POST['description']);
            $url = esc_url_raw($_POST['affiliate_url']);
            $exp = sanitize_text_field($_POST['expiration_date']);

            $wpdb->insert(
                $this->table_name,
                [
                    'title' => $title,
                    'description' => $desc,
                    'affiliate_url' => $url,
                    'expiration_date' => $exp ? $exp : null
                ]
            );
            echo '<div class="updated"><p>Deal added successfully.</p></div>';
        }

        $deals = $wpdb->get_results("SELECT * FROM $this->table_name ORDER BY expiration_date ASC, created_at DESC");

        ?>
        <div class="wrap">
            <h1>Affiliate Deal Aggregator</h1>
            <form method="post">
                <?php wp_nonce_field('ada_add_deal_nonce'); ?>
                <input type="hidden" name="ada_action" value="add_deal" />
                <table class="form-table">
                    <tr>
                        <th><label for="title">Deal Title</label></th>
                        <td><input name="title" type="text" id="title" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="description">Description</label></th>
                        <td><textarea name="description" id="description" cols="30" rows="3" required class="large-text"></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="affiliate_url">Affiliate URL</label></th>
                        <td><input name="affiliate_url" type="url" id="affiliate_url" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th><label for="expiration_date">Expiration Date</label></th>
                        <td><input name="expiration_date" type="date" id="expiration_date" class="regular-text"></td>
                    </tr>
                </table>
                <?php submit_button('Add Deal'); ?>
            </form>

            <h2>Current Deals</h2>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr><th>Title</th><th>Description</th><th>Affiliate URL</th><th>Expiration</th></tr></thead>
                <tbody>
                <?php
                if ($deals) {
                    foreach ($deals as $deal) {
                        $expiring = $deal->expiration_date ? esc_html($deal->expiration_date) : 'No Expiry';
                        $url_display = esc_url($deal->affiliate_url);
                        echo "<tr><td>" . esc_html($deal->title) . "</td><td>" . esc_html($deal->description) . "</td><td><a href='$url_display' target='_blank' rel='nofollow noopener'>Link</a></td><td>$expiring</td></tr>";
                    }
                } else {
                    echo "<tr><td colspan='4'>No deals found.</td></tr>";
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function shortcode_display($atts) {
        global $wpdb;
        $today = date('Y-m-d');
        $deals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE expiration_date IS NULL OR expiration_date >= %s ORDER BY expiration_date ASC, created_at DESC",
            $today
        ));

        if (!$deals) return '<p>No affiliate deals currently available. Please check back later.</p>';

        ob_start();
        echo '<div class="ada-deal-list">';
        foreach ($deals as $deal) {
            $expiry_text = $deal->expiration_date ? 'Expires on ' . esc_html($deal->expiration_date) : 'No Expiry';
            $url = esc_url($deal->affiliate_url);
            echo "<div class='ada-deal-item' style='border:1px solid #ccc;padding:10px;margin-bottom:10px;'>";
            echo "<h3><a href='$url' target='_blank' rel='nofollow noopener'>$deal->title</a></h3>";
            echo "<p>" . esc_html($deal->description) . "</p>";
            echo "<small>$expiry_text</small>";
            echo "</div>";
        }
        echo '</div>';
        return ob_get_clean();
    }
}

new AffiliateDealAggregator();
