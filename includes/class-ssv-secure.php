<?php
if (!defined('ABSPATH')) exit;

class SSV_Secure {

    private $dir;

    public function __construct() {

        $this->dir = WP_CONTENT_DIR . '/secure-slides/';

        add_action('init', [$this,'serve']);
    }

    public function serve() {

        if (!isset($_GET['ssv_slide'])) return;

        if (!is_user_logged_in()) wp_die('Login required');

        $slide = sanitize_text_field($_GET['ssv_slide']);
        $post  = intval($_GET['post']);

        $file = $this->dir . $post . '/' . $slide . '.webp';

        if (!file_exists($file)) wp_die('Not found');

        header('Content-Type: image/webp');
        header('Cache-Control: no-store');

        readfile($file);
        exit;
    }
}
