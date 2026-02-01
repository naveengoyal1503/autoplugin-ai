/*
Author: Auto Plugin Factory
Author URI: https://automation.bhandarum.in/generated-plugins/tracker.php?plugin=AI_Content_Optimizer_Pro.php
*/
<?php
/**
 * Plugin Name: AI Content Optimizer Pro
 * Plugin URI: https://example.com/ai-content-optimizer
 * Description: Analyzes and optimizes your WordPress content for SEO and readability. Free version with premium upgrades.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * Text Domain: ai-content-optimizer
 * Requires at least: 5.0
 * Tested up to: 6.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const $ACO_VERSION = '1.0.0';
const $ACO_PLUGIN_FILE = __FILE__;

// Freemius integration (requires Freemius library - download and include)
if ( file_exists( plugin_dir_path( __FILE__ ) . 'freemius/start.php' ) ) {
    require_once plugin_dir_path( __FILE__ ) . 'freemius/start.php';
    if ( ! function_exists( 'ai_content_optimizer_freemius' ) ) {
        function ai_content_optimizer_freemius() {
            $freemius = Freemius::create( 'freemius_id', $ACO_VERSION, 'ai-content-optimizer' );
            return $freemius;
        }
    }
}

class AIContentOptimizer {
    public function __construct() {
        add_action( 'init', array( $this, 'init' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post', array( $this, 'save_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_aco_analyze', array( $this, 'ajax_analyze' ) );
        add_action( 'wp_ajax_aco_upgrade', array( $this, 'ajax_upgrade' ) );
    }

    public function init() {
        load_plugin_textdomain( 'ai-content-optimizer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    }

    public function enqueue_scripts( $hook ) {
        if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
            return;
        }
        wp_enqueue_script( 'aco-admin', plugin_dir_url( __FILE__ ) . 'admin.js', array( 'jquery' ), $ACO_VERSION, true );
        wp_localize_script( 'aco-admin', 'aco_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'aco_nonce' ),
            'is_premium' => $this->is_premium(),
        ) );
        wp_enqueue_style( 'aco-admin', plugin_dir_url( __FILE__ ) . 'admin.css', array(), $ACO_VERSION );
    }

    public function add_meta_box() {
        add_meta_box( 'aco-optimizer', __( 'AI Content Optimizer', 'ai-content-optimizer' ), array( $this, 'meta_box_html' ), 'post', 'side' );
    }

    public function meta_box_html( $post ) {
        wp_nonce_field( 'aco_meta_nonce', 'aco_nonce' );
        $content = get_post_field( 'post_content', $post->ID );
        echo '<div id="aco-results">';
        echo '<button id="aco-analyze" class="button button-primary">' . __( 'Analyze Content', 'ai-content-optimizer' ) . '</button>';
        echo '<div id="aco-score"></div>';
        echo '<div id="aco-suggestions"></div>';
        if ( ! $this->is_premium() ) {
            echo '<div class="aco-upgrade"><p>' . __( 'Upgrade to Premium for AI rewriting and bulk optimization!', 'ai-content-optimizer' ) . '</p><button id="aco-upgrade" class="button">Upgrade Now</button></div>';
        }
        echo '</div>';
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['aco_nonce'] ) || ! wp_verify_nonce( $_POST['aco_nonce'], 'aco_meta_nonce' ) ) {
            return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        // Save any meta if needed
    }

    public function ajax_analyze() {
        check_ajax_referer( 'aco_nonce', 'nonce' );
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die();
        }

        $post_id = intval( $_POST['post_id'] );
        $content = get_post_field( 'post_content', $post_id );

        // Basic free analysis: word count, readability (Flesch score approx), keyword density
        $word_count = str_word_count( strip_tags( $content ) );
        $sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
        $sentence_count = count( $sentences );
        $readability = 206.835 - 1.015 * ( $word_count / ( $sentence_count ?: 1 ) ) - 84.6 * ( $this->get_avg_syllables( $content ) / ( $word_count ?: 1 ) );
        $score = min( 100, max( 0, 80 + ( $readability / 100 ) * 20 ) );

        $suggestions = array();
        if ( $word_count < 300 ) $suggestions[] = 'Add more content (aim for 1000+ words for SEO).';
        if ( $readability < 60 ) $suggestions[] = 'Improve readability: shorten sentences.';

        $response = array(
            'score' => round( $score, 1 ),
            'word_count' => $word_count,
            'readability' => round( $readability, 1 ),
            'suggestions' => $suggestions,
        );

        if ( isset( $_POST['rewrite'] ) && $this->is_premium() ) {
            // Premium: Simulate AI rewrite (in real: API call to OpenAI)
            $response['rewrite'] = $this->mock_ai_rewrite( $content );
        }

        wp_send_json_success( $response );
    }

    public function ajax_upgrade() {
        check_ajax_referer( 'aco_nonce', 'nonce' );
        if ( class_exists( 'Freemius' ) ) {
            ai_content_optimizer_freemius()->show_upgrade_after_action();
        } else {
            wp_send_json_error( 'Freemius not available.' );
        }
    }

    private function is_premium() {
        // Check Freemius license or option
        if ( class_exists( 'Freemius' ) && ai_content_optimizer_freemius()->is_premium() ) {
            return true;
        }
        return false;
    }

    private function get_avg_syllables( $text ) {
        $words = explode( ' ', strip_tags( $text ) );
        $syllables = 0;
        foreach ( $words as $word ) {
            $syllables += preg_match_all( '/[aeiouy]{2}/', strtolower( $word ) ) + preg_match( '/[aeiouy]$/', strtolower( $word ) ) ? 1 : 0;
        }
        return $syllables / ( count( $words ) ?: 1 );
    }

    private function mock_ai_rewrite( $content ) {
        // Mock premium rewrite
        return '<p>Premium AI-rewritten content: ' . substr( $content, 0, 100 ) . '... (Full rewrite available in Pro)</p>';
    }
}

new AIContentOptimizer();

// Admin JS placeholder - save as admin.js
/*
$(document).ready(function() {
    $('#aco-analyze').click(function() {
        $.post(aco_ajax.ajax_url, {
            action: 'aco_analyze',
            nonce: aco_ajax.nonce,
            post_id: $('#post_ID').val()
        }, function(resp) {
            if (resp.success) {
                $('#aco-score').html('<strong>Score: ' + resp.data.score + '%</strong>');
                $('#aco-suggestions').html(resp.data.suggestions.join('<br>'));
            }
        });
    });
    $('#aco-upgrade').click(function() {
        $.post(aco_ajax.ajax_url, {
            action: 'aco_upgrade',
            nonce: aco_ajax.nonce
        });
    });
});
*/
// Admin CSS placeholder - save as admin.css
/*
#aco-results { margin: 10px 0; }
#aco-score { font-size: 24px; color: green; }
.aco-upgrade { background: #fff3cd; padding: 10px; border-radius: 4px; }
*/