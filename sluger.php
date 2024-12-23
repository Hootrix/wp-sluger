<?php
/**
 * Plugin Name: Sluger
 * Plugin URI: https://github.com/Hootrix/wp-sluger
 * Description: URL shortener & automation slug generator with DeepLX and ChatGPT API support
 * Version: 0.0.1
 * Author: HHTJIM
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: hhtjim-wp-sluger
 */

if (!defined('ABSPATH')) {
    exit;
}

class HHTJIM_WP_Sluger_Plugin {
    private static $instance = null;
    private $options;
    private $option_name = 'hhtjim_wp_sluger_options';
    private $option_group = 'hhtjim_wp_sluger_group';
    private $page_slug = 'hhtjim_wp_sluger_settings';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Load translations
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('save_post', array($this, 'generate_slug'), 10, 3);
        add_action('admin_notices', array($this, 'admin_notices'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_hhtjim_wp_sluger_test_api', array($this, 'test_api_connection'));
        
        $this->options = get_option($this->option_name);
    }

    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'hhtjim-wp-sluger',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );
    }

    public function add_admin_menu() {
        add_options_page(
            __('Sluger Settings', 'hhtjim-wp-sluger'),
            __('Sluger', 'hhtjim-wp-sluger'),
            'manage_options',
            $this->page_slug,
            array($this, 'options_page')
        );
    }

    public function register_settings() {
        register_setting($this->option_group, $this->option_name, array($this, 'validate_options'));
        
        add_settings_section(
            'hhtjim_wp_sluger_section',
            __('API Settings', 'hhtjim-wp-sluger'),
            array($this, 'section_callback'),
            $this->page_slug
        );

        add_settings_field(
            'translation_service',
            __('Translation Service', 'hhtjim-wp-sluger'),
            array($this, 'service_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'language_style',
            __('Language Style', 'hhtjim-wp-sluger'),
            array($this, 'language_style_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'custom_prompt',
            __('Custom Prompt Template', 'hhtjim-wp-sluger'),
            array($this, 'custom_prompt_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'deeplx_url',
            __('DeepLX API URL', 'hhtjim-wp-sluger'),
            array($this, 'deeplx_url_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'chatgpt_url',
            __('ChatGPT API URL', 'hhtjim-wp-sluger'),
            array($this, 'chatgpt_url_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'chatgpt_api_key',
            __('ChatGPT API Key', 'hhtjim-wp-sluger'),
            array($this, 'chatgpt_api_key_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );

        add_settings_field(
            'chatgpt_model',
            __('ChatGPT Model', 'hhtjim-wp-sluger'),
            array($this, 'chatgpt_model_callback'),
            $this->page_slug,
            'hhtjim_wp_sluger_section'
        );
    }

    public function section_callback() {
        echo '<p>' . __('Configure your translation service settings here.', 'hhtjim-wp-sluger') . '</p>';
    }

    public function service_callback() {
        $service = isset($this->options['translation_service']) ? $this->options['translation_service'] : 'deeplx';
        ?>
        <select name="<?php echo $this->option_name; ?>[translation_service]">
            <option value="deeplx" <?php selected($service, 'deeplx'); ?>>DeepLX</option>
            <option value="chatgpt" <?php selected($service, 'chatgpt'); ?>>ChatGPT</option>
        </select>
        <?php
    }

    public function language_style_callback() {
        $style = isset($this->options['language_style']) ? $this->options['language_style'] : 'english';
        ?>
        <select name="<?php echo $this->option_name; ?>[language_style]" id="language-style-select">
            <option value="english" <?php selected($style, 'english'); ?>><?php _e('English Translation', 'hhtjim-wp-sluger'); ?></option>
            <option value="pinyin" <?php selected($style, 'pinyin'); ?>><?php _e('Chinese Pinyin', 'hhtjim-wp-sluger'); ?></option>
            <option value="romanize" <?php selected($style, 'romanize'); ?>><?php _e('Romanization (For non-Latin scripts)', 'hhtjim-wp-sluger'); ?></option>
            <option value="original" <?php selected($style, 'original'); ?>><?php _e('Keep Original (just sanitize)', 'hhtjim-wp-sluger'); ?></option>
            <option value="custom" <?php selected($style, 'custom'); ?>><?php _e('Custom Style', 'hhtjim-wp-sluger'); ?></option>
        </select>
        <?php
    }

    public function custom_prompt_callback() {
        $custom_prompt = isset($this->options['custom_prompt']) ? $this->options['custom_prompt'] : '';
        ?>
        <div id="custom-prompt-container" style="<?php echo isset($this->options['language_style']) && $this->options['language_style'] === 'custom' ? '' : 'display: none;'; ?>">
            <textarea name="<?php echo $this->option_name; ?>[custom_prompt]" 
                      rows="4" 
                      cols="50" 
                      class="large-text code"
                      placeholder="<?php esc_attr_e('Example 1: Convert {title} to Pinyin and create a URL slug
Example 2: Translate {title} to English and create a URL slug
Example 3: Create a URL slug from {title} using Japanese romaji', 'hhtjim-wp-sluger'); ?>"
            ><?php echo esc_textarea($custom_prompt); ?></textarea>
            <p class="description">
                <?php _e('Use {title} as a placeholder for the input title.', 'hhtjim-wp-sluger'); ?><br>
                <?php _e('The prompt should instruct how to convert the title into a URL-friendly slug.', 'hhtjim-wp-sluger'); ?>
            </p>
        </div>
        <?php
    }

    public function deeplx_url_callback() {
        $url = isset($this->options['deeplx_url']) ? $this->options['deeplx_url'] : '';
        ?>
        <div class="hhtjim-wp-sluger-field-group">
            <input type="text" 
                   name="<?php echo $this->option_name; ?>[deeplx_url]" 
                   value="<?php echo esc_attr($url); ?>" 
                   class="regular-text"
                   placeholder="http://your-deeplx-server:1188/translate"
            >
            <button type="button" class="button button-secondary test-api" data-service="deeplx">
                <?php _e('Test Connection', 'hhtjim-wp-sluger'); ?>
            </button>
            <p class="description"><?php _e('Enter your DeepLX server URL (e.g., http://localhost:1188/translate, http://localhost:1188/translate?token=xxxxxxxxx)', 'hhtjim-wp-sluger'); ?></p>
            <div class="api-test-result"></div>
        </div>
        <?php
    }

    public function chatgpt_url_callback() {
        $url = isset($this->options['chatgpt_url']) ? $this->options['chatgpt_url'] : '';
        ?>
        <div class="hhtjim-wp-sluger-field-group">
            <input type="text" 
                   name="<?php echo $this->option_name; ?>[chatgpt_url]" 
                   value="<?php echo esc_attr($url); ?>" 
                   class="regular-text"
                   placeholder="<?php _e('https://api.openai.com/v1/chat/completions', 'hhtjim-wp-sluger'); ?>"
            >
            <p class="description"><?php _e('Enter the ChatGPT API endpoint URL', 'hhtjim-wp-sluger'); ?></p>
        </div>
        <?php
    }

    public function chatgpt_api_key_callback() {
        $key = isset($this->options['chatgpt_api_key']) ? $this->options['chatgpt_api_key'] : '';
        ?>
        <div class="hhtjim-wp-sluger-field-group">
            <input type="password" 
                   name="<?php echo $this->option_name; ?>[chatgpt_api_key]" 
                   value="<?php echo esc_attr($key); ?>" 
                   class="regular-text"
                   placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
            >
            <button type="button" class="button button-secondary test-api" data-service="chatgpt">
                <?php _e('Test Connection', 'hhtjim-wp-sluger'); ?>
            </button>
            <p class="description"><?php _e('Your OpenAI API key or custom API key', 'hhtjim-wp-sluger'); ?></p>
            <div class="api-test-result"></div>
        </div>
        <?php
    }

    public function chatgpt_model_callback() {
        $model = isset($this->options['chatgpt_model']) ? $this->options['chatgpt_model'] : 'gpt-3.5-turbo';
        $custom_model = isset($this->options['chatgpt_custom_model']) ? $this->options['chatgpt_custom_model'] : '';
        ?>
        <select name="<?php echo $this->option_name; ?>[chatgpt_model]" id="chatgpt-model-select">
            <option value="gpt-3.5-turbo" <?php selected($model, 'gpt-3.5-turbo'); ?>><?php _e('GPT-3.5 Turbo', 'hhtjim-wp-sluger'); ?></option>
            <option value="gpt-4" <?php selected($model, 'gpt-4'); ?>><?php _e('GPT-4', 'hhtjim-wp-sluger'); ?></option>
            <option value="gpt-4-turbo" <?php selected($model, 'gpt-4-turbo'); ?>><?php _e('GPT-4 Turbo', 'hhtjim-wp-sluger'); ?></option>
            <option value="custom" <?php selected($model, 'custom'); ?>><?php _e('Custom Model', 'hhtjim-wp-sluger'); ?></option>
        </select>
        <div id="custom-model-input" style="margin-top: 10px; <?php echo $model === 'custom' ? '' : 'display: none;'; ?>">
            <input type="text" 
                   name="<?php echo $this->option_name; ?>[chatgpt_custom_model]" 
                   value="<?php echo esc_attr($custom_model); ?>" 
                   class="regular-text"
                   placeholder="<?php _e('gpt-4-1106-preview', 'hhtjim-wp-sluger'); ?>"
            >
            <p class="description"><?php _e('Enter the model identifier as specified in OpenAI\'s documentation', 'hhtjim-wp-sluger'); ?></p>
        </div>
        <?php
    }

    public function options_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Sluger Settings', 'hhtjim-wp-sluger'); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields($this->option_group);
                do_settings_sections($this->page_slug);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function generate_slug($post_id, $post, $update) {
        // break auto-draft
        if ($post->post_status === 'auto-draft') {
            return;
        }

        // only support post and page
        if (!in_array($post->post_type, array('post', 'page'))) {
            return;
        }

        // check if the title has changed
        if ($update) {
            $old_post = get_post($post_id);
            if ($old_post->post_title === $post->post_title) {
                // no change
                return;
            }
        }

        $title = $post->post_title;
        $service = isset($this->options['translation_service']) ? $this->options['translation_service'] : 'deeplx';

        if ($service === 'deeplx') {
            $slug = $this->translate_with_deeplx($title);
        } else {
            $slug = $this->translate_with_chatgpt($title);
        }

        if ($slug) {
            global $wpdb;
            
            $wpdb->update(
                $wpdb->posts,
                array('post_name' => sanitize_title($slug)),
                array('ID' => $post_id),
                array('%s'),
                array('%d')
            );
            
            clean_post_cache($post_id);
        }
    }

    private function translate_with_deeplx($text) {
        $api_url = isset($this->options['deeplx_url']) ? $this->options['deeplx_url'] : '';
        
        if (empty($api_url)) {
            return false;
        }

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'text' => $text,
                'source_lang' => 'auto',
                'target_lang' => 'EN'
            ))
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['data']) ? $body['data'] : false;
    }

    private function translate_with_chatgpt($text) {
        $api_url = isset($this->options['chatgpt_url']) ? $this->options['chatgpt_url'] : '';
        $api_key = isset($this->options['chatgpt_api_key']) ? $this->options['chatgpt_api_key'] : '';
        $model = isset($this->options['chatgpt_model']) ? $this->options['chatgpt_model'] : 'gpt-3.5-turbo';
        $style = isset($this->options['language_style']) ? $this->options['language_style'] : 'english';
        
        if ($model === 'custom') {
            $model = isset($this->options['chatgpt_custom_model']) ? $this->options['chatgpt_custom_model'] : 'gpt-3.5-turbo';
        }
        
        if (empty($api_url) || empty($api_key)) {
            return false;
        }

        $system_prompt = "You are a URL slug generator. Your task is to create SEO-friendly URL slugs from titles. " .
                        "Rules:\n" .
                        "1. ONLY output the slug, nothing else\n" .
                        "2. Use ONLY lowercase letters (a-z), numbers (0-9), and hyphens (-)\n" .
                        "3. Replace spaces with hyphens\n" .
                        "4. Remove all special characters, emojis, and non-ASCII characters\n" .
                        "5. Keep the slug concise but meaningful\n" .
                        "6. If the input is unclear or cannot be processed, respond with 'INVALID_SLUG'\n" .
                        "7. Maximum length: 60 characters\n";

        $prompt_template = $this->get_prompt_template($style);
        $user_prompt = str_replace('{title}', $text, $prompt_template);

        $response = wp_remote_post($api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_prompt
                    ),
                    array(
                        'role' => 'user',
                        'content' => $user_prompt
                    )
                ),
                'temperature' => 0.1, // 降低温度以获得更确定性的输出
                'max_tokens' => 60,
                'presence_penalty' => 0,
                'frequency_penalty' => 0
            ))
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (!isset($body['choices'][0]['message']['content'])) {
            return false;
        }

        $slug = trim($body['choices'][0]['message']['content']);
        
        // 验证生成的slug
        if ($slug === 'INVALID_SLUG' || !$this->validate_slug($slug)) {
            // 如果验证失败，返回原始标题的sanitized版本
            return sanitize_title($text);
        }

        return $slug;
    }

    private function get_prompt_template($style) {
        switch ($style) {
            case 'english':
                return "Translate the title to English and create a URL slug: {title}";
            case 'pinyin':
                return "Convert the Chinese title to Pinyin and create a URL slug. Use standard Pinyin without tones: {title}";
            case 'romanize':
                return "Romanize this title (convert to Latin characters) and create a URL slug: {title}";
            case 'original':
                return "Create a URL slug keeping the original language, only remove special characters and spaces: {title}";
            case 'custom':
                $custom_prompt = isset($this->options['custom_prompt']) ? $this->options['custom_prompt'] : '';
                return !empty($custom_prompt) ? $custom_prompt : "Create a URL slug for: {title}";
            default:
                return "Create a URL slug for: {title}";
        }
    }

    /**
     * 验证生成的slug是否符合要求
     */
    private function validate_slug($slug) {
        // 1. 检查长度
        if (strlen($slug) > 60 || strlen($slug) < 1) {
            return false;
        }

        // 2. 检查格式（只允许小写字母、数字和连字符）
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            return false;
        }

        // 3. 检查连字符使用
        if (strpos($slug, '--') !== false) { // 不允许连续的连字符
            return false;
        }

        if (substr($slug, 0, 1) === '-' || substr($slug, -1) === '-') { // 不允许首尾连字符
            return false;
        }

        // 4. 检查是否包含有意义的内容（不只是数字）
        if (preg_match('/^[0-9-]+$/', $slug)) {
            return false;
        }

        return true;
    }

    public function admin_notices() {
        if (isset($_GET['settings-updated']) && $_GET['settings-updated']) {
            if (isset($_GET['sluger-error'])) {
                $error_message = get_transient('sluger_error_message');
                delete_transient('sluger_error_message');
                if ($error_message) {
                    ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html($error_message); ?></p>
                    </div>
                    <?php
                }
            } else {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php _e('Settings saved successfully.', 'hhtjim-wp-sluger'); ?></p>
                </div>
                <?php
            }
        }
    }

    public function validate_options($input) {
        $output = array();
        
        // Validate translation service
        if (isset($input['translation_service'])) {
            $output['translation_service'] = sanitize_text_field($input['translation_service']);
        }

        // Validate DeepLX URL
        if (isset($input['deeplx_url'])) {
            $url = esc_url_raw($input['deeplx_url']);
            if ($output['translation_service'] === 'deeplx' && !empty($url)) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    set_transient('sluger_error_message', __('Invalid DeepLX URL format.', 'hhtjim-wp-sluger'), 30);
                    wp_redirect(add_query_arg('sluger-error', 'true'));
                    exit;
                }
            }
            $output['deeplx_url'] = $url;
        }

        // Validate ChatGPT URL
        if (isset($input['chatgpt_url'])) {
            $url = esc_url_raw($input['chatgpt_url']);
            if ($output['translation_service'] === 'chatgpt' && !empty($url)) {
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    set_transient('sluger_error_message', __('Invalid ChatGPT API URL format.', 'hhtjim-wp-sluger'), 30);
                    wp_redirect(add_query_arg('sluger-error', 'true'));
                    exit;
                }
            }
            $output['chatgpt_url'] = $url;
        }

        // Validate ChatGPT API Key
        if (isset($input['chatgpt_api_key'])) {
            $api_key = sanitize_text_field($input['chatgpt_api_key']);
            if ($output['translation_service'] === 'chatgpt' && empty($api_key)) {
                set_transient('sluger_error_message', __('ChatGPT API Key is required.', 'hhtjim-wp-sluger'), 30);
                wp_redirect(add_query_arg('sluger-error', 'true'));
                exit;
            }
            $output['chatgpt_api_key'] = $api_key;
        }

        // Copy other fields
        $other_fields = array('chatgpt_model', 'chatgpt_custom_model', 'language_style', 'custom_prompt');
        foreach ($other_fields as $field) {
            if (isset($input[$field])) {
                $output[$field] = sanitize_text_field($input[$field]);
            }
        }

        return $output;
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'settings_page_' . $this->page_slug) {
            return;
        }

        wp_enqueue_script(
            'hhtjim-wp-sluger-admin',
            plugin_dir_url(__FILE__) . 'assets/js/admin.js',
            array('jquery'),
            '0.0.1',
            true
        );

        wp_localize_script('hhtjim-wp-sluger-admin', 'hhtjimWpSlugerAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('hhtjim_wp_sluger_test_api')
        ));

        wp_enqueue_style(
            'hhtjim-wp-sluger-admin',
            plugin_dir_url(__FILE__) . 'assets/css/admin.css',
            array(),
            '0.0.1'
        );
    }

    /**
     * Test API connection
     */
    public function test_api_connection() {
        check_ajax_referer('hhtjim_wp_sluger_test_api', 'nonce');

        if (!current_user_can('manage_options')) {
            $this->log_error(__('Unauthorized API test attempt', 'hhtjim-wp-sluger'), array(
                'user_id' => get_current_user_id()
            ));
            wp_send_json_error(__('Unauthorized access', 'hhtjim-wp-sluger'));
        }

        $service = isset($_POST['service']) ? sanitize_text_field($_POST['service']) : '';
        
        if ($service === 'deeplx') {
            $url = isset($_POST['deeplx_url']) ? esc_url_raw($_POST['deeplx_url']) : '';
            if (empty($url)) {
                wp_send_json_error(__('DeepLX URL is required', 'hhtjim-wp-sluger'));
            }
            
            // 测试DeepLX连接
            $response = wp_remote_post($url, array(
                'timeout' => 15,
                'headers' => array('Content-Type' => 'application/json'),
                'body' => json_encode(array(
                    'text' => 'test',
                    'source_lang' => 'auto',
                    'target_lang' => 'EN'
                ))
            ));

        } else if ($service === 'chatgpt') {
            $url = isset($_POST['chatgpt_url']) ? esc_url_raw($_POST['chatgpt_url']) : '';
            $key = isset($_POST['chatgpt_key']) ? sanitize_text_field($_POST['chatgpt_key']) : '';
            $model = isset($_POST['chatgpt_model']) ? sanitize_text_field($_POST['chatgpt_model']) : '';
            
            if (empty($url) || empty($key)) {
                wp_send_json_error(__('ChatGPT URL and API key are required', 'hhtjim-wp-sluger'));
            }

            // 测试ChatGPT连接
            $response = wp_remote_post($url, array(
                'timeout' => 15,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json'
                ),
                'body' => json_encode(array(
                    'model' => $model,
                    'messages' => array(
                        array('role' => 'user', 'content' => 'test')
                    ),
                    'max_tokens' => 5
                ))
            ));
        } else {
            $this->log_error(__('Invalid service specified', 'hhtjim-wp-sluger'), array(
                'service' => $service
            ));
            wp_send_json_error(__('Invalid service', 'hhtjim-wp-sluger'));
        }

        if (is_wp_error($response)) {
            $this->log_error(__('API connection error', 'hhtjim-wp-sluger'), array(
                'service' => $service,
                'error' => $response->get_error_message()
            ));
            wp_send_json_error($response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($status_code !== 200) {
            $this->log_error(__('API returned non-200 status', 'hhtjim-wp-sluger'), array(
                'service' => $service,
                'status' => $status_code,
                'response' => $body
            ));
            wp_send_json_error(__('API returned status code: ', 'hhtjim-wp-sluger') . $status_code . '. ' . __('Response: ', 'hhtjim-wp-sluger') . $body);
        }

        wp_send_json_success(__('API connection successful', 'hhtjim-wp-sluger'));
    }

    /**
     * Log error message
     */
    private function log_error($message, $data = array()) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }

        $log_data = array(
            'timestamp' => current_time('mysql'),
            'message' => $message,
            'data' => $data
        );

        error_log(print_r(array(
            'plugin' => 'HHTJIM WP Sluger',
            'error' => $log_data
        ), true));
    }

    /**
     * Plugin activation hook
     */
    public static function activate() {
        // Add default options
        $default_options = array(
            'translation_service' => 'chatgpt',
            'chatgpt_url' => 'https://api.openai.com/v1/chat/completions',
            'chatgpt_model' => 'gpt-3.5-turbo',
            'language_style' => 'english'
        );
        add_option('hhtjim_wp_sluger_options', $default_options);
    }

    /**
     * Plugin uninstall hook
     */
    public static function uninstall() {
        delete_option('hhtjim_wp_sluger_options');
    }
}

// Initialize the plugin
add_action('plugins_loaded', array('HHTJIM_WP_Sluger_Plugin', 'get_instance'));

// Register activation and uninstall hooks
register_activation_hook(__FILE__, array('HHTJIM_WP_Sluger_Plugin', 'activate'));
register_uninstall_hook(__FILE__, array('HHTJIM_WP_Sluger_Plugin', 'uninstall'));
