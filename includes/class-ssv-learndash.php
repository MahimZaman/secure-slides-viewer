<?php
if (!defined('ABSPATH')) exit;

class SSV_Learndash
{
    public function __construct()
    {
        // add_action('learndash-course-navigation-after', [$this, 'navigation']);

        // add_action('wp_enqueue_scripts', [$this, 'assets']);
    }

    public function assets()
    {
        wp_enqueue_style('ssv_learndash_style', SSV_URL . 'assets/css/ssv_learndash.css', [], SSV_VERSION);
    }

    public function navigation()
    {
        if (!is_singular('sfwd-lessons')) return;

        global $post;

        $slides = $this->ssv_get_slides_from_lesson($post->ID);

        if (empty($slides)) return;

        echo '<div class="ssv-doc-box">';
        echo '<h3>📄 Lesson Resources</h3>';
        echo '<div class="ssv-doc-list">';

        foreach ($slides as $slide) {

            $link = get_permalink($post->ID); // same lesson page

            echo '<a href="' . esc_url($link) . '" class="ssv-doc-item">';
            echo '<span class="ssv-doc-icon"><i class="ri-file-pdf-line"></i></span>';
            echo '<span class="ssv-doc-title">' . esc_html($slide->post_title) . '</span>';
            echo '</a>';
        }

        echo '</div></div>';
    }

    function ssv_get_slides_from_lesson($lesson_id)
    {
        $post = get_post($lesson_id);
        if (!$post) return [];

        $ids = $this->ssv_get_slide_ids_from_content($post->post_content);

        if (empty($ids)) return [];

        return get_posts([
            'post_type' => 'ssv_slides',
            'post__in'  => $ids,
            'orderby'   => 'post__in',
            'numberposts' => -1
        ]);
    }

    function ssv_get_slide_ids_from_content($content)
    {
        $ids = [];

        if (preg_match_all('/\[ssv_slide\s+id="(\d+)"\]/', $content, $matches)) {
            $ids = array_map('intval', $matches[1]);
        }

        return $ids;
    }
}
