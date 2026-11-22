<?php

defined('ABSPATH') || exit;

class SSV_Ajax
{
    public function __construct()
    {
        add_action('wp_ajax_ssv_check_pdf_status', [$this, 'ssv_check_pdf_status']);
        add_action('wp_ajax_ssv_save_page', [$this, 'ssv_save_page']);
        add_action('wp_ajax_nopriv_ssv_save_page', [$this, 'ssv_save_page']);
    }

    public function ssv_save_page()
    {
        $user_id = sanitize_text_field($_POST['user_id']);
        $post_id = sanitize_text_field($_POST['post_id']);
        $id = sanitize_text_field($_POST['id']);
        $currentPage = intval($_POST['page_num']);

        $resume_page = is_array(get_post_meta($post_id, 'resume_data', true)) ? get_post_meta($post_id, 'resume_data', true) : [];
        $resume_page[$user_id][$id] = $currentPage;
        update_post_meta($post_id, 'resume_data', $resume_page);
        wp_send_json_success([
            'page_num' => $currentPage
        ]);
    }

    public function ssv_check_pdf_status()
    {
        if (!isset($_POST['post_id'])) {
            wp_send_json_error();
        }

        $post_id = intval($_POST['post_id']);
        $status  = get_post_meta($post_id, '_ssv_pdf_status', true);

        wp_send_json_success([
            'status' => $status
        ]);
    }
}
