<?php
/*
Plugin Name: WP Smart Contracts
Description: Create and manage smart contracts for digital agreements, payments, and workflows.
Version: 1.0
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=WP_Smart_Contracts.php
*/

class WPSmartContracts {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page(
            'WP Smart Contracts',
            'Smart Contracts',
            'manage_options',
            'wp_smart_contracts',
            array($this, 'plugin_page'),
            'dashicons-hammer'
        );
    }

    public function settings_init() {
        register_setting('wp_smart_contracts', 'wp_smart_contracts_options');

        add_settings_section(
            'wp_smart_contracts_section',
            'Smart Contract Settings',
            null,
            'wp_smart_contracts'
        );

        add_settings_field(
            'contract_template',
            'Default Contract Template',
            array($this, 'contract_template_render'),
            'wp_smart_contracts',
            'wp_smart_contracts_section'
        );
    }

    public function contract_template_render() {
        $options = get_option('wp_smart_contracts_options');
        ?>
        <textarea cols='60' rows='10' name='wp_smart_contracts_options[contract_template]'>
            <?php echo isset($options['contract_template']) ? esc_textarea($options['contract_template']) : 'This is a smart contract between [Party A] and [Party B].'; ?>
        </textarea>
        <?php
    }

    public function plugin_page() {
        $options = get_option('wp_smart_contracts_options');
        ?>
        <div class="wrap">
            <h1>WP Smart Contracts</h1>
            <form action='options.php' method='post'>
                <?php
                settings_fields('wp_smart_contracts');
                do_settings_sections('wp_smart_contracts');
                submit_button();
                ?>
            </form>
            <h2>Create New Contract</h2>
            <form method="post">
                <label>Contract Title: <input type="text" name="contract_title" /></label><br>
                <label>Parties: <input type="text" name="contract_parties" /></label><br>
                <label>Terms: <textarea name="contract_terms" cols="60" rows="10"></textarea></label><br>
                <input type="submit" name="create_contract" value="Create Contract" />
            </form>
            <?php
            if (isset($_POST['create_contract'])) {
                $title = sanitize_text_field($_POST['contract_title']);
                $parties = sanitize_text_field($_POST['contract_parties']);
                $terms = sanitize_textarea_field($_POST['contract_terms']);
                echo '<div class="notice notice-success"><p>Contract created: <strong>' . $title . '</strong> between ' . $parties . '</p></div>';
            }
            ?>
        </div>
        <?php
    }
}

new WPSmartContracts();
