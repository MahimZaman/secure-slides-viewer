<?php
if (!defined('ABSPATH')) exit;

class SSV_Admin
{

    public function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'ssv_enqueue_assets']);
        add_action('add_meta_boxes', [$this, 'register_secure_slides_metabox']);
        add_action('save_post', [$this, 'handle_secure_slide_on_save'], 20, 3);
        add_action('post_edit_form_tag', [$this, 'add_multipart_to_post_form']);
        add_action('ssv_process_secure_slide_conversion', [$this, 'ssv_process_secure_slide_conversion_callback']);
    }

    public function add_multipart_to_post_form()
    {

        global $post;

        if (!$post) return;

        if (in_array($post->post_type, ['post', 'ssv_slides'])) {
            echo ' enctype="multipart/form-data"';
        }
    }


    public function ssv_enqueue_assets($hook)
    {

        wp_enqueue_media();


        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
            [],
            SSV_VERSION
        );

        wp_enqueue_style(
            'ssv-admin-css',
            SSV_URL . 'assets/css/ssv-admin.css'
        );

        wp_enqueue_script(
            'ssv-admin-js',
            SSV_URL . 'assets/js/ssv-admin.js',
            ['jquery'],
            SSV_VERSION,
            true
        );

        wp_enqueue_style(
            'ssv-metabox-css',
            SSV_URL . 'assets/css/ssv_metabox.css',
            [],
            SSV_VERSION
        );

        wp_enqueue_script(
            'ssv-metabox-js',
            SSV_URL . 'assets/js/ssv_metabox.js',
            ['jquery'],
            SSV_VERSION,
            true
        );

        wp_enqueue_script(
            'ssv-pdf-status',
            SSV_URL . 'assets/js/ssv_pdf_status.js',
            ['jquery'],
            SSV_VERSION,
            true
        );

        wp_localize_script('ssv-pdf-status', 'ssvPdfStatus', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'post_id'  => get_the_ID()
        ]);

        wp_enqueue_style(
            'ssv-pdf-status-style',
            SSV_URL . 'assets/css/ssv_pdf_status.css'
        );


        wp_localize_script('ssv-metabox-js', 'ssv_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('secure_slides_nonce')
        ]);

        wp_localize_script('ssv-admin-js', 'ssvData', [
            'ajax'  => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ssv_nonce')
        ]);
    }

    public function render_secure_slides_metabox($post)
    {

        wp_nonce_field('secure_slides_nonce', 'secure_slides_nonce_field');

        $existing = get_post_meta($post->ID, '_secure_slide_file', true);
        $pdf_url  = $existing
            ? content_url("secure-slides/{$post->post_type}/{$post->ID}/{$existing}")
            : '';
        $pdf_guid  = $existing
            ? trailingslashit(wp_normalize_path(WP_CONTENT_DIR)) . "secure-slides/{$post->post_type}/{$post->ID}/{$existing}"
            : '';
        $pdf_status = get_post_meta($post->ID, '_ssv_pdf_status', true);
?>

        <div id="ssv-wrapper">
            <?php if ($pdf_status == 'pending'): ?>
                <div id="ssv-pdf-card">
                    <i class="ri-loader-4-line ssv-icon spin"></i>
                    <span class="ssv-text">Processing PDF...</span>
                </div>
            <?php elseif ($pdf_status == 'completed'): ?>
                <?php echo $this->ssv_render_file_data_table($pdf_guid); ?>
                <div id="ssv-preview">
                    <div class="ssv-preview-header">
                        <h2 class="ssv-preview-file-title"><?php echo basename($pdf_guid); ?></h2>
                        <span class="ssv-preview-close">
                            <i class="ri-close-line"></i>
                        </span>
                    </div>
                    <iframe src="<?php echo esc_url($pdf_url); ?>" width="100%" height="100%"></iframe>
                </div>
            <?php else: ?>
                <?php if (!$existing): ?>
                    <div id="ssv-dropzone">
                        <i class="ri-upload-cloud-2-line ssv-icon"></i>
                        <p>Upload PDF or PPT<br><span>Preview before saving</span></p>
                        <input type="file" name="ssv_upload_file" id="ssv-file-input" accept=".pdf,.ppt,.pptx" hidden>
                    </div>

                    <div id="ssv-preview-temp"></div>
                <?php endif; ?>
            <?php endif; ?>
        </div>

    <?php
    }

    public function ssv_get_file_data($file_path)
    {
        $file_path = wp_normalize_path($file_path);

        if (!file_exists($file_path)) {
            return false;
        }

        $filetype = wp_check_filetype($file_path);

        return [
            'name'       => basename($file_path),
            'path'       => $file_path,
            'size'       => filesize($file_path),
            'size_human' => size_format(filesize($file_path), 2),
            'mime'       => $filetype['type'],
            'extension'  => pathinfo($file_path, PATHINFO_EXTENSION),
            'modified'   => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filemtime($file_path)),
            'created'    => date_i18n(get_option('date_format') . ' ' . get_option('time_format'), filectime($file_path)),
            'readable'   => is_readable($file_path) ? 'Yes' : 'No',
            'writable'   => is_writable($file_path) ? 'Yes' : 'No',
        ];
    }


    public function ssv_render_file_data_table($file_path)
    {
        $data = $this->ssv_get_file_data($file_path);

        if (!$data) {
            echo '<div class="ssv-file-error">File not found.</div>';
            return;
        }

    ?>
        <div class="ssv-file-card">
            <div class="ssv-file-header">
                <i class="ri-file-pdf-2-fill"></i>
                <span><?php echo esc_html($data['name']); ?></span>
            </div>

            <table class="ssv-file-table">
                <tr>
                    <th>Full Path</th>
                    <td><?php echo esc_html($data['path']); ?></td>
                </tr>
                <tr>
                    <th>File Size</th>
                    <td><?php echo esc_html($data['size_human']); ?></td>
                </tr>
                <tr>
                    <th>MIME Type</th>
                    <td><?php echo esc_html($data['mime']); ?></td>
                </tr>
                <tr>
                    <th>Extension</th>
                    <td><?php echo esc_html($data['extension']); ?></td>
                </tr>
                <tr>
                    <th>Last Modified</th>
                    <td><?php echo esc_html($data['modified']); ?></td>
                </tr>
                <tr>
                    <th>Created</th>
                    <td><?php echo esc_html($data['created']); ?></td>
                </tr>
                <tr>
                    <th>Readable</th>
                    <td><?php echo esc_html($data['readable']); ?></td>
                </tr>
                <tr>
                    <th>Writable</th>
                    <td><?php echo esc_html($data['writable']); ?></td>
                </tr>
                <tr>
                    <th>Actions</th>
                    <td>
                        <div style="display: flex;align-items:center;gap: 10px;">
                            <button type="button" class="ssv-preview-btn">
                                <i class="ri-eye-line"></i>
                                <span>View File</span>
                            </button>
                            <button type="submit" name="ssv_remove_file" value="1" class="ssv-remove">
                                Remove & Re-upload
                            </button>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
<?php
    }

    public function handle_secure_slide_on_save($post_id, $post, $update)
    {

        if (!isset($_POST['secure_slides_nonce_field'])) return;
        if (!wp_verify_nonce($_POST['secure_slides_nonce_field'], 'secure_slides_nonce')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $post_type = $post->post_type;

        $base_dir   = wp_normalize_path(WP_CONTENT_DIR) . '/secure-slides';
        $upload_dir = trailingslashit($base_dir) . $post_type . '/' . $post_id;

        if (!file_exists($upload_dir)) {
            wp_mkdir_p($upload_dir);
        }

        // Remove file
        if (isset($_POST['ssv_remove_file'])) {

            $existing = get_post_meta($post_id, '_secure_slide_file', true);

            if ($existing && file_exists($upload_dir . '/' . $existing)) {
                unlink($upload_dir . '/' . $existing);
            }

            delete_post_meta($post_id, '_secure_slide_file');
            delete_post_meta($post_id, '_ssv_pending_ppt');
            delete_post_meta($post_id, '_ssv_pdf_status');
            return;
        }

        if (!empty($_FILES['ssv_upload_file']['name'])) {

            $file = $_FILES['ssv_upload_file'];
            $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            $allowed = ['pdf', 'ppt', 'pptx'];
            if (!in_array($ext, $allowed)) return;

            $filename_base = $post_type . '-' . $post_id;
            $temp_path = $upload_dir . '/' . $filename_base . '.' . $ext;

            move_uploaded_file($file['tmp_name'], $temp_path);

            if ($ext === 'ppt' || $ext === 'pptx') {

                // Store temp file path for background processing
                update_post_meta($post_id, '_ssv_pending_ppt', $temp_path);
                update_post_meta($post_id, '_ssv_pdf_status', 'pending');

                // Schedule background conversion
                if (!wp_next_scheduled('ssv_process_secure_slide_conversion', [$post_id])) {
                    wp_schedule_single_event(time() + 5, 'ssv_process_secure_slide_conversion', [$post_id]);
                }

                return; // Stop here — conversion will happen in background
            } else {
                $final_filename = basename($temp_path);
                update_post_meta($post_id, '_ssv_pdf_status', 'completed');
            }

            update_post_meta($post_id, '_secure_slide_file', $final_filename);
        }
    }

    function ssv_process_secure_slide_conversion_callback($post_id)
    {
        $temp_path = get_post_meta($post_id, '_ssv_pending_ppt', true);

        if (!$temp_path || !file_exists($temp_path)) {
            update_post_meta($post_id, '_ssv_pdf_status', 'failed');
            return;
        }

        $upload_dir = dirname($temp_path);

        $converter = new SSV_Converter();
        $converted_pdf = $converter->convert_ppt_to_pdf($temp_path, $upload_dir);

        if (is_array($converted_pdf) && isset($converted_pdf['error']) && isset($converted_pdf['message'])) {
            update_post_meta($post_id, '_ssv_pdf_status', $converted_pdf['message']);
        } else {
            if ($converted_pdf && file_exists($converted_pdf)) {

                unlink($temp_path); // remove original ppt

                $final_filename = basename($converted_pdf);

                update_post_meta($post_id, '_secure_slide_file', $final_filename);
                update_post_meta($post_id, '_ssv_pdf_status', 'completed');
            } else {
                update_post_meta($post_id, '_ssv_pdf_status', 'failed');
            }
        }

        delete_post_meta($post_id, '_ssv_pending_ppt');
    }

    public function register_secure_slides_metabox()
    {

        add_meta_box(
            'secure_slides_box',
            __('Secure Slides Upload', 'textdomain'),
            [$this, 'render_secure_slides_metabox'],
            'ssv_slides',
            'normal',
            'high'
        );
    }
}
