<?php
if (!defined('ABSPATH')) exit;

class SSV_Shortcode
{

    public function __construct()
    {
        add_shortcode('ssv_slide', [$this, 'ssv_slide_shortcode']);
    }

    function ssv_slide_shortcode($atts)
    {
        $atts = shortcode_atts(['id' => 0], $atts);
        $post_id = intval($atts['id']);
        if (!$post_id) return '';

        if (!is_user_logged_in()) {
            return '<div class="ssv-locked-message">Please login to access this content.</div>';
        }

        $user_id   = get_current_user_id();
        $course_id = learndash_get_course_id(get_the_ID());

        // Check LearnDash access
        $has_access = sfwd_lms_has_access($course_id, $user_id);

        if (!$has_access) {

            // Try to get purchase link (WooCommerce linked product if exists)
            $purchase_link = get_permalink($course_id);

            ob_start();
?>
            <div class="ssv-fake-wrapper">
                <div class="ssv-blur-layer"></div>

                <div class="ssv-expired-overlay">
                    <div class="ssv-expired-card">
                        <h3>Course Access Expired</h3>
                        <p>Your access to this course has expired.
                            Please re-purchase to continue viewing this PDF.</p>

                        <a href="<?php echo esc_url($purchase_link); ?>" class="ssv-repurchase-btn">
                            Re-Purchase Course
                        </a>
                    </div>
                </div>
            </div>
        <?php
            return ob_get_clean();
        }

        if (!session_id()) {
            session_start();
        }

        $session_hash = hash('sha256', session_id() . $_SERVER['HTTP_USER_AGENT']);

        $token = wp_generate_password(40, false, false);

        set_transient(
            'ssv_token_' . $token,
            [
                'post_id' => $post_id,
                'session' => $session_hash,
                'used'    => false
            ],
            10 * MINUTE_IN_SECONDS
        );
        $watermark_old =  esc_attr(wp_get_current_user()->user_email);
        $secure_url = site_url('/ssv-secure-pdf/?token=' . $token);
        $resume_page = get_post_meta(get_the_ID(), 'resume_data', true);
        $resume_page_no = $resume_page[$user_id][$post_id] ?? 1;
        ob_start();
        ?>
        <div class="ssv-pdf-header">
            <button type="button" class="ssv-button ssv-resume" data-viewer="ssv_<?php echo md5($post_id); ?>"><?php _e('Resume Slide', 'ssv'); ?></button>
            <button type="button" class="ssv-button ssv-startOver" data-viewer="ssv_<?php echo md5($post_id); ?>"><?php _e('Start Over', 'ssv'); ?></button>
            <button type="button" class="ssv-button ssv-complete" data-viewer="ssv_<?php echo md5($post_id); ?>"><?php _e('Complete', 'ssv'); ?></button>
        </div>
        <div
            class="ssv-pdf-viewer"
            id="ssv_<?php echo md5($post_id); ?>"
            data-url="<?php echo esc_url($secure_url); ?>"
            data-user-email="info@trainforthecrane.com"
            data-user-id="<?php echo get_current_user_id(); ?>"
            data-post_id="<?php echo get_the_ID(); ?>"
            data-id="<?php echo $post_id; ?>"
            data-page="<?php echo intval($resume_page_no); ?>"></div>
<?php

        return ob_get_clean();
    }
}
