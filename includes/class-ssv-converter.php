<?php
defined('ABSPATH') || exit;

class SSV_Converter
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function get_user()
    {
        try {

            \ConvertApi\ConvertApi::setApiCredentials(get_option('cs_converter_api_key'));

            $result = \ConvertApi\ConvertApi::getUser();

            return $result;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    public function convert_ppt_to_pdf($file_path, $output_dir)
    {
        $user_info = $this->get_user();
        if ($user_info && isset($user_info['SecondsLeft'])) {
            $usage = (int) $user_info['SecondsLeft'];
            if ($usage == 0) {
                return [
                    'error' => true,
                    'message' => 'You have reached your API usage limit. Purchase pro plan to get more usage'
                ];
            }
        }

        try {

            \ConvertApi\ConvertApi::setApiCredentials(get_option('cs_converter_api_key'));

            $result = \ConvertApi\ConvertApi::convert(
                'pdf',
                ['File' => $file_path],
                pathinfo($file_path, PATHINFO_EXTENSION)
            );

            $result->saveFiles($output_dir);

            // ConvertAPI saves with original name but pdf extension
            $pdf_name = pathinfo($file_path, PATHINFO_FILENAME) . '.pdf';
            return $output_dir . '/' . $pdf_name;
        } catch (Exception $e) {
            return [
                'error' => true,
                'message' => $e->getMessage()
            ];
        }
    }

    public function add_menu()
    {
        add_menu_page(
            'SSV Settings',
            'SSV Settings',
            'manage_options',
            'cs-converter-settings',
            [$this, 'settings_page'],
            'dashicons-media-document',
            56
        );

        add_submenu_page('cs-converter-settings', 'Secure Slides', 'Secure Slides', 'manage_options', 'edit.php?post_type=ssv_slides');
    }

    public function register_settings()
    {
        register_setting('cs_settings_group', 'cs_converter_api_key');
        register_setting('cs_settings_group', 'cs_theme_color');
    }

    public function api_key_field()
    {
        $value = esc_attr(get_option('cs_converter_api_key'));
?>
        <div class="cs-field-group">
            <label for="cs_converter_api_key" class="cs-label">
                Converter API
            </label>

            <div class="cs-api-wrapper">
                <input
                    type="password"
                    id="cs_converter_api_key"
                    name="cs_converter_api_key"
                    value="<?php echo $value; ?>"
                    placeholder="Enter your API key"
                    class="cs-api-input">

                <button type="button" class="cs-toggle-btn" aria-label="Toggle API Key">
                    <i class="ri-eye-line"></i>
                </button>
            </div>

            <p class="cs-description">
                Your API key will be securely stored in WordPress options.
            </p>
        </div>
    <?php
    }

    public function settings_page()
    {

        $value = esc_attr(get_option('cs_converter_api_key'));
        $color = esc_attr(get_option('cs_theme_color', '#2563eb'));
    ?>

        <div class="cs-admin-wrap">
            <div class="cs-card">

                <div class="cs-card-header">
                    <h1>Converter Settings</h1>
                    <p>Manage your API configuration below</p>
                </div>

                <form method="post" action="options.php">
                    <?php settings_fields('cs_settings_group'); ?>

                    <div class="cs-field-group">
                        <label for="cs_converter_api_key" class="cs-label">
                            Converter API
                        </label>

                        <div class="cs-api-wrapper">
                            <input
                                type="password"
                                id="cs_converter_api_key"
                                name="cs_converter_api_key"
                                value="<?php echo $value; ?>"
                                placeholder="Enter your API key">

                            <button type="button" class="cs-toggle-btn">
                                <i class="ri-eye-line"></i>
                            </button>
                        </div>

                        <p class="cs-description">
                            Your API key will be securely stored in WordPress options.
                        </p>
                    </div>

                    <div class="cs-field-group">
                        <label for="cs_theme_color" class="cs-label">
                            Notes Color Schema
                        </label>

                        <input
                            type="text"
                            id="cs_theme_color"
                            name="cs_theme_color"
                            value="<?php echo $color; ?>"
                            class="cs-color-field"
                            data-default-color="#2563eb">

                        <p class="cs-description">
                            Select a primary color for your plugin UI.
                        </p>
                    </div>

                    <div style="margin-top:30px;">
                        <button type="submit" class="cs-save-btn">
                            Save Settings
                        </button>
                    </div>

                </form>

            </div>
        </div>

<?php
    }
}
