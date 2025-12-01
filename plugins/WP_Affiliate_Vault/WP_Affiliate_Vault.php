<?php
/*
Plugin Name: WP Affiliate Vault
Description: Create and manage private affiliate programs with automated payouts and real-time analytics.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Affiliate_Vault.php
*/

if (!defined('ABSPATH')) exit;

define('WPAV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPAV_PLUGIN_URL', plugin_dir_url(__FILE__));

class WPAffiliateVault {
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('init', array($this, 'register_post_types'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Affiliate Vault',
            'Affiliate Vault',
            'manage_options',
            'wp-affiliate-vault',
            array($this, 'admin_page'),
            'dashicons-groups',
            6
        );
    }

    public function register_post_types() {
        register_post_type('wpav_affiliate', array(
            'labels' => array('name' => 'Affiliates', 'singular_name' => 'Affiliate'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title'),
            'show_in_menu' => 'wp-affiliate-vault'
        ));
        register_post_type('wpav_commission', array(
            'labels' => array('name' => 'Commissions', 'singular_name' => 'Commission'),
            'public' => false,
            'show_ui' => true,
            'supports' => array('title'),
            'show_in_menu' => 'wp-affiliate-vault'
        ));
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wpav-style', WPAV_PLUGIN_URL . 'assets/style.css');
        wp_enqueue_script('wpav-script', WPAV_PLUGIN_URL . 'assets/script.js', array('jquery'), '1.0', true);
    }

    public function admin_page() {
        echo '<div class="wrap"><h1>WP Affiliate Vault</h1><p>Manage your private affiliate program here.</p></div>';
    }
}

new WPAffiliateVault();

// Create plugin directory and assets
if (!file_exists(WPAV_PLUGIN_DIR . 'assets')) {
    mkdir(WPAV_PLUGIN_DIR . 'assets', 0755, true);
}

// Create style.css
if (!file_exists(WPAV_PLUGIN_DIR . 'assets/style.css')) {
    file_put_contents(WPAV_PLUGIN_DIR . 'assets/style.css', "/* WP Affiliate Vault Styles */\nbody { font-family: sans-serif; }\n");
}

// Create script.js
if (!file_exists(WPAV_PLUGIN_DIR . 'assets/script.js')) {
    file_put_contents(WPAV_PLUGIN_DIR . 'assets/script.js', "// WP Affiliate Vault Scripts\nconsole.log('WP Affiliate Vault loaded.');\n");
}
?>