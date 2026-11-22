<?php

defined('ABSPATH') || exit;

class SSV_Loader
{
    public function run()
    {
        $this->load_sources();
        $this->load_instances();
        $this->load_hooks();
    }

    private function load_sources()
    {
        require_once SSV_PATH . 'vendor/autoload.php';
        require_once SSV_PATH . 'includes/class-ssv-cpt.php';
        require_once SSV_PATH . 'includes/class-ssv-admin.php';
        require_once SSV_PATH . 'includes/class-ssv-notes.php';
        require_once SSV_PATH . 'includes/class-ssv-secure.php';
        require_once SSV_PATH . 'includes/class-ssv-shortcode.php';
        require_once SSV_PATH . 'includes/class-ssv-converter.php';
        require_once SSV_PATH . 'includes/class-ssv-ajax.php';
        require_once SSV_PATH . 'includes/class-ssv-learndash.php';
    }

    private function load_instances()
    {
        new SSV_CPT();
        new SSV_Admin();
        SSV_Notes::instance();
        new SSV_Secure();
        new SSV_Shortcode();
        new SSV_Converter();
        new SSV_Ajax();
        new SSV_Learndash();
    }

    private function load_hooks()
    {
        add_action('wp_enqueue_scripts', [$this, 'assets']);
        register_activation_hook(SSV_DIR, [$this, 'activator']);
        add_action('wp_head', [$this, 'variable_colors']);
    }

    public function variable_colors()
    {
        $color = get_option('cs_theme_color', '#2563eb');

        if (!$color) return;

        echo '<style>
            :root {
                --cs-notes-color-schema: ' . esc_attr($color) . ';
            }
        </style>';
    }

    public function activator()
    {
        $dir = WP_CONTENT_DIR . '/secure-slides';

        if (!file_exists($dir)) {
            wp_mkdir_p($dir);
        }

        file_put_contents($dir . '/index.php', '<?php // silence');
        file_put_contents($dir . '/.htaccess', 'Options -Indexes');
    }

    public function assets()
    {
        wp_enqueue_script(
            'pdfjs',
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js',
            [],
            SSV_VERSION,
            true
        );
        wp_enqueue_style(
            'remixicon',
            'https://cdn.jsdelivr.net/npm/remixicon@4.2.0/fonts/remixicon.css',
            [],
            SSV_VERSION
        );
        wp_enqueue_style('ssv-viewer', SSV_URL . 'assets/css/viewer.css');
        wp_enqueue_script('ssv-viewer', SSV_URL . 'assets/js/viewer.js', ['jquery'], false, true);

        wp_localize_script('ssv-viewer', 'ssv_data', [
            'ajax' => admin_url('admin-ajax.php')
        ]);
    }
}
