<?php

if (!defined('ABSPATH')) exit;

class SSV_Notes
{

    private static $instance = null;
    private $table;

    public static function instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        global $wpdb;
        $this->table = $wpdb->prefix . 'ssv_ld_notes';

        register_activation_hook(SSV_DIR, [$this, 'ssv_activate']);

        add_action('wp_enqueue_scripts', [$this, 'ssv_enqueue_assets']);
        //add_action('wp_footer', [$this, 'ssv_render_widget']);
        add_filter('the_content', [$this, 'ssv_render_widget']);

        add_action('wp_ajax_ssv_save_note', [$this, 'ssv_save_note']);
        add_action('wp_ajax_ssv_get_note', [$this, 'ssv_get_note']);
    }

    public function ssv_activate()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$this->table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            lesson_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED DEFAULT 0,
            note_content LONGTEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_lesson (user_id, lesson_id),
            KEY lesson_id (lesson_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function ssv_enqueue_assets()
    {
        if (!is_user_logged_in() || !is_singular('sfwd-lessons')) return;

        wp_enqueue_style('ssv-notes-css', SSV_URL . 'assets/css/ssv-notes.css', [], SSV_VERSION);

        wp_enqueue_script('ssv-notes-js', SSV_URL . 'assets/js/ssv-notes.js', ['jquery'], SSV_VERSION, true);

        wp_localize_script('ssv-notes-js', 'SSVNotesData', [
            'ajax_url'  => admin_url('admin-ajax.php'),
            'nonce'     => wp_create_nonce('ssv_notes_nonce'),
            'lesson_id' => get_the_ID(),
            'course_id' => function_exists('learndash_get_course_id') ? learndash_get_course_id(get_the_ID()) : 0
        ]);
    }

    public function ssv_render_widget($content)
    {
        // Prevent multiple renders (Elementor / loops)
        static $rendered = false;

        if ($rendered) {
            return $content;
        }

        if (!is_user_logged_in() || !is_singular('sfwd-lessons')) {
            return $content;
        }

        $rendered = true;

        ob_start();
?>
        <div id="ssv-note-widget">
            <button id="ssv-note-toggle">
                <i class="ri-sticky-note-line"></i>
                <span>Notes</span>
            </button>

            <div id="ssv-note-panel">
                <div class="ssv-note-header">
                    <span><i class="ri-sticky-note-fill"></i> My Lesson Note</span>
                    <span class="ssv-note-close"><i class="ri-close-line"></i></span>
                </div>

                <textarea id="ssv-note-content" placeholder="Write your notes here..."></textarea>

                <div class="ssv-note-footer">
                    <span id="ssv-note-status">Saved</span>
                    <button id="ssv-note-save">
                        <i class="ri-save-line"></i> Save
                    </button>
                </div>
            </div>
        </div>
<?php
        $widget = ob_get_clean();

        // Append AFTER lesson content
        return $content . $widget;
    }

    public function ssv_get_note()
    {
        check_ajax_referer('ssv_notes_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        global $wpdb;
        $user_id   = get_current_user_id();
        $lesson_id = intval($_POST['lesson_id']);

        $note = $wpdb->get_var($wpdb->prepare(
            "SELECT note_content FROM {$this->table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));

        wp_send_json_success(['content' => $note ?: '']);
    }

    public function ssv_save_note()
    {
        check_ajax_referer('ssv_notes_nonce', 'nonce');

        if (!is_user_logged_in()) wp_send_json_error();

        global $wpdb;

        $user_id   = get_current_user_id();
        $lesson_id = intval($_POST['lesson_id']);
        $course_id = intval($_POST['course_id']);
        $content   = wp_kses_post($_POST['content']);

        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table} WHERE user_id = %d AND lesson_id = %d",
            $user_id,
            $lesson_id
        ));

        if ($exists) {
            $wpdb->update(
                $this->table,
                ['note_content' => $content],
                ['id' => $exists],
                ['%s'],
                ['%d']
            );
        } else {
            $wpdb->insert(
                $this->table,
                [
                    'user_id'     => $user_id,
                    'lesson_id'   => $lesson_id,
                    'course_id'   => $course_id,
                    'note_content' => $content
                ],
                ['%d', '%d', '%d', '%s']
            );
        }

        wp_send_json_success(['message' => 'Saved']);
    }
}
