<?php
if (!defined('ABSPATH')) exit;

class SSV_CPT
{

    public function __construct()
    {
        add_action('init', [$this, 'register']);
        add_filter('manage_ssv_slides_posts_columns', [$this, 'ssv_add_shortcode_column']);
        add_action('manage_ssv_slides_posts_custom_column', [$this, 'ssv_render_shortcode_column'], 10, 2);
        add_action('init', [$this, 'ssv_register_pdf_endpoint']);
        add_filter('query_vars', [$this, 'ssv_register_query_vars']);
        add_action('template_redirect', [$this, 'ssv_secure_pdf_stream']);
        add_action('init', [$this, 'ssv_start_session'], 1);
    }

    function ssv_start_session()
    {
        if (!session_id()) {
            session_start();
        }
    }

    function ssv_register_pdf_endpoint()
    {
        add_rewrite_rule(
            '^ssv-secure-pdf/?$',
            'index.php?ssv_secure_pdf=1',
            'top'
        );
    }

    function ssv_secure_pdf_stream()
    {
        if (!get_query_var('ssv_secure_pdf')) return;
        if (!isset($_GET['token'])) wp_die('Invalid request');        
        
        if (!session_id()) {
            session_start();
        }

        $token = sanitize_text_field($_GET['token']);
        $data  = get_transient('ssv_token_' . $token);

        if (!$data) wp_die('Token expired');

        $current_hash = hash('sha256', session_id() . $_SERVER['HTTP_USER_AGENT']);

        if ($data['session'] !== $current_hash) {
            wp_die('Session mismatch');
        }

        if ($data['used'] && $data['session'] !== $current_hash) {
            wp_die('Token already used');
        }

        // Mark used but allow same session
        $data['used'] = true;
        set_transient('ssv_token_' . $token, $data, 10 * MINUTE_IN_SECONDS);

        $post_id = intval($data['post_id']);
        $filename = get_post_meta($post_id, '_secure_slide_file', true);
        if (!$filename) wp_die('File missing');

        $base = wp_normalize_path(WP_CONTENT_DIR) . '/secure-slides/ssv_slides/' . $post_id;
        $path = trailingslashit($base) . $filename;

        if (!file_exists($path)) wp_die('File not found');

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');

        readfile($path);
        exit;
    }


    function ssv_register_query_vars($vars)
    {
        $vars[] = 'ssv_secure_pdf';
        return $vars;
    }

    function ssv_add_shortcode_column($columns)
    {
        unset($columns['date']);
        $columns['ssv_shortcode'] = 'Shortcode';
        $columns['date'] = 'Published';
        return $columns;
    }

    function ssv_render_shortcode_column($column, $post_id)
    {
        if ($column !== 'ssv_shortcode') return;

        $shortcode = '[ssv_slide id="' . $post_id . '"]';

        echo '<div class="ssv-shortcode-wrap">';
        echo '<code class="ssv-shortcode-text">' . esc_html($shortcode) . '</code>';
        echo '<button type="button" class="ssv-copy-btn" data-code="' . esc_attr($shortcode) . '">
            <i class="ri-file-copy-line"></i>
          </button>';
        echo '</div>';
    }



    public function register()
    {

        register_post_type('ssv_slides', [
            'label' => 'Secure Slides',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title'],
            'menu_icon' => 'dashicons-media-document'
        ]);
    }
}
