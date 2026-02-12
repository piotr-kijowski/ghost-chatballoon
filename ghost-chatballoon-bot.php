<?php
/*
Plugin Name: Ghost Chat Balloon Bot
Description: Floating AI chat balloon for WordPress with customizable tone, themes, knowledge base CPT, logging, guardrails, WooCommerce context, per‑page overrides, a Test Connection tool, and deep UI customization.
Version: 1.2.7
Author:      Piotr Kijowski [piotr.kijowski@gmail.com]
Author URI:  https://github.com/piotr-kijowski
License:     GPL-3.0+
License URI: https://www.gnu.org/licenses/gpl-3.0.html
*/

if ( ! defined( 'ABSPATH' ) ) exit;

class Ghost_Chat_Balloon_Bot {
    const OPTION_KEY = 'ghost_chat_balloon_options';
    const NONCE_ACTION = 'ghost_chat_balloon_nonce';
    const CPT_KB = 'ghost_kb';
    const CPT_LOG = 'ghost_bot_log';

    public function __construct() {
        add_action('init', [$this, 'register_assets']);
        add_action('init', [$this, 'register_cpts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);

        // AJAX endpoints (public + admin)
        add_action('wp_ajax_nopriv_ghost_bot_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_ghost_bot_chat', [$this, 'ajax_chat']);
        add_action('wp_ajax_ghost_bot_test', [$this, 'ajax_test']); // admin-only test

        // Custom save handler (bypasses options.php issues)
        add_action('admin_post_ghost_bot_save', [$this, 'handle_settings_save']);

        // Export logs
        add_action('admin_post_ghost_bot_export_logs', [$this, 'export_logs_csv']);

        // Shortcode
        add_shortcode('ghost_chatbot', [$this, 'render_shortcode']);
        add_shortcode('pp_chatbot', [$this, 'render_shortcode']);

        // Per-page meta
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
    }

    public function register_cpts() {
        register_post_type(self::CPT_KB, [
            'label' => 'Bot Knowledge',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'pp-chat-bot',
            'supports' => ['title', 'editor'],
            'menu_icon' => 'dashicons-book',
        ]);

        register_post_type(self::CPT_LOG, [
            'label' => 'Bot Logs',
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'pp-chat-bot',
            'supports' => ['title', 'editor', 'custom-fields'],
            'capabilities' => ['create_posts' => 'do_not_allow'],
            'map_meta_cap' => true,
            'menu_icon' => 'dashicons-archive',
        ]);
    }

    public function default_options() {
        return [
            'api_key' => '',
            'model' => 'gpt-4o-mini',
            'temperature' => 0.6,
            'max_tokens' => 300,
            'bot_name' => 'Ghost Bot',
            'welcome_message' => 'Hey! I’m your helpful site assistant. Ask me anything.',
            'tone' => 'friendly',
            'tone_custom' => '',
            'kb' => '',
            'theme' => 'imessage',
            'primary_color' => '#5b8cff',
            'position' => 'bottom-right',
            'open_on_load' => 0,
            'enable_on_mobile' => 1,
            // Guardrails & logging
            'enable_logging' => 0,
            'consent_required' => 0,
            'consent_label' => 'I agree that my chat may be saved to help improve our guidance and support experience.',
            'max_turns' => 20,
            'profanity_filter' => 1,
            'woo_context' => 0,
            // UI customizations
            'launcher_icon_type' => 'emoji', // emoji|image
            'launcher_icon_emoji' => '💬',
            'launcher_icon_image' => '',
            'launcher_bg_color' => '#5b8cff',
            'launcher_size' => 56,
            'font_size_base' => 14,
            'bubble_radius' => 18,
            'send_button_bg' => '#5b8cff',
            'send_icon' => '↩',
        ];
    }

    public function get_options() {
        $defaults = $this->default_options();
        $opts = get_option(self::OPTION_KEY, []);
        return wp_parse_args($opts, $defaults);
    }

    public function register_assets() {
        $ver = '1.2.1';
        wp_register_style('ghost-bot-css', plugins_url('assets/css/ghost-bot.css', __FILE__), [], $ver);
        wp_register_script('ghost-bot-js', plugins_url('assets/js/ghost-bot.js', __FILE__), ['jquery'], $ver, true);
    }

    public function enqueue_assets() {
        if (is_admin()) return;
        if ($this->is_disabled_for_current_page()) return;

        $opts = $this->get_options();
        wp_enqueue_style('ghost-bot-css');
        wp_enqueue_script('ghost-bot-js');

        $settings = [
            'botName' => $opts['bot_name'],
            'welcome' => $opts['welcome_message'],
            'theme' => $opts['theme'],
            'primaryColor' => $opts['primary_color'],
            'position' => $opts['position'],
            'openOnLoad' => (bool) $opts['open_on_load'],
            'enableOnMobile' => (bool) $opts['enable_on_mobile'],
            'consentRequired' => (bool) $opts['consent_required'],
            'consentLabel' => $opts['consent_label'],
            'enableLogging' => (bool) $opts['enable_logging'],
            'maxTurns' => intval($opts['max_turns']),
            'launcher' => [
                'type' => $opts['launcher_icon_type'],
                'emoji' => $opts['launcher_icon_emoji'],
                'image' => $opts['launcher_icon_image'],
                'bg' => $opts['launcher_bg_color'],
                'size' => intval($opts['launcher_size']),
            ],
            'ui' => [
                'fontSize' => intval($opts['font_size_base']),
                'radius' => intval($opts['bubble_radius']),
                'sendBg' => $opts['send_button_bg'],
                'sendIcon' => $opts['send_icon'],
            ],
        ];

        // Per-page overrides
        if (is_singular()) {
            $over = [
                'bot_name' => get_post_meta(get_the_ID(), '_ghost_bot_name', true),
                'theme' => get_post_meta(get_the_ID(), '_ghost_bot_theme', true),
                'position' => get_post_meta(get_the_ID(), '_ghost_bot_position', true),
                'welcome' => get_post_meta(get_the_ID(), '_ghost_bot_welcome', true),
                'primary_color' => get_post_meta(get_the_ID(), '_ghost_bot_primary', true),
                'open_on_load' => get_post_meta(get_the_ID(), '_ghost_bot_open', true),
            ];
            if (!empty($over['bot_name'])) $settings['botName'] = $over['bot_name'];
            if (!empty($over['theme'])) $settings['theme'] = $over['theme'];
            if (!empty($over['position'])) $settings['position'] = $over['position'];
            if (!empty($over['welcome'])) $settings['welcome'] = $over['welcome'];
            if (!empty($over['primary_color'])) $settings['primaryColor'] = $over['primary_color'];
            if ($over['open_on_load'] !== '') $settings['openOnLoad'] = (bool) $over['open_on_load'];
        }

        $data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(self::NONCE_ACTION),
            'settings' => $settings,
        ];
        wp_localize_script('ghost-bot-js', 'GhostBotData', $data);
    }

    public function admin_menu() {
        add_menu_page(
            'Chat Bot',
            'Chat Bot',
            'manage_options',
            'pp-chat-bot',
            [$this, 'render_settings_page'],
            'dashicons-format-chat',
            65
        );
        // Explicit Settings submenu so it always shows
        add_submenu_page(
            'pp-chat-bot',
            'Settings',
            'Settings',
            'manage_options',
            'pp-chat-bot',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        register_setting('ghost_chat_bot_group', self::OPTION_KEY, [$this, 'sanitize_options']);
    }

    public function sanitize_options($input) {
        $d = $this->default_options();
        $o = [];
        // Basics
        $o['api_key'] = isset($input['api_key']) ? trim($input['api_key']) : '';
        $o['model'] = isset($input['model']) ? sanitize_text_field($input['model']) : $d['model'];
        $o['temperature'] = isset($input['temperature']) ? floatval($input['temperature']) : $d['temperature'];
        $o['max_tokens'] = isset($input['max_tokens']) ? intval($input['max_tokens']) : $d['max_tokens'];
        $o['bot_name'] = isset($input['bot_name']) ? sanitize_text_field($input['bot_name']) : $d['bot_name'];
        $o['welcome_message'] = isset($input['welcome_message']) ? wp_kses_post($input['welcome_message']) : $d['welcome_message'];
        $o['tone'] = isset($input['tone']) ? sanitize_text_field($input['tone']) : $d['tone'];
        $o['tone_custom'] = isset($input['tone_custom']) ? wp_kses_post($input['tone_custom']) : '';
        $o['kb'] = isset($input['kb']) ? wp_kses_post($input['kb']) : '';
        $o['theme'] = isset($input['theme']) ? sanitize_text_field($input['theme']) : $d['theme'];
        $o['primary_color'] = isset($input['primary_color']) ? sanitize_hex_color($input['primary_color']) : $d['primary_color'];
        $o['position'] = isset($input['position']) ? sanitize_text_field($input['position']) : $d['position'];
        $o['open_on_load'] = ! empty($input['open_on_load']) ? 1 : 0;
        $o['enable_on_mobile'] = ! empty($input['enable_on_mobile']) ? 1 : 0;
        // Guardrails & logging
        $o['enable_logging'] = ! empty($input['enable_logging']) ? 1 : 0;
        $o['consent_required'] = ! empty($input['consent_required']) ? 1 : 0;
        $o['consent_label'] = isset($input['consent_label']) ? sanitize_text_field($input['consent_label']) : $d['consent_label'];
        $o['max_turns'] = isset($input['max_turns']) ? max(1, intval($input['max_turns'])) : $d['max_turns'];
        $o['profanity_filter'] = ! empty($input['profanity_filter']) ? 1 : 0;
        $o['woo_context'] = ! empty($input['woo_context']) ? 1 : 0;
        // UI customization
        $o['launcher_icon_type'] = isset($input['launcher_icon_type']) ? sanitize_text_field($input['launcher_icon_type']) : 'emoji';
        $o['launcher_icon_emoji'] = isset($input['launcher_icon_emoji']) ? sanitize_text_field($input['launcher_icon_emoji']) : '💬';
        $o['launcher_icon_image'] = isset($input['launcher_icon_image']) ? esc_url_raw($input['launcher_icon_image']) : '';
        $o['launcher_bg_color'] = isset($input['launcher_bg_color']) ? sanitize_hex_color($input['launcher_bg_color']) : '#5b8cff';
        $o['launcher_size'] = isset($input['launcher_size']) ? max(40, intval($input['launcher_size'])) : 56;
        $o['font_size_base'] = isset($input['font_size_base']) ? max(12, intval($input['font_size_base'])) : 14;
        $o['bubble_radius'] = isset($input['bubble_radius']) ? max(8, intval($input['bubble_radius'])) : 18;
        $o['send_button_bg'] = isset($input['send_button_bg']) ? sanitize_hex_color($input['send_button_bg']) : '#5b8cff';
        $o['send_icon'] = isset($input['send_icon']) ? sanitize_text_field($input['send_icon']) : '↩';
        return $o;
    }

    public function render_settings_page() {
        if (function_exists('wp_enqueue_media')) { wp_enqueue_media(); }
        if (function_exists('wp_enqueue_style')) { wp_enqueue_style('wp-color-picker'); }
        if (function_exists('wp_enqueue_script')) { wp_enqueue_script('wp-color-picker'); }
        if (function_exists('wp_enqueue_media')) { wp_enqueue_media(); }
        $opts = $this->get_options();
        include plugin_dir_path(__FILE__) . 'admin/settings-page.php';
    }

    public function handle_settings_save() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        check_admin_referer('ghost_bot_save_settings');

        $incoming = isset($_POST[ self::OPTION_KEY ]) ? (array) $_POST[ self::OPTION_KEY ] : [];
        $sanitized = $this->sanitize_options($incoming);
        update_option(self::OPTION_KEY, $sanitized);

        wp_redirect(add_query_arg(['page'=>'pp-chat-bot','updated'=>'1'], admin_url('admin.php')));
        exit;
    }

    public function add_meta_boxes() {
        add_meta_box('ghost_bot_page_box', 'Chat Bot (Page Options)', [$this, 'render_page_metabox'], ['post','page'], 'side', 'default');
    }

    public function render_page_metabox($post) {
        wp_nonce_field('ghost_bot_meta', 'ghost_bot_meta_nonce');
        $fields = [
            '_ghost_bot_disable' => get_post_meta($post->ID, '_ghost_bot_disable', true),
            '_ghost_bot_name' => get_post_meta($post->ID, '_ghost_bot_name', true),
            '_ghost_bot_theme' => get_post_meta($post->ID, '_ghost_bot_theme', true),
            '_ghost_bot_position' => get_post_meta($post->ID, '_ghost_bot_position', true),
            '_ghost_bot_welcome' => get_post_meta($post->ID, '_ghost_bot_welcome', true),
            '_ghost_bot_primary' => get_post_meta($post->ID, '_ghost_bot_primary', true),
            '_ghost_bot_open' => get_post_meta($post->ID, '_ghost_bot_open', true),
        ];
        ?>
        <p><label><input type="checkbox" name="_ghost_bot_disable" value="1" <?php checked($fields['_ghost_bot_disable'], '1'); ?>/> Disable chat on this page</label></p>
        <p><label>Bot Name<br/><input type="text" name="_ghost_bot_name" value="<?php echo esc_attr($fields['_ghost_bot_name']); ?>" class="widefat"/></label></p>
        <p><label>Theme<br/>
            <select name="_ghost_bot_theme" class="widefat">
                <option value="">— inherit —</option>
                <option value="imessage" <?php selected($fields['_ghost_bot_theme'],'imessage'); ?>>iMessage</option>
                <option value="whatsapp" <?php selected($fields['_ghost_bot_theme'],'whatsapp'); ?>>WhatsApp</option>
                <option value="minimal" <?php selected($fields['_ghost_bot_theme'],'minimal'); ?>>Minimal</option>
                <option value="custom" <?php selected($fields['_ghost_bot_theme'],'custom'); ?>>Custom</option>
            </select>
        </label></p>
        <p><label>Position<br/>
            <select name="_ghost_bot_position" class="widefat">
                <option value="">— inherit —</option>
                <option value="bottom-right" <?php selected($fields['_ghost_bot_position'],'bottom-right'); ?>>Bottom Right</option>
                <option value="bottom-left" <?php selected($fields['_ghost_bot_position'],'bottom-left'); ?>>Bottom Left</option>
            </select>
        </label></p>
        <p><label>Welcome Message<br/><textarea name="_ghost_bot_welcome" class="widefat" rows="3"><?php echo esc_textarea($fields['_ghost_bot_welcome']); ?></textarea></label></p>
        <p><label>Primary Color<br/><input type="text" name="_ghost_bot_primary" value="<?php echo esc_attr($fields['_ghost_bot_primary']); ?>" class="widefat" placeholder="#5b8cff"/></label></p>
        <p><label><input type="checkbox" name="_ghost_bot_open" value="1" <?php checked($fields['_ghost_bot_open'], '1'); ?>/> Open on load (this page)</label></p>
        <?php
    }

    public function save_meta_boxes($post_id) {
        if (!isset($_POST['ghost_bot_meta_nonce']) || !wp_verify_nonce($_POST['ghost_bot_meta_nonce'], 'ghost_bot_meta')) return;
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $map = ['_ghost_bot_disable','_ghost_bot_name','_ghost_bot_theme','_ghost_bot_position','_ghost_bot_welcome','_ghost_bot_primary','_ghost_bot_open'];
        foreach ($map as $k) {
            $v = isset($_POST[$k]) ? $_POST[$k] : '';
            if (in_array($k, ['_ghost_bot_disable','_ghost_bot_open'])) {
                update_post_meta($post_id, $k, $v ? '1':'0');
            } else {
                if ($k === '_ghost_bot_welcome') { update_post_meta($post_id, $k, wp_kses_post($v)); }
                else { update_post_meta($post_id, $k, sanitize_text_field($v)); }
            }
        }
    }

    private function is_disabled_for_current_page() {
        if (is_singular()) {
            $dis = get_post_meta(get_the_ID(), '_ghost_bot_disable', true);
            if ($dis === '1') return true;
        }
        return false;
    }

    private function build_system_prompt($opts) {
        $tone_map = [
            'friendly' => 'Be warm, concise, and approachable.',
            'professional' => 'Be formal, precise, and efficient.',
            'funny' => 'Be playful and witty without being cringy. Keep answers short.',
            'custom' => $opts['tone_custom'],
        ];
        $tone_text = isset($tone_map[$opts['tone']]) ? $tone_map[$opts['tone']] : $tone_map['friendly'];

        $kb_inline = trim(wp_strip_all_tags($opts['kb']));
        $kb_posts = $this->gather_kb_posts_text();
        $kb_all = trim($kb_inline . "\n\n" . $kb_posts);
        $kb_text = $kb_all ? "Here is site knowledge you can rely on:\n" . $kb_all : "You have no additional site knowledge.";

        $woo_cta = '';
        if (!empty($opts['woo_context']) && class_exists('WooCommerce')) {
            $shop = function_exists('wc_get_page_id') ? get_permalink(wc_get_page_id('shop')) : home_url('/shop/');
            $cats = get_terms(['taxonomy'=>'product_cat','hide_empty'=>true,'number'=>10]);
            $cat_names = is_array($cats) ? implode(', ', wp_list_pluck($cats, 'name')) : '';
            $woo_cta = "\nWooCommerce context: Shop URL: {$shop}. Top categories: {$cat_names}.";
        }

        $sys = "You are a helpful website assistant named '{$opts['bot_name']}'. {$tone_text}
Always prioritize accuracy. If unsure, ask a simple follow-up.
{$kb_text}{$woo_cta}
Answer in Markdown where useful. Keep answers compact.";
        return $sys;
    }

    private function gather_kb_posts_text() {
        $out = '';
        $q = new WP_Query([
            'post_type' => self::CPT_KB,
            'post_status' => 'publish',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);
        if ($q->have_posts()) {
            while ($q->have_posts()) { $q->the_post();
                $title = get_the_title();
                $content = wp_strip_all_tags(get_the_content(null, false));
                $out .= "\n- {$title}: " . mb_substr($content, 0, 600);
            }
            wp_reset_postdata();
        }
        return mb_substr($out, 0, 6000);
    }

    private function rate_limit_check() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : 'unknown';
        $key = 'ghost_bot_rate_' . md5($ip);
        $count = (int) get_transient($key);
        $count++;
        set_transient($key, $count, 60);
        $limit = apply_filters('ghost_bot_rate_limit_per_min', 10);
        return $count <= $limit;
    }

    private function profanity_filter($text) {
        $bad = apply_filters('ghost_bot_bad_words', ['fuck','shit','bitch','asshole','bastard']);
        return preg_replace_callback('/\b(' . implode('|', array_map('preg_quote', $bad)) . ')\b/i', function($m){
            return str_repeat('*', strlen($m[0]));
        }, $text);
    }

    public function ajax_chat() {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');
        if ( ! $this->rate_limit_check() ) {
            wp_send_json_error(['message' => 'Too many requests. Please wait a moment and try again.'], 429);
        }

        $question = isset($_POST['message']) ? wp_unslash($_POST['message']) : '';
        $history  = isset($_POST['history']) ? json_decode(stripslashes($_POST['history']), true) : [];
        $turns    = isset($_POST['turns']) ? intval($_POST['turns']) : 0;
        $consent  = !empty($_POST['consent']) ? 1 : 0;

        $question = wp_strip_all_tags($question);
        if (empty($question)) {
            wp_send_json_error(['message' => 'Empty message.'], 400);
        }

        $opts = $this->get_options();
        if (empty($opts['api_key'])) {
            wp_send_json_error(['message' => 'API key not configured.'], 500);
        }
        if ($turns >= intval($opts['max_turns'])) {
            wp_send_json_error(['message' => 'This conversation reached the turn limit. Please start a new one.'], 400);
        }
        if (!empty($opts['profanity_filter'])) {
            $question = $this->profanity_filter($question);
        }

        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $this->build_system_prompt($opts)];
        if (is_array($history)) {
            foreach ($history as $h) {
                if (!isset($h['role'], $h['content'])) continue;
                $role = $h['role'] === 'assistant' ? 'assistant' : 'user';
                $messages[] = ['role' => $role, 'content' => wp_strip_all_tags($h['content'])];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        $body = [
            'model' => $opts['model'],
            'messages' => $messages,
            'temperature' => floatval($opts['temperature']),
            'max_tokens' => intval($opts['max_tokens']),
        ];

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 30,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $opts['api_key'],
            ],
            'body' => wp_json_encode($body),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()], 500);
        }

        $code = wp_remote_retrieve_response_code($response);
        $raw  = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);

        if ($code !== 200 || empty($data['choices'][0]['message']['content'])) {
            $err = !empty($data['error']['message']) ? $data['error']['message'] : 'Unknown API error.';
            wp_send_json_error(['message' => $err, 'debug' => $raw], $code ?: 500);
        }

        $answer = trim($data['choices'][0]['message']['content']);
        if (!empty($opts['profanity_filter'])) {
            $answer = $this->profanity_filter($answer);
        }

        // Logging
        if (!empty($opts['enable_logging']) && (!$opts['consent_required'] || $consent)) {
            $log_id = wp_insert_post([
                'post_type' => self::CPT_LOG,
                'post_status' => 'private',
                'post_title' => 'Chat ' . current_time('mysql'),
                'post_content' => "Q: {$question}\n\nA: {$answer}",
            ]);
            if ($log_id) {
                update_post_meta($log_id, '_ip', isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '');
                update_post_meta($log_id, '_ua', isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '');
                update_post_meta($log_id, '_consent', $consent ? '1':'0');
            }
        }

        wp_send_json_success(['answer' => wp_kses_post($answer)]);
    }

    public function ajax_test() {
        if (!current_user_can('manage_options')) wp_send_json_error(['message'=>'Forbidden'], 403);
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $api_key = isset($_POST['api_key']) ? trim(sanitize_text_field($_POST['api_key'])) : '';
        $model   = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        if (empty($api_key)) {
            $opts = $this->get_options();
            $api_key = $opts['api_key'];
            if (empty($model)) $model = $opts['model'];
        }
        if (empty($api_key)) wp_send_json_error(['message'=>'No API key provided.'], 400);
        if (empty($model)) $model = 'gpt-4o-mini';

        $t0 = microtime(true);
        $body = [
            'model' => $model,
            'messages' => [['role'=>'user','content'=>'ping']],
            'max_tokens' => 1,
            'temperature' => 0.0,
        ];
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 20,
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body' => wp_json_encode($body),
        ]);
        $latency = round((microtime(true) - $t0) * 1000);
        if (is_wp_error($response)) wp_send_json_error(['message'=>$response->get_error_message()], 500);

        $code = wp_remote_retrieve_response_code($response);
        $raw = wp_remote_retrieve_body($response);
        $data = json_decode($raw, true);
        if ($code !== 200) {
            $err = !empty($data['error']['message']) ? $data['error']['message'] : ('HTTP ' . $code);
            wp_send_json_error(['message'=>$err, 'debug'=>$raw], $code);
        }
        $model_used = isset($data['model']) ? $data['model'] : $model;
        wp_send_json_success(['message'=>'Connected OK','model'=>$model_used,'latency_ms'=>$latency]);
    }

    public function render_shortcode($atts) {
        ob_start(); ?>
        <div class="ghost-bot-inline-launcher" data-launcher="1">
            <button class="ghost-bot-toggle"><?php echo esc_html($this->get_options()['bot_name']); ?> 💬</button>
        </div>
        <?php return ob_get_clean();
    }

    public function export_logs_csv() {
        if (!current_user_can('manage_options')) wp_die('Forbidden');
        check_admin_referer('ghost_bot_export');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=pp-bot-logs.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date','Question','Answer','IP','User Agent','Consent']);

        $q = new WP_Query([
            'post_type' => self::CPT_LOG,
            'post_status' => ['private','publish'],
            'posts_per_page' => -1,
            'orderby' => 'date', 'order' => 'DESC',
            'no_found_rows' => true,
        ]);
        if ($q->have_posts()) {
            while ($q->have_posts()) { $q->the_post();
                $content = get_post_field('post_content', get_the_ID());
                $qtxt = ''; $atxt = '';
                if (strpos($content, "\n\nA: ") !== false) {
                    $parts = explode("\n\nA: ", $content, 2);
                    $qtxt = preg_replace('/^Q:\s*/','', $parts[0]);
                    $atxt = $parts[1];
                } else {
                    $qtxt = $content;
                }
                fputcsv($out, [
                    get_the_date('c'),
                    $qtxt,
                    $atxt,
                    get_post_meta(get_the_ID(), '_ip', true),
                    get_post_meta(get_the_ID(), '_ua', true),
                    get_post_meta(get_the_ID(), '_consent', true) ? 'yes':'no',
                ]);
            }
            wp_reset_postdata();
        }
        fclose($out);
        exit;
    }
}

new Ghost_Chat_Balloon_Bot();
