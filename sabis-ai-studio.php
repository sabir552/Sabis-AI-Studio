<?php
/**
 * Plugin Name: Sabis AI Studio
 * Plugin URI: https://github.com/sabir-arain/sabis-ai-studio
 * Description: Autonomous No-Code Full-Page AI Visual Builder powered by Gemini, Groq, GrapesJS, and Tailwind CSS.
 * Version: 1.1.0
 * Author: Elite Principal WordPress Core Developer & Senior AI Systems Architect
 * Author URI: https://ai.studio/build
 * License: Apache-2.0
 * Text Domain: sabis-ai-studio
 */

if (!defined('ABSPATH')) {
    exit;
}

class Sabis_AI_Studio {

    private static $instance = null;
    
    const OPTION_API_KEY = 'sabis_gemini_api_key';
    const OPTION_GROQ_KEY = 'sabis_groq_api_key';
    const OPTION_WEBHOOK_URL = 'sabis_sheets_webhook_url';
    const OPTION_STRIPE_KEY = 'sabis_stripe_publishable_key';

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('init', array($this, 'init_features'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // AJAX Endpoints
        add_action('wp_ajax_sabis_generate_page_via_gemini', array($this, 'generate_page_via_gemini_ajax'));
        add_action('wp_ajax_sabis_publish_visual_page', array($this, 'publish_visual_page_ajax'));
        add_action('wp_ajax_sabis_copywrite_via_gemini', array($this, 'copywrite_via_gemini_ajax'));
        add_action('wp_ajax_sabis_translate_via_gemini', array($this, 'translate_via_gemini_ajax'));
        add_action('wp_ajax_sabis_save_section_block', array($this, 'save_section_block_ajax'));
        add_action('wp_ajax_sabis_auto_webp_upload', array($this, 'auto_webp_upload_ajax'));
        add_action('wp_ajax_sabis_regenerate_section_via_gemini', array($this, 'regenerate_section_via_gemini_ajax'));
        add_action('wp_ajax_sabis_get_wordpress_page', array($this, 'get_wordpress_page_ajax'));
        add_action('wp_ajax_sabis_screenshot_to_code', array($this, 'screenshot_to_code_ajax'));
        add_action('wp_ajax_sabis_generate_component', array($this, 'generate_component_ajax'));

        add_shortcode('sabis_section', array($this, 'render_section_shortcode'));
        add_filter('wp_handle_upload', array($this, 'auto_webp_media_handler'), 10, 2);

        // NATIVE WORDPRESS INTEGRATION SHORTCUTS & ACTIONS
        add_filter('page_row_actions', array($this, 'add_sabis_row_actions'), 10, 2);
        add_filter('post_row_actions', array($this, 'add_sabis_row_actions'), 10, 2);
        add_action('admin_bar_menu', array($this, 'add_sabis_admin_bar_menu'), 999);
        add_action('admin_enqueue_scripts', array($this, 'inject_gutenberg_sabis_buttons'));
    }

    public function init_features() {
        register_post_type('sabis_section', array(
            'labels' => array(
                'name'               => __('AI Sections', 'sabis-ai-studio'),
                'singular_name'      => __('AI Section', 'sabis-ai-studio'),
                'add_new_item'       => __('Add New AI Section', 'sabis-ai-studio'),
                'edit_item'          => __('Edit AI Section', 'sabis-ai-studio'),
            ),
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'supports'           => array('title', 'editor', 'custom-fields'),
            'hierarchical'       => false,
            'has_archive'        => false,
            'rewrite'            => false,
        ));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Sabis AI Studio', 'sabis-ai-studio'),
            __('Sabis AI Studio', 'sabis-ai-studio'),
            'manage_options',
            'sabis-ai-studio',
            array($this, 'render_admin_dashboard_ui'),
            'dashicons-superhero',
            6
        );
    }

    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_sabis-ai-studio' !== $hook) {
            return;
        }
        wp_enqueue_style('grapesjs-css', 'https://unpkg.com/grapesjs/dist/css/grapes.min.css', array(), '1.10.4');
        wp_enqueue_script('grapesjs-js', 'https://unpkg.com/grapesjs', array(), '1.10.4', true);

        wp_add_inline_style('grapesjs-css', "
            .sabis-dashboard-wrap { 
                max-width: 100%; 
                overflow-x: hidden;
                box-sizing: border-box; 
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                padding: 28px; 
                background: linear-gradient(135deg, #070a13 0%, #0d1222 100%); 
                color: #f1f5f9;
                min-height: calc(100vh - 40px); 
            }
            .sabis-dashboard-wrap h1, .sabis-dashboard-wrap h2, .sabis-dashboard-wrap h3, .sabis-dashboard-wrap h4 {
                color: #f1f5f9 !important;
            }
            .sabis-card { 
                background: rgba(13, 17, 30, 0.65); 
                backdrop-filter: blur(16px);
                -webkit-backdrop-filter: blur(16px);
                border: 1px solid rgba(234, 179, 8, 0.12);
                box-shadow: 0 16px 40px 0 rgba(0, 0, 0, 0.45), inset 0 1px 1px rgba(255, 255, 255, 0.05); 
                border-radius: 20px; 
                padding: 32px; 
                margin-bottom: 24px; 
                transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            }
            .sabis-card:hover {
                border-color: rgba(234, 179, 8, 0.3);
                box-shadow: 0 20px 48px 0 rgba(0, 0, 0, 0.55), 0 0 20px rgba(234, 179, 8, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.08);
            }
            .sabis-btn { 
                display: inline-flex; 
                align-items: center; 
                justify-content: center; 
                gap: 8px; 
                font-weight: 600; 
                padding: 12px 24px; 
                border-radius: 10px; 
                border: none; 
                cursor: pointer; 
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
                font-size: 14px; 
                text-decoration: none; 
                color: #fff !important;
            }
            .sabis-btn-primary { 
                background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 100%); 
                box-shadow: 0 4px 14px rgba(139, 92, 246, 0.4);
            }
            .sabis-btn-primary:hover:not(:disabled) { 
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(139, 92, 246, 0.6);
                filter: brightness(1.15);
            }
            .sabis-btn-primary:active:not(:disabled) {
                transform: translateY(1px);
            }
            .sabis-btn-success { 
                background: linear-gradient(135deg, #10b981 0%, #059669 100%); 
                box-shadow: 0 4px 14px rgba(16, 185, 129, 0.4);
            }
            .sabis-btn-success:hover:not(:disabled) { 
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
                filter: brightness(1.15);
            }
            .sabis-btn-success:active:not(:disabled) {
                transform: translateY(1px);
            }
            .sabis-btn-danger { 
                background: linear-gradient(135deg, #ef4444 0%, #b91c1c 100%); 
                box-shadow: 0 4px 14px rgba(239, 68, 68, 0.4);
            }
            .sabis-btn-danger:hover:not(:disabled) { 
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(239, 68, 68, 0.6);
                filter: brightness(1.15);
            }
            .sabis-btn-danger:active:not(:disabled) {
                transform: translateY(1px);
            }
            .sabis-btn:disabled {
                opacity: 0.5;
                cursor: not-allowed;
                transform: none !important;
                box-shadow: none !important;
            }
            .sabis-toolbar { 
                display: flex; 
                flex-wrap: wrap; 
                gap: 12px; 
                margin-bottom: 15px; 
                align-items: center; 
            }
            .sabis-editor-container { 
                border: 1px solid rgba(255, 255, 255, 0.1); 
                border-radius: 16px; 
                overflow: hidden; 
                height: calc(100vh - 425px); 
                min-height: 550px; 
                display: flex; 
                flex-direction: column; 
                background: #1e293b; 
                box-shadow: 0 15px 35px rgba(0,0,0,0.3);
            }
            #gjs { 
                flex-grow: 1; 
            }
            .gjs-frame {
                max-width: 100% !important;
                box-sizing: border-box !important;
            }
                        /* GrapesJS selected component toolbar protection to prevent button clipping */
            .gjs-toolbar {
                opacity: 1 !important;
                transform: none !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                width: auto !important;
                flex-wrap: nowrap !important;
                max-width: none !important;
                background-color: rgba(30, 41, 59, 0.98) !important;
                border: 1px solid rgba(255, 255, 255, 0.15) !important;
                border-radius: 8px !important;
                padding: 4px !important;
                gap: 4px !important;
                box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
                overflow: visible !important;
                z-index: 9999 !important;
            }
            #sabis-btn-ai-redesign, .sabis-gjs-ai-btn {
                position: absolute !important;
                bottom: calc(100% + 8px) !important;
                left: 0 !important;
                width: max-content !important;
                max-width: 130px !important;
                display: inline-flex !important;
                padding: 4px 10px !important;
                z-index: 999999 !important;
                font-size: 11px !important;
                background-color: #3b82f6 !important;
                color: #ffffff !important;
                font-weight: 700 !important;
                border-radius: 6px !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 4px !important;
                height: 28px !important;
                line-height: 28px !important;
                transition: all 0.2s ease-in-out !important;
                box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4) !important;
                white-space: nowrap !important;
            }
            #sabis-btn-ai-redesign:hover, .sabis-gjs-ai-btn:hover {
                background-color: #2563eb !important;
                transform: scale(1.04) !important;
                box-shadow: 0 6px 14px rgba(37, 99, 235, 0.5) !important;
            }
            /* Prevent horizontal overflow on GrapesJS iframe workspace and canvas container */
            #gjs-wrapper {
                max-width: 100vw !important;
                overflow-x: hidden !important;
                box-sizing: border-box !important;
            }
            .gjs-cv-canvas {
                max-width: 100% !important;
                overflow: hidden !important;
            }
            /* Responsive media queries for mobile-first layout support */
            @media (max-width: 768px) {
                .sabis-dashboard-wrap {
                    padding: 12px !important;
                }
                .sabis-card {
                    padding: 16px !important;
                }
                .sabis-editor-container {
                    min-height: 480px !important;
                }
                .sabis-input-group {
                    flex-direction: column !important;
                }
            }
            .sabis-input-group { 
                display: flex; 
                align-items: stretch; 
                gap: 12px; 
                width: 100%; 
                margin-bottom: 16px; 
            }
            .sabis-textarea { 
                flex-grow: 1; 
                min-height: 80px; 
                padding: 16px; 
                border-radius: 12px; 
                background: #0f172a;
                border: 1px solid rgba(255, 255, 255, 0.12);
                color: #f1f5f9;
                font-size: 14px; 
                resize: vertical; 
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.2); 
                transition: all 0.3s ease;
            }
            .sabis-textarea:focus {
                outline: none;
                border-color: #8b5cf6;
                box-shadow: 0 0 15px rgba(139, 92, 246, 0.45), inset 0 2px 4px rgba(0,0,0,0.2);
            }
            .sabis-mic-btn { 
                background: #1e293b; 
                border: 1px solid rgba(255, 255, 255, 0.12); 
                color: #f1f5f9;
                padding: 0 20px; 
                border-radius: 12px; 
                cursor: pointer; 
                font-size: 18px;
                transition: all 0.3s ease; 
            }
            .sabis-mic-btn:hover { 
                background: #334155; 
                transform: scale(1.05);
            }
            .sabis-mic-active { 
                background: #ef4444 !important; 
                color: #fff !important; 
                box-shadow: 0 0 15px #ef4444;
                animation: sabis-pulse 1.5s infinite; 
            }
            .sabis-send-btn {
                background: linear-gradient(135deg, #06b6d4 0%, #8b5cf6 100%);
                border: none;
                color: #fff;
                padding: 0 20px;
                border-radius: 12px;
                cursor: pointer;
                font-size: 18px;
                transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }
            .sabis-send-btn:hover {
                transform: scale(1.05);
                box-shadow: 0 0 15px rgba(139, 92, 246, 0.5);
                filter: brightness(1.1);
            }
            @keyframes sabis-pulse { 
                0% { opacity: 1; transform: scale(1); } 
                50% { opacity: 0.6; transform: scale(0.95); } 
                100% { opacity: 1; transform: scale(1); } 
            }
            .sabis-status-badge { 
                inline-size: fit-content; 
                padding: 8px 16px; 
                border-radius: 9999px; 
                font-size: 12px; 
                font-weight: 600; 
                display: inline-flex; 
                align-items: center; 
                gap: 8px; 
                background: rgba(15, 23, 42, 0.6);
                border: 1px solid rgba(255, 255, 255, 0.12);
                color: #cbd5e1;
                transition: all 0.3s ease;
            }
            .sabis-status-ready .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #10b981;
                box-shadow: 0 0 8px #10b981;
            }
            .sabis-status-loading .status-dot {
                width: 8px;
                height: 8px;
                border-radius: 50%;
                background: #f59e0b;
                box-shadow: 0 0 8px #f59e0b;
                animation: sabis-glow-pulse 1s infinite alternate;
            }
            @keyframes sabis-glow-pulse {
                0% { transform: scale(0.9); opacity: 0.6; box-shadow: 0 0 4px #f59e0b; }
                100% { transform: scale(1.2); opacity: 1; box-shadow: 0 0 12px #f59e0b; }
            }
            .sabis-context-menu { 
                position: absolute; 
                z-index: 999999; 
                background: #111827; 
                border: 1px solid rgba(255,255,255,0.1);
                color: #f3f4f6; 
                border-radius: 12px; 
                padding: 10px; 
                display: none; 
                flex-direction: column; 
                gap: 6px; 
                box-shadow: 0 10px 25px rgba(0,0,0,0.5); 
                backdrop-filter: blur(8px);
            }
            .sabis-context-btn { 
                background: none; 
                border: none; 
                text-align: left; 
                padding: 8px 16px; 
                color: #e5e7eb; 
                cursor: pointer; 
                border-radius: 8px; 
                font-size: 13px; 
                font-weight: 500; 
                transition: all 0.2s ease; 
            }
            .sabis-context-btn:hover { 
                background: rgba(255, 255, 255, 0.08); 
                color: #fff;
            }
            .sabis-viewport-btn {
                background: rgba(255,255,255,0.05);
                border: 1px solid rgba(255,255,255,0.1);
                color: #cbd5e1;
                padding: 6px 12px;
                border-radius: 8px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            .sabis-viewport-btn:hover {
                background: rgba(255,255,255,0.12);
                color: #fff;
            }
            .sabis-viewport-btn.active {
                background: #8b5cf6;
                border-color: #8b5cf6;
                color: #fff;
                box-shadow: 0 0 10px rgba(139,92,246,0.4);
            }
            select, input[type="password"], input[type="url"] {
                background: #0f172a !important;
                border: 1px solid rgba(255, 255, 255, 0.12) !important;
                color: #f1f5f9 !important;
                border-radius: 10px !important;
                padding: 10px 14px !important;
                transition: all 0.3s ease !important;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.2) !important;
            }
            select:focus, input[type="password"]:focus, input[type="url"]:focus {
                outline: none !important;
                border-color: #8b5cf6 !important;
                box-shadow: 0 0 12px rgba(139, 92, 246, 0.45), inset 0 2px 4px rgba(0,0,0,0.2) !important;
            }
        ");
    }

    public function render_admin_dashboard_ui() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'sabis-ai-studio'));
        }

        if (isset($_POST['sabis_save_settings_nonce']) && wp_verify_nonce($_POST['sabis_save_settings_nonce'], 'sabis_save_settings')) {
            if (isset($_POST['gemini_api_key'])) {
                update_option(self::OPTION_API_KEY, sanitize_text_field($_POST['gemini_api_key']));
            }
            if (isset($_POST['groq_api_key'])) {
                update_option(self::OPTION_GROQ_KEY, sanitize_text_field($_POST['groq_api_key']));
            }
            if (isset($_POST['sheets_webhook_url'])) {
                update_option(self::OPTION_WEBHOOK_URL, esc_url_raw($_POST['sheets_webhook_url']));
            }
            if (isset($_POST['stripe_publishable_key'])) {
                update_option(self::OPTION_STRIPE_KEY, sanitize_text_field($_POST['stripe_publishable_key']));
            }
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'sabis-ai-studio') . '</p></div>';
        }

        $api_key = get_option(self::OPTION_API_KEY, '');
        $groq_api_key = get_option(self::OPTION_GROQ_KEY, '');
        $webhook_url = get_option(self::OPTION_WEBHOOK_URL, '');
        $stripe_key = get_option(self::OPTION_STRIPE_KEY, '');
        ?>
        <div class="sabis-dashboard-wrap">
            <h1 class="wp-heading-inline" style="font-weight: 800; font-size: 32px; letter-spacing: -0.025em; margin-bottom: 24px; display: inline-flex; align-items: center;">
                <!-- Stunning Inline World-Class SVG Gold Logo -->
                <svg class="sabis-svg-logo animate-pulse" width="46" height="46" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 14px; filter: drop-shadow(0 0 12px rgba(234, 179, 8, 0.45));">
                    <defs>
                        <linearGradient id="wpSabisGoldGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#FDE047" />
                            <stop offset="50%" stop-color="#EAB308" />
                            <stop offset="100%" stop-color="#CA8A04" />
                        </linearGradient>
                        <linearGradient id="wpSabisGlowNeon" x1="0%" y1="100%" x2="100%" y2="0%">
                            <stop offset="0%" stop-color="#10B981" />
                            <stop offset="100%" stop-color="#3B82F6" />
                        </linearGradient>
                    </defs>
                    <circle cx="50" cy="50" r="44" stroke="url(#wpSabisGoldGrad)" stroke-width="2.5" stroke-dasharray="5 5" opacity="0.8" />
                    <circle cx="50" cy="50" r="35" stroke="url(#wpSabisGlowNeon)" stroke-width="1.5" opacity="0.5" />
                    <polygon points="50,15 82,33 82,67 50,85 18,67 18,33" stroke="url(#wpSabisGoldGrad)" stroke-width="1.5" stroke-linecap="round" fill="rgba(15, 23, 42, 0.65)" />
                    <path d="M40 38 C40 31, 60 31, 60 38 C60 44, 40 46, 40 52 C40 59, 60 59, 60 52" stroke="url(#wpSabisGoldGrad)" stroke-width="5.5" stroke-linecap="round" stroke-linejoin="round" />
                    <circle cx="50" cy="50" r="3" fill="#FACC15" />
                    <circle cx="18" cy="33" r="2.5" fill="#3B82F6" />
                    <circle cx="82" cy="33" r="2.5" fill="#10B981" />
                    <circle cx="50" cy="85" r="2.5" fill="#FACC15" />
                </svg>
                <span style="background: linear-gradient(135deg, #FFF 0%, #FACC15 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight:900;">Sabis AI Studio</span>
                <span style="font-size: 13px; font-weight: 700; color: #facc15; background: rgba(234, 179, 8, 0.12); padding: 4px 12px; border-radius: 9999px; margin-left:14px; border: 1px solid rgba(234, 179, 8, 0.25); text-transform: uppercase; letter-spacing: 1px;">
                    Ultimate Professional v2.0
                </span>
            </h1>

            <div class="sabis-card" style="margin-top: 15px;">
                <h3 style="margin-top:0; font-weight:700; font-size:18px; border-bottom:1px solid rgba(255,255,255,0.08); padding-bottom:12px; color: #f1f5f9; display: flex; align-items: center; gap: 8px;">
                    <span>🔌 Cloud Credentials & Integration Keys</span>
                </h3>
                <form method="post" action="" style="display:flex; flex-direction:column; gap:12px;">
                    <?php wp_nonce_field('sabis_save_settings', 'sabis_save_settings_nonce'); ?>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:15px;">
                        <div>
                            <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#94a3b8;">
                                Google Gemini API Key
                            </label>
                            <input type="password" name="gemini_api_key" value="<?php echo esc_attr($api_key); ?>" placeholder="Enter Gemini APi Key" style="width:100%;" />
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#94a3b8;">
                                Groq API Secret Key (Llama 3 Tasks)
                            </label>
                            <input type="password" name="groq_api_key" value="<?php echo esc_attr($groq_api_key); ?>" placeholder="gsk_..." style="width:100%;" />
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#94a3b8;">
                                Google Sheets External Webhook
                            </label>
                            <input type="url" name="sheets_webhook_url" value="<?php echo esc_url($webhook_url); ?>" placeholder="https://script.google.com/macros/s/..." style="width:100%;" />
                        </div>
                        <div>
                            <label style="display:block; font-weight:600; font-size:13px; margin-bottom:6px; color:#94a3b8;">
                                Stripe Publishable Key (pk_...)
                            </label>
                            <input type="password" name="stripe_publishable_key" value="<?php echo esc_attr($stripe_key); ?>" placeholder="pk_live_... / pk_test_..." style="width:100%;" />
                        </div>
                    </div>
                    <button type="submit" class="sabis-btn sabis-btn-primary" style="align-self:flex-start; padding: 10px 20px; font-size:13px; margin-top: 6px;">
                        Save Core Configuration
                    </button>
                </form>
            </div>

            <div class="sabis-card">
                <h3 style="margin-top:0; font-weight:700; font-size:18px; color:#f1f5f9;">🚀 Master Controls (AI Canvas Creator)</h3>
                
                <div class="sabis-input-group">
                    <textarea id="sabis-prompt" class="sabis-textarea" placeholder="Speak or type a prompt... e.g., 'A gorgeous grid layout for organic green tea, premium layout aesthetics, and gold accents.'"></textarea>
                    <button type="button" id="sabis-mic-btn" class="sabis-mic-btn" title="Speak to Voice Dictate">🎤</button>
                    <input type="file" id="sabis-reference-image" accept="image/*" style="display:none;" />
                    <button type="button" id="sabis-ref-image-btn" class="sabis-mic-btn" style="padding: 0 15px; font-size: 13px; display: inline-flex; align-items: center; gap: 4px; border-radius: 12px; font-weight: 500;" title="Upload Reference Image to guide layout aesthetics">📁 Reference Image</button>
                    <!-- Text Send Button -->
                    <button type="button" id="sabis-send-btn" class="sabis-send-btn" title="Send text prompt to generator">🚀</button>
                </div>

                <div class="sabis-multi-page-setup" style="margin-bottom: 20px; padding: 15px; border-top: 1px solid rgba(255,255,255,0.08); display: flex; flex-direction: column; gap: 12px; background: rgba(15, 23, 42, 0.2); border-radius: 12px;">
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span style="font-size: 13px; font-weight: 600; color: #94a3b8;">Pages Count:</span>
                            <select id="sabis-page-count" style="padding: 4px 10px; font-size: 12px; background: #0f172a; color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 4px;">
                                <option value="1">1 Page</option>
                                <option value="2">2 Pages</option>
                                <option value="3">3 Pages</option>
                                <option value="4">4 Pages</option>
                                <option value="5">5 Pages</option>
                                <option value="6">6 Pages</option>
                                <option value="7">7 Pages</option>
                                <option value="8">8 Pages</option>
                                <option value="9">9 Pages</option>
                                <option value="10">10 Pages</option>
                            </select>
                        </div>
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 14px; font-size: 13px;">
                            <span style="font-weight: 600; color: #94a3b8;">Included Templates:</span>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Home" checked style="accent-color: #8b5cf6;" /> Home
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="About Us" style="accent-color: #8b5cf6;" /> About Us
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Services" style="accent-color: #8b5cf6;" /> Services
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Product Landing" style="accent-color: #8b5cf6;" /> Product Landing
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Product Specs" style="accent-color: #8b5cf6;" /> Product Specs
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Pricing Plans" style="accent-color: #8b5cf6;" /> Pricing Plans
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Testimonials" style="accent-color: #8b5cf6;" /> Testimonials
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="FAQs" style="accent-color: #8b5cf6;" /> FAQs
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Contact" style="accent-color: #8b5cf6;" /> Contact
                            </label>
                            <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer; color: #cbd5e1;">
                                <input type="checkbox" class="sabis-page-template-cb" value="Custom Business Page" style="accent-color: #8b5cf6;" /> Custom Business Page
                            </label>
                        </div>
                    </div>

                    <!-- Existing Page Re-designer Addition Panel -->
                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 16px; border-top: 1px dashed rgba(255,255,255,0.1); padding-top: 12px; margin-top: 4px;">
                        <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 10px;">
                            <span style="font-size: 13px; font-weight: 600; color: #a5b4fc; display: inline-flex; align-items: center; gap: 4px;">🔄 Select Existing Page to Re-design:</span>
                            <select id="sabis-existing-page-selector" style="max-width: 280px; padding: 4px 10px; font-size: 12px; background: #0f172a; color: #fff; border: 1px solid rgba(255,255,255,0.15); border-radius: 4px; cursor: pointer;">
                                <option value="">-- Choose Published Page --</option>
                                <?php
                                $wp_pages = get_posts(array(
                                    'post_type' => 'page',
                                    'post_status' => 'publish',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC'
                                ));
                                foreach ($wp_pages as $p) {
                                    echo '<option value="' . esc_attr($p->ID) . '">' . esc_html($p->post_title) . ' (#' . esc_attr($p->ID) . ')</option>';
                                }
                                ?>
                            </select>
                            <button type="button" id="sabis-load-existing-page-btn" class="sabis-btn sabis-btn-success" style="padding: 4px 12px; font-size: 11px; font-weight: 700; height: 30px; line-height: normal; display: inline-flex; align-items: center; gap: 4px;" title="Load this page's HTML structure into GrapesJS canvas">
                                📥 Load Page into Studio
                            </button>
                        </div>
                    </div>

                    <!-- Workspace Tabs Nav -->
                    <div id="sabis-editor-tabs-container" style="display: none; align-items: center; gap: 8px; background: rgba(15, 23, 42, 0.4); border: 1px dashed rgba(255,255,255,0.1); border-radius: 10px; padding: 10px 14px; margin-top: 4px;">
                        <span style="font-size: 12px; font-weight: 700; color: #22d3ee; margin-right: 6px; display: inline-flex; align-items: center; gap: 4px;">📂 AI Multi-Page Switcher:</span>
                        <div id="sabis-tabs-nav" style="display: flex; gap: 8px; flex-wrap: wrap;"></div>
                    </div>
                </div>

                <div class="sabis-toolbar">
                    <button type="button" id="btn-generate-ai" class="sabis-btn sabis-btn-primary">
                        ✨ Create Complete Visual Page
                    </button>
                    <button type="button" id="btn-publish-wp" class="sabis-btn sabis-btn-success" disabled>
                        💾 Publish Design to WordPress
                    </button>
                    <button type="button" id="btn-reset-canvas" class="sabis-btn sabis-btn-danger">
                        🔄 Reset Canvas
                    </button>
                    <div id="generation-status" class="sabis-status-badge sabis-status-ready">
                        <span class="status-dot"></span>
                        <span class="status-text">Standby - AI Engine Ready</span>
                    </div>
                </div>
            </div>

            <div class="sabis-card" style="padding:0; overflow:hidden;">
                <div style="background:#1e293b; border-bottom:1px solid rgba(255,255,255,0.08); padding:12px; display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                    <div style="display:flex; align-items:center; gap:6px;">
                        <span style="font-size:12px; font-weight:600; color:#94a3b8;">Design Theme:</span>
                        <select id="sabis-theme-preset" style="padding:4px 8px; border-radius:4px; font-size:12px; background: #1e293b; color: #fff; border: 1px solid rgba(255,255,255,0.15); cursor: pointer;">
                            <option value="Minimal Light">Minimal Light</option>
                            <option value="Cyberpunk Dark">Cyberpunk Dark</option>
                            <option value="Royal Gold">Royal Gold</option>
                            <option value="Sunset Orange & Charcoal">Sunset Orange & Charcoal</option>
                            <option value="Forest Emerald & Sage">Forest Emerald & Sage</option>
                            <option value="Deep Ocean Blues">Deep Ocean Blues</option>
                            <option value="Dracula Vampire">Dracula Vampire</option>
                            <option value="Custom Accent">✒️ Custom Accent Profile...</option>
                        </select>
                        <input type="text" id="sabis-theme-custom-desc" placeholder="Specify custom accent description..." style="display:none; padding:4px 8px; border-radius:4px; font-size:11px; background:#0f172a; color:#fff; border:1px solid rgba(255,255,255,0.15); width:240px; margin-left:6px;" />
                    </div>

                    <div style="display:flex; align-items:center; gap:6px; border-left:1px solid rgba(255,255,255,0.1); padding-left:12px;">
                        <button type="button" id="btn-toggle-dark" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;" title="Injects Tailwind active dark mode switches">
                            🌗 Invert Mode
                        </button>
                    </div>

                    <div style="display:flex; align-items:center; gap:6px; border-left:1px solid rgba(255,255,255,0.1); padding-left:12px;">
                        <button type="button" id="btn-translate-lang" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;" title="Convert layout text to Urdu (RTL)">
                            🇵🇰 Translate Urdu (RTL)
                        </button>
                        <button type="button" id="btn-translate-eng" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;">
                            🇬🇧 Reset English
                        </button>
                    </div>

                    <!-- Custom Device / Viewport Preview Switcher Toolbar -->
                    <div style="display:flex; align-items:center; gap:6px; border-left:1px solid rgba(255,255,255,0.1); padding-left:12px;">
                        <button type="button" id="btn-view-desktop" class="sabis-viewport-btn active" title="Simulate Laptop / PC Desktop Canvas">
                            🖥️ Laptop
                        </button>
                        <button type="button" id="btn-view-tablet" class="sabis-viewport-btn" title="Simulate Tablet Width Canvas" style="padding: 6px 12px; font-size:12px;">
                            📟 Tablet
                        </button>
                        <button type="button" id="btn-view-mobile" class="sabis-viewport-btn" title="Simulate Mobile Phone Width Canvas" style="padding: 6px 12px; font-size:12px;">
                            📱 Mobile
                        </button>
                    </div>

                    <div style="margin-left:auto; display:flex; gap:8px;">
                        <input type="file" id="screenshot-file-picker" accept="image/*" style="display:none;" />
                        <button type="button" id="btn-upload-inspiration" class="sabis-btn sabis-btn-primary" style="padding: 6px 12px; font-size: 12px; background: #8b5cf6;" title="Upload layout screenshot to extract design">
                            📸 Upload Design Inspiration
                        </button>
                        <button type="button" id="btn-export-zip" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); border-radius:4px; padding:4px 8px; font-size:12px; cursor:pointer;">
                            📦 Export Static HTML
                        </button>
                    </div>
                </div>

                <div class="sabis-editor-container">
                    <div id="gjs"></div>
                </div>
            </div>
            
            <div id="sabis-publish-feedback" class="sabis-card" style="display:none; background:rgba(16, 185, 129, 0.1); border-color:#10b981;">
                <h4 style="margin:0 0 6px 0; color:#10b981; font-weight:700;">🎉 WordPress page published with Premium Meta & SEO Schema!</h4>
                <p style="margin:0; font-size:13px; color:#a7f3d0;" id="feedback-post-link"></p>
                <div style="margin-top:10px; font-family:monospace; font-size:11px; color:#34d399; background:rgba(15, 23, 42, 0.5); padding:8px; border-radius:4px;" id="feedback-audit-log"></div>
            </div>
        </div>

        <div id="sabis-context-menu" class="sabis-context-menu">
            <div style="font-size:10px; color:#94a3b8; font-weight:700; padding:4px 8px; border-bottom:1px solid rgba(255,255,255,0.08); margin-bottom:4px;">💎 COPYWRITING (GROQ LLAMA 3)</div>
            <button class="sabis-context-btn" data-action="Make Catchy">⚡ Make Catchy</button>
            <button class="sabis-context-btn" data-action="Shorten">✂️ Shorten</button>
            <button class="sabis-context-btn" data-action="Change Tone">💼 Executive Tone</button>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof grapesjs === 'undefined') {
                console.error('GrapesJS failed to load.');
                return;
            }

            const editor = grapesjs.init({
                container: '#gjs',
                fromElement: true,
                height: '100%',
                width: 'auto',
                storageManager: false,
                deviceManager: {
                    devices: [
                        { id: 'desktop', name: 'Desktop', width: '' },
                        { id: 'tablet', name: 'Tablet', width: '768px', widthMedia: '992px' },
                        { id: 'mobile', name: 'Mobile', width: '375px', widthMedia: '480px' }
                    ]
                },
                canvas: {
                    styles: [],
                    scripts: ['https://cdn.tailwindcss.com']
                },
                panels: { defaults: [] },
                assetManager: {
                    upload: true,
                    dropzone: true,
                    customUpload: function(files) {
                        const formData = new FormData();
                        formData.append('action', 'sabis_auto_webp_upload');
                        formData.append('nonce', '<?php echo wp_create_nonce("sabis_upload_nonce"); ?>');
                        for (let i = 0; i < files.length; i++) {
                            formData.append('files[]', files[i]);
                        }
                        jQuery.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: formData,
                            contentType: false,
                            processData: false,
                            success: function(response) {
                                if (response.success && response.data) {
                                    editor.AssetManager.add(response.data);
                                    const selected = editor.getSelected();
                                    if (selected && selected.get('type') === 'image') {
                                        selected.set('src', response.data[0]);
                                    }
                                } else {
                                    alert('Image compression and WebP conversion failed.');
                                }
                            }
                        });
                    }
                },
            });

            // Context Menu Coordination Transmission Refactoring
            const contextMenu = document.getElementById('sabis-context-menu');
            editor.on('canvas:load', () => {
                const head = editor.Canvas.getHead();
                if (head) {
                    const styleEl = document.createElement('style');
                    styleEl.innerHTML = `
                        body { 
                            overflow-x: hidden !important; 
                            width: 100% !important; 
                            margin: 0; 
                            padding: 0; 
                            box-sizing: border-box !important; 
                        }
                        * { 
                            box-sizing: border-box !important; 
                            max-width: 100% !important; 
                        }
                    `;
                    head.appendChild(styleEl);
                }
                const body = editor.Canvas.getBody();
                body.addEventListener('contextmenu', function(e) {
                    e.preventDefault();
                    const offset = editor.Canvas.getOffset();
                    const cursorX = e.clientX + offset.left;
                    const cursorY = e.clientY + offset.top;
                    contextMenu.style.left = cursorX + 'px';
                    contextMenu.style.top = cursorY + 'px';
                    contextMenu.style.display = 'flex';
                });
                body.addEventListener('click', function() {
                    contextMenu.style.display = 'none';
                });
            });

            // Text Tracking
            let selectedTextEl = null;
            editor.on('component:selected', function(model) {
                const tag = model.get('tagName');
                if (model.get('type') === 'text' || ['p', 'h1', 'h2', 'h3', 'span', 'a'].includes(tag)) {
                    selectedTextEl = model;
                } else {
                    selectedTextEl = null;
                    contextMenu.style.display = 'none';
                }
            });

            document.querySelectorAll('.sabis-context-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (!selectedTextEl) return;
                    const originalText = selectedTextEl.getEl().innerText;
                    const action = btn.getAttribute('data-action');
                    contextMenu.style.display = 'none';
                    updateStatus('✍️ Groq Llama 3 Copywriting in action...', 'loading');

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_copywrite_via_gemini',
                            text: originalText,
                            command: action,
                            nonce: '<?php echo wp_create_nonce("sabis_copywrite_nonce"); ?>'
                        },
                        success: function(res) {
                            if (res.success) {
                                selectedTextEl.setComponents(res.data.text);
                                updateStatus('Standby - AI Engine Ready', 'ready');
                            } else {
                                updateStatus('Failed copywriting', 'ready');
                            }
                        }
                    });
                });
            });

            const blockManager = editor.Blocks;

            // ✨ Ask AI for Component block
            blockManager.add('sabis-ask-ai-component', {
                label: '✨ Ask AI for Component',
                category: 'AI Interactive Blocks',
                content: `
                    <div class="sabis-ai-component-placeholder p-8 text-center bg-slate-900 border-2 border-dashed border-slate-700 rounded-xl cursor-pointer hover:border-blue-500 transition-colors" data-prompted="false">
                        <div class="text-3xl mb-2">✨</div>
                        <div class="text-sm font-bold text-slate-100">Click to ask AI for Component</div>
                        <div class="text-xs text-slate-400 mt-1">Llama 3 compilations dynamically added here</div>
                    </div>
                `
            });

            editor.on('component:add', (model) => {
                const el = model.getEl();
                if (el && el.classList && el.classList.contains('sabis-ai-component-placeholder')) {
                    el.addEventListener('click', function() {
                        const prompt = window.parent.prompt("Describe custom interactive component (e.g. 'pricing slider', 'premium FAQ custom dropdown'):");
                        if (!prompt) return;

                        updateStatus('Generating dynamic component with Groq Llama 3...', 'loading');
                        jQuery.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            data: {
                                action: 'sabis_generate_component',
                                prompt: prompt,
                                nonce: '<?php echo wp_create_nonce("sabis_comp_nonce"); ?>'
                            },
                            success: function(res) {
                                if (res.success && res.data.html) {
                                    model.replaceWith(res.data.html);
                                    updateStatus('Standby - AI Engine Ready', 'ready');
                                } else {
                                    alert('Component generation failed');
                                    updateStatus('Failed component generation', 'ready');
                                }
                            }
                        });
                    });
                }
            });

            blockManager.add('sabis-testform', {
                label: '⚡ Order Form (WhatsApp)',
                category: 'Sabis Essentials',
                content: `
                    <div class="p-6 bg-slate-900 border border-slate-800 rounded-xl max-w-md mx-auto my-8 text-white shadow-xl">
                        <h3 class="text-xl font-bold mb-2">Order Direct via WhatsApp</h3>
                        <form onsubmit="event.preventDefault(); alert('🛍️ Order Submitted Successfully!');" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Full Name</label>
                                <input type="text" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white" />
                            </div>
                            <div>
                                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-400 mb-1">Phone Number</label>
                                <input type="tel" required class="w-full bg-slate-800 border border-slate-700 rounded p-2 text-sm text-white" />
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded text-sm transition">
                                Send WhatsApp Order
                            </button>
                        </form>
                    </div>
                `
            });

            // Voice Dictation support
            const micBtn = document.getElementById('sabis-mic-btn');
            const promptBox = document.getElementById('sabis-prompt');
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechReq = window.SpeechRecognition || window.webkitSpeechRecognition;
                const recognizer = new SpeechReq();
                recognizer.continuous = false;
                recognizer.lang = 'en-US';

                micBtn.addEventListener('click', function() {
                    if (micBtn.classList.contains('sabis-mic-active')) {
                        recognizer.stop();
                    } else {
                        micBtn.classList.add('sabis-mic-active');
                        recognizer.start();
                    }
                });
                recognizer.onend = () => micBtn.classList.remove('sabis-mic-active');
                recognizer.onresult = (e) => promptBox.value += ' ' + e.results[0][0].transcript;
                recognizer.onerror = () => micBtn.classList.remove('sabis-mic-active');
            } else {
                if (micBtn) micBtn.style.display = 'none';
            }

            // Reference Image trigger & handler
            const refImgBtn = document.getElementById('sabis-ref-image-btn');
            const refImgInput = document.getElementById('sabis-reference-image');
            window.attachedReferenceImage = null;

            if (refImgBtn && refImgInput) {
                refImgBtn.addEventListener('click', () => refImgInput.click());
                refImgInput.addEventListener('change', function() {
                    const file = this.files[0];
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        window.attachedReferenceImage = e.target.result;
                        refImgBtn.textContent = "📁 Attached";
                        refImgBtn.style.background = "#06b6d4";
                        refImgBtn.style.color = "#ffffff";
                        updateStatus('Reference image selected and successfully loaded into multimodal pipeline!', 'ready');
                    };
                    reader.readAsDataURL(file);
                });
            }

            // Tabs / Multiple Page State Variables
            window.sabisPagesData = {};
            window.sabisCurrentActiveTab = null;

            function initSabisTabs(pagesArray) {
                window.sabisPagesData = {};
                const tabsNav = document.getElementById('sabis-tabs-nav');
                tabsNav.innerHTML = '';
                const container = document.getElementById('sabis-editor-tabs-container');
                
                if (pagesArray && pagesArray.length > 0) {
                    container.style.display = 'flex';
                    pagesArray.forEach((p, idx) => {
                        window.sabisPagesData[p.title] = {
                            html: p.html,
                            title: p.title
                        };
                        
                        const tabBtn = document.createElement('button');
                        tabBtn.type = 'button';
                        tabBtn.className = 'sabis-viewport-btn';
                        tabBtn.style.padding = '4px 10px';
                        tabBtn.style.fontSize = '12px';
                        tabBtn.textContent = p.title;
                        tabBtn.setAttribute('data-tab-title', p.title);
                        
                        if (idx === 0) {
                            tabBtn.classList.add('active');
                            window.sabisCurrentActiveTab = p.title;
                        }
                        
                        tabBtn.addEventListener('click', function() {
                            switchSabisTab(p.title);
                        });
                        
                        tabsNav.appendChild(tabBtn);
                    });
                    
                    editor.setComponents(pagesArray[0].html);
                } else {
                    container.style.display = 'none';
                }
            }

            function switchSabisTab(targetTitle) {
                if (window.sabisCurrentActiveTab === targetTitle) return;
                
                // Save current active tab state
                if (window.sabisCurrentActiveTab && window.sabisPagesData[window.sabisCurrentActiveTab]) {
                    window.sabisPagesData[window.sabisCurrentActiveTab].html = editor.getHtml();
                }
                
                // Update active visualization
                document.querySelectorAll('#sabis-tabs-nav button').forEach(btn => {
                    if (btn.getAttribute('data-tab-title') === targetTitle) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
                
                window.sabisCurrentActiveTab = targetTitle;
                editor.setComponents(window.sabisPagesData[targetTitle].html);
                updateStatus('Editing: ' + targetTitle, 'ready');
            }

            const generateBtn = document.getElementById('btn-generate-ai');
            const sendBtn = document.getElementById('sabis-send-btn');
            const publishBtn = document.getElementById('btn-publish-wp');
            const resetBtn = document.getElementById('btn-reset-canvas');
            const feedbackContainer = document.getElementById('sabis-publish-feedback');

            const defaultTemplate = `
                <section class="max-w-4xl mx-auto py-16 px-6 text-center font-sans bg-zinc-50 border border-zinc-200 rounded-3xl my-8">
                  <span class="text-xs font-bold uppercase text-blue-600 bg-blue-100/50 px-3 py-1 rounded-full mb-4 inline-block tracking-wider">Empty Slate Canvas</span>
                  <h2 class="text-3xl font-black text-zinc-900 mb-3 tracking-tight">Your Visual Studio is Prepped</h2>
                  <p class="text-zinc-500 text-sm max-w-md mx-auto mb-6">
                    Enter a prompt above and speak directly using dynamic voice speech recognition logic, or drag premium elements from the blocks inspector shelf.
                  </p>
                  <div class="flex justify-center gap-3">
                    <span class="text-[11px] font-mono text-zinc-400">Powered by GrapesJS + Tailwind CSS v4</span>
                  </div>
                </section>
            `;

            // Reset Canvas to default
            resetBtn.addEventListener('click', function() {
                if (confirm('Are you sure you want to completely reset the visual canvas back to its original prepped slate layout?')) {
                    initSabisTabs([]);
                    editor.setComponents(defaultTemplate);
                    updateStatus('Canvas Reset Completed LTR Lived!', 'ready');
                }
            });

            // Load original template initially
            editor.setComponents(defaultTemplate);

            function updateStatus(text, type = 'ready') {
                const statusBadge = document.getElementById('generation-status');
                const textSpan = statusBadge.querySelector('.status-text');
                textSpan.textContent = text;
                
                statusBadge.classList.remove('sabis-status-ready', 'sabis-status-loading');
                if (type === 'loading') {
                    statusBadge.classList.add('sabis-status-loading');
                } else {
                    statusBadge.classList.add('sabis-status-ready');
                }
            }

            function handleAILayoutGeneration() {
                const userPrompt = promptBox.value.trim();
                if (!userPrompt) {
                     alert('Please fill in or dictate a prompt.');
                     return;
                }
                
                // Gather page selection counts and templates
                const pageCount = document.getElementById('sabis-page-count').value;
                const selectedTemplates = [];
                document.querySelectorAll('.sabis-page-template-cb:checked').forEach(cb => {
                    selectedTemplates.push(cb.value);
                });

                updateStatus('Dual-AI Router routing using Gemini 2.5 Pro Multimodal Vision...', 'loading');
                generateBtn.disabled = true;
                if(sendBtn) sendBtn.disabled = true;

                const payload = {
                    action: 'sabis_generate_page_via_gemini',
                    prompt: userPrompt,
                    theme: document.getElementById('sabis-theme-preset').value,
                    custom_theme_desc: document.getElementById('sabis-theme-custom-desc').value,
                    pages_count: pageCount,
                    templates: JSON.stringify(selectedTemplates),
                    nonce: '<?php echo wp_create_nonce("sabis_gemini_nonce"); ?>'
                };

                if (window.attachedReferenceImage) {
                    payload.reference_image = window.attachedReferenceImage;
                }

                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: payload,
                    success: function(response) {
                        generateBtn.disabled = false;
                        if(sendBtn) sendBtn.disabled = false;
                        if (response.success && response.data) {
                            if (response.data.pages && Array.isArray(response.data.pages)) {
                                initSabisTabs(response.data.pages);
                                updateStatus('AI Multi-Pages Synced Successful!', 'ready');
                            } else if (response.data.html) {
                                initSabisTabs([]);
                                editor.setComponents(response.data.html);
                                updateStatus('AI Hybrid Design Synced!', 'ready');
                            }
                            
                            // Auto reset theme layout accents
                            const preset = document.getElementById('sabis-theme-preset').value;
                            const wrapper = editor.getWrapper();
                            if (wrapper) {
                                wrapper.removeClass('bg-slate-950 text-cyan-400 bg-zinc-50 text-zinc-900 bg-slate-900 text-yellow-101 bg-slate-900 text-yellow-100');
                                if (preset === 'Cyberpunk Dark') {
                                    wrapper.addClass('bg-slate-950 text-cyan-400');
                                } else if (preset === 'Minimal Light') {
                                    wrapper.addClass('bg-zinc-50 text-zinc-900');
                                } else if (preset === 'Royal Gold') {
                                    wrapper.addClass('bg-slate-900 text-yellow-100');
                                }
                            }
                            publishBtn.disabled = false;
                        } else {
                            updateStatus('Failed routing.', 'ready');
                            alert(response.data.error || 'Server processing error.');
                        }
                    },
                    error: function() {
                        generateBtn.disabled = false;
                        if(sendBtn) sendBtn.disabled = false;
                        updateStatus('Failed connection.', 'ready');
                    }
                });
            }

            generateBtn.addEventListener('click', handleAILayoutGeneration);
            if (sendBtn) {
                sendBtn.addEventListener('click', handleAILayoutGeneration);
            }

            // Theme Accents Controller
            document.getElementById('sabis-theme-preset').addEventListener('change', function() {
                const preset = this.value;
                const customInput = document.getElementById('sabis-theme-custom-desc');
                if (preset === 'Custom Accent') {
                    customInput.style.display = 'inline-block';
                } else {
                    customInput.style.display = 'none';
                    customInput.value = '';
                }

                const wrapper = editor.getWrapper();
                if (!wrapper) return;
                wrapper.removeClass('bg-slate-950 text-cyan-400 bg-zinc-50 text-zinc-900 bg-slate-900 text-yellow-101 text-yellow-100');
                wrapper.removeClass('bg-[#0e0f11] text-orange-400 bg-[#0a100d] text-emerald-400 bg-[#050b14] text-sky-400 bg-[#121214] text-purple-400 bg-[#0d0d0e] text-zinc-100');
                
                if (preset === 'Cyberpunk Dark') {
                    wrapper.addClass('bg-slate-950 text-cyan-400');
                } else if (preset === 'Minimal Light') {
                    wrapper.addClass('bg-zinc-50 text-zinc-900');
                } else if (preset === 'Royal Gold') {
                    wrapper.addClass('bg-slate-900 text-yellow-100');
                } else if (preset === 'Sunset Orange & Charcoal') {
                    wrapper.addClass('bg-[#0e0f11] text-orange-400');
                } else if (preset === 'Forest Emerald & Sage') {
                    wrapper.addClass('bg-[#0a100d] text-emerald-400');
                } else if (preset === 'Deep Ocean Blues') {
                    wrapper.addClass('bg-[#050b14] text-sky-400');
                } else if (preset === 'Dracula Vampire') {
                    wrapper.addClass('bg-[#121214] text-purple-400');
                } else if (preset === 'Custom Accent') {
                    wrapper.addClass('bg-[#0d0d0e] text-zinc-100');
                }
            });

            // Device Viewport Preset Controller
            const viewDesktop = document.getElementById('btn-view-desktop');
            const viewTablet = document.getElementById('btn-view-tablet');
            const viewMobile = document.getElementById('btn-view-mobile');

            function clearActiveViewportClass() {
                viewDesktop.classList.remove('active');
                viewTablet.classList.remove('active');
                viewMobile.classList.remove('active');
            }

            viewDesktop.addEventListener('click', function() {
                clearActiveViewportClass();
                viewDesktop.classList.add('active');
                editor.setDevice('desktop');
                const frame = document.querySelector('.gjs-frame');
                if (frame) {
                    frame.style.width = '100%';
                    frame.style.maxWidth = '100%';
                }
            });

            viewTablet.addEventListener('click', function() {
                clearActiveViewportClass();
                viewTablet.classList.add('active');
                editor.setDevice('tablet');
                const frame = document.querySelector('.gjs-frame');
                if (frame) {
                    frame.style.width = '768px';
                    frame.style.maxWidth = '100%';
                    frame.style.margin = '0 auto';
                }
            });

            viewMobile.addEventListener('click', function() {
                clearActiveViewportClass();
                viewMobile.classList.add('active');
                editor.setDevice('mobile');
                const frame = document.querySelector('.gjs-frame');
                if (frame) {
                    frame.style.width = '375px';
                    frame.style.maxWidth = '100%';
                    frame.style.margin = '0 auto';
                }
            });

            // Screenshot to Code logic click handler
            const uploadInspirationBtn = document.getElementById('btn-upload-inspiration');
            const filePickerInput = document.getElementById('screenshot-file-picker');
            uploadInspirationBtn.addEventListener('click', () => filePickerInput.click());
            filePickerInput.addEventListener('change', function() {
                const file = this.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = function(e) {
                    const base64Data = e.target.result;
                    updateStatus('Transmitting inspiration screenshot to Gemini 2.5 Pro Vision...', 'loading');
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_screenshot_to_code',
                            image: base64Data,
                            theme: document.getElementById('sabis-theme-preset').value,
                            nonce: '<?php echo wp_create_nonce("sabis_vision_nonce"); ?>'
                        },
                        success: function(res) {
                            if (res.success && res.data.html) {
                                editor.setComponents(res.data.html);
                                updateStatus('Inspiration Layout Created!', 'ready');
                                publishBtn.disabled = false;
                            } else {
                                alert(res.data.error || 'Failed drawing source snapshot layout');
                                updateStatus('Screen extraction failed', 'ready');
                            }
                        }
                    });
                };
                reader.readAsDataURL(file);
            });

            // Translate Urdu RTL Trigger (Groq Llama 3)
            document.getElementById('btn-translate-lang').addEventListener('click', function() {
                updateStatus('Localizing visual canvas to Urdu RTL via Groq...', 'loading');
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'sabis_translate_via_gemini',
                        text: editor.getHtml(),
                        target: 'urdu',
                        nonce: '<?php echo wp_create_nonce("sabis_translate_nonce"); ?>'
                    },
                    success: function(res) {
                        if (res.success && res.data.text) {
                            editor.setComponents(res.data.text);
                            const activeWrap = editor.getWrapper();
                            if (activeWrap) {
                                activeWrap.setAttribute('dir', 'rtl');
                                activeWrap.addClass('text-right');
                            }
                            updateStatus('Urdu RTL loaded', 'ready');
                        } else {
                            updateStatus('Localization failed', 'ready');
                        }
                    }
                });
            });

            document.getElementById('btn-translate-eng').addEventListener('click', function() {
                const activeWrap = editor.getWrapper();
                if (activeWrap) {
                    activeWrap.setAttribute('dir', 'ltr');
                    activeWrap.removeClass('text-right');
                }
            });

            publishBtn.addEventListener('click', function() {
                // Save current page state
                if (window.sabisCurrentActiveTab && window.sabisPagesData[window.sabisCurrentActiveTab]) {
                    window.sabisPagesData[window.sabisCurrentActiveTab].html = editor.getHtml();
                }

                const multCount = Object.keys(window.sabisPagesData).length;
                if (multCount > 1) {
                    if (!confirm('You are about to mass-publish ' + multCount + ' independent visual pages directly to WordPress. Proceed?')) return;
                    publishBtn.disabled = true;
                    updateStatus('Mass-publishing pages and generating custom SEO meta structures...', 'loading');

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_publish_visual_page',
                            pages: JSON.stringify(Object.values(window.sabisPagesData)),
                            nonce: '<?php echo wp_create_nonce("sabis_publish_nonce"); ?>'
                        },
                        success: function(res) {
                            publishBtn.disabled = false;
                            if (res.success) {
                                feedbackContainer.style.display = 'block';
                                let linksHtml = '<div style="display:flex; flex-direction:column; gap:6px;">';
                                res.data.pages.forEach(p => {
                                    linksHtml += '<div><a href="' + p.permalink + '" target="_blank" style="font-weight:bold; color:#10b981;">View ' + p.title + ': ' + p.permalink + '</a></div>';
                                });
                                linksHtml += '</div>';
                                document.getElementById('feedback-post-link').innerHTML = linksHtml;
                                document.getElementById('feedback-audit-log').textContent = res.data.audit;
                                updateStatus('Mass-publishing completed!', 'ready');
                            } else {
                                alert(res.data.error || 'Failed mass-publishing WordPress pages');
                            }
                        }
                    });
                } else {
                    const title = prompt('Provide SEO Page Title:', 'My Custom Layout');
                    if (!title) return;
                    publishBtn.disabled = true;
                    updateStatus('Saving page & generating SEO parameters...', 'loading');

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_publish_visual_page',
                            html: editor.getHtml(),
                            title: title,
                            nonce: '<?php echo wp_create_nonce("sabis_publish_nonce"); ?>'
                        },
                        success: function(res) {
                            publishBtn.disabled = false;
                            if (res.success) {
                                feedbackContainer.style.display = 'block';
                                document.getElementById('feedback-post-link').innerHTML = '<a href="' + res.data.permalink + '" target="_blank" style="font-weight:bold; color:#10b981;">View Page: ' + res.data.permalink + '</a>';
                                document.getElementById('feedback-audit-log').textContent = res.data.audit;
                                updateStatus('Saved successfully', 'ready');
                            } else {
                                alert(res.data.error || 'Failed saving WordPress page');
                            }
                        }
                    });
                }
            });

            document.getElementById('btn-export-zip').addEventListener('click', function() {
                const html = editor.getHtml();
                const css = editor.getCss();
                const completeDoc = `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sabis Static Export</title>
    <script src="https://cdn.tailwindcss.com"></` + `script>
    <style>${css}</style>
</head>
<body class="bg-zinc-50">
    \${html}
</body>
</html>`;
                const blob = new Blob([completeDoc], {type: 'text/html'});
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'sabis-ai-studio-export.html';
                link.click();
            });

            editor.Commands.add('regenerate-section-command', {
                run(editor) {
                    const selected = editor.getSelected();
                    if (!selected) {
                        alert('Select a component redesign block first.');
                        return;
                    }
                    let htmlToRegen = '';
                    if (selected.toHTML) htmlToRegen = selected.toHTML();
                    else if (selected.getEl) htmlToRegen = selected.getEl().outerHTML;

                    const instruction = prompt('AI Section Revision Command (e.g. "make pricing bold cyan buttons"):');
                    if (!instruction) return;

                    updateStatus('Direct Section revision with Gemini 2.5 Pro...', 'loading');
                    
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_regenerate_section_via_gemini',
                            section_html: htmlToRegen,
                            instruction: instruction,
                            theme: document.getElementById('sabis-theme-preset').value,
                            nonce: '<?php echo wp_create_nonce("sabis_regenerate_section_nonce"); ?>'
                        },
                        success: function(res) {
                            updateStatus('AI Engine Redesigned Section!', 'ready');
                            if (res.success && res.data.html) {
                                const parent = selected.parent();
                                if (parent) {
                                    const index = selected.index();
                                    parent.append(res.data.html, { at: index });
                                    selected.remove();
                                } else {
                                    selected.setComponents(res.data.html);
                                }
                            } else {
                                alert(res.data.error || 'Failed to redesign section.');
                            }
                        }
                    });
                }
            });

            editor.on('component:selected', function(model) {
                const toolbar = model.get('toolbar');
                const buttonExists = toolbar ? toolbar.some(item => item.id === 'regenerate-section') : false;
                if (!buttonExists && toolbar) {
                    toolbar.push({
                        id: 'regenerate-section',
                        command: 'regenerate-section-command',
                        label: '✨ AI Redesign',
                        attributes: { 
                            id: 'sabis-btn-ai-redesign',
                            title: 'AI Redesigns via Gemini 2.5 Pro' 
                        }
                    });
                    model.set('toolbar', [...toolbar]);
                    model.trigger('change:toolbar');
                }
            });

            // Capture-phase event listeners to guarantee that clicking/touching the '✨ AI Redesign' button runs properly without misfiring on mobile
            const handleAiRedesignTrigger = function(e) {
                const btn = e.target.closest('#sabis-btn-ai-redesign');
                if (btn) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('Intercepting click for AI Redesign on mobile...');
                    editor.runCommand('regenerate-section-command');
                }
            };
            document.addEventListener('click', handleAiRedesignTrigger, { capture: true });
            document.addEventListener('touchstart', handleAiRedesignTrigger, { capture: true });

            // Existing Page Loader AJAX event handler
            const loadPageBtn = document.getElementById('sabis-load-existing-page-btn');
            if (loadPageBtn) {
                loadPageBtn.addEventListener('click', function() {
                    const selector = document.getElementById('sabis-existing-page-selector');
                    const pageId = selector.value;
                    if (!pageId) {
                        alert('Please select a published WordPress page from the dropdown first.');
                        return;
                    }
                    
                    updateStatus('Retrieving page layout structure from database...', 'loading');
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: {
                            action: 'sabis_get_wordpress_page',
                            page_id: pageId,
                            nonce: '<?php echo wp_create_nonce("sabis_get_page_nonce"); ?>'
                        },
                        success: function(res) {
                            if (res.success && typeof res.data.html !== 'undefined') {
                                editor.setComponents(res.data.html);
                                updateStatus('Loaded page HTML into GrapesJS canvas successfully!', 'ready');
                            } else {
                                alert(res.data.error || 'Failed to retrieve page content from WordPress.');
                                updateStatus('Error loading page.', 'ready');
                            }
                        },
                        error: function() {
                            alert('HTTP request failed while querying WordPress page content.');
                            updateStatus('Network failure', 'ready');
                        }
                    });
                });
            }

            // Auto loading page if query parameter is present: e.g. ?page=sabis-ai-studio&sabis_edit_page=X
            <?php if (isset($_GET['sabis_edit_page'])): ?>
            const urlPageId = "<?php echo esc_js($_GET['sabis_edit_page']); ?>";
            const selector = document.getElementById('sabis-existing-page-selector');
            if (selector && urlPageId) {
                selector.value = urlPageId;
                setTimeout(() => {
                    const loadBtn = document.getElementById('sabis-load-existing-page-btn');
                    if (loadBtn) {
                        loadBtn.click();
                    }
                }, 800);
            }
            <?php endif; ?>
        });
        </script>
        <?php
    }

    private function clean_markdown_html($text) {
        $text = trim($text);
        $text = preg_replace('/^```(?:html)?\s*/i', '', $text);
        $text = preg_replace('/\s*```$/', '', $text);
        return trim($text);
    }

    private function apply_isolate_wrapper($html) {
        if (empty($html) || !is_string($html)) {
            return $html;
        }
        $trimmed = trim($html);
        if (strpos($trimmed, 'data-sabis-isolated=') !== false) {
            return $html;
        }
        $id_str = 'sabis-iso-' . wp_generate_password(7, false, false);
        return '<div class="sabis-layout-isolated-wrapper relative w-full" data-sabis-isolated="true" id="' . $id_str . '">' . $html . '</div>';
    }

    private function call_gemini($system_prompt, $user_prompt) {
        $api_key = get_option(self::OPTION_API_KEY, '');
        if (empty($api_key)) {
            return new WP_Error('missing_gemini_key', __('Gemini key is missing.', 'sabis-ai-studio'));
        }
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => $user_prompt)
                    )
                )
            ),
            'systemInstruction' => array(
                'parts' => array(
                    array('text' => $system_prompt)
                )
            )
        );
        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $api_key;
        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 30
        ));
        if (is_wp_error($response)) {
            return $response;
        }
        $res_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($res_body['candidates'][0]['content']['parts'][0]['text'])) {
            return $res_body['candidates'][0]['content']['parts'][0]['text'];
        }
        return new WP_Error('gemini_parse_error', __('Failed parsing Gemini response data.', 'sabis-ai-studio'));
    }

    private function call_groq($system_prompt, $user_prompt) {
        $groq_api_key = get_option(self::OPTION_GROQ_KEY, '');
        if (empty($groq_api_key)) {
            return new WP_Error('missing_key', __('Groq API key is missing.', 'sabis-ai-studio'));
        }

        $payload = array(
            'model' => 'llama-3.1-8b-instant',
            'messages' => array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt)
            ),
            'temperature' => 0.5,
        );

        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $groq_api_key
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 20
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['choices'][0]['message']['content'])) {
            return trim($body['choices'][0]['message']['content']);
        }

        return new WP_Error('empty_response', __('Groq returned an invalid response.', 'sabis-ai-studio'));
    }

    public function generate_page_via_gemini_ajax() {
        check_ajax_referer('sabis_gemini_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $theme_preset = sanitize_text_field($_POST['theme']);
        $custom_theme_desc = isset($_POST['custom_theme_desc']) ? sanitize_text_field($_POST['custom_theme_desc']) : '';
        $api_key = get_option(self::OPTION_API_KEY, '');
        $stripe_key = get_option(self::OPTION_STRIPE_KEY, '');

        $pages_count = isset($_POST['pages_count']) ? intval($_POST['pages_count']) : 1;
        $templates = isset($_POST['templates']) ? json_decode(stripslashes($_POST['templates']), true) : array();
        if (!is_array($templates)) {
            $templates = array();
        }

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Urgent: Please add your Google Gemini API Key first!', 'sabis-ai-studio')));
        }

        // Dual AI Hybrid Optimization Task
        $enhanced = $prompt;
        $groq_enhancement = $this->call_groq(
            "You are an Elite Prompt Generator. Expand the user query into a detailed, descriptive, custom component layout structure instruction emphasizing Tailwind grid elements.",
            $prompt
        );
        if (!is_wp_error($groq_enhancement)) {
            $enhanced = $groq_enhancement;
        }

        $stripe_prompt_addon = !empty($stripe_key) 
            ? "CRITICAL NO-CODE PAYMENT ACTION: We have an active Stripe Publishable Key: '{$stripe_key}'. If checkout forms, pricing visual blocks, shopping carts, or digital payment features are requested or suited, you MUST automatically output a highly secure, functional, fully customized visual script layout utilizing official SDK libraries 'https://js.stripe.com/v3/' to render smart credit card payment fields dynamically. Avoid WooCommerce by handling transaction submissions directly via raw GrapesJS / Stripe JS routines using that key."
            : "PAYMENT INTEGRATION WARNING: If payment fields are needed, render direct payment UI placeholders and mention Stripe publishable key setup within the settings.";

        $system_instruction = "You are an elite, modern Web Designer and No-Code Tailwind CSS Master. Output ONLY clean, valid, standalone semantic HTML tags with inline Tailwind classes. Ensure the design follows requested accents theme correctly. Do NOT return markdown blocks or any conversational explanations. {$stripe_prompt_addon} 
        The theme preset should match [ {$theme_preset} ].
        Theme Profiles details list:
        - 'Minimal Light': sleek off-whites (bg-zinc-50), subtle gray borders, elegant spacing.
        - 'Cyberpunk Dark': neon elements, dark background (bg-slate-950), interactive glowing borders.
        - 'Royal Gold': emerald/gold accents, opulent visual elegance (bg-slate-900).
        - 'Sunset Orange & Charcoal': warm sunset gradient, vivid orange text accents, dark charcoal panels.
        - 'Forest Emerald & Sage': earthy calm feel, natural deep greens and soft sage details.
        - 'Deep Ocean Blues': tech navy backgrounds, beautiful marine teal elements.
        - 'Dracula Vampire': dark gothic cyber themes, purple borders, magenta accents.
        - 'Custom Accent': custom vibe described as: [ " . $custom_theme_desc . " ]. Formulate custom color gradients, backgrounds and cards directly matching this description.";

        // Handle possible multimodal image reference attached to prompt
        $parts = array();
        if (isset($_POST['reference_image']) && !empty($_POST['reference_image'])) {
            $image_base64 = $_POST['reference_image'];
            $image_base64 = str_replace('data:image/png;base64,', '', $image_base64);
            $image_base64 = str_replace('data:image/jpeg;base64,', '', $image_base64);
            $image_base64 = str_replace('data:image/jpg;base64,', '', $image_base64);
            $image_base64 = str_replace(' ', '+', $image_base64);

            $parts[] = array(
                'inlineData' => array(
                    'mimeType' => 'image/png',
                    'data' => $image_base64
                )
            );
        }

        // Add visual layout instruction suffix if reference image is active
        $layout_target_prompt = "Design complete visual block styled for preset: [ " . $theme_preset . " ]. ";
        if ($theme_preset === 'Custom Accent' && !empty($custom_theme_desc)) {
            $layout_target_prompt .= "Follow custom theme details: \"" . $custom_theme_desc . "\". ";
        }
        if (isset($_POST['reference_image']) && !empty($_POST['reference_image'])) {
            $layout_target_prompt .= "Analyze the attached screenshot/image carefully. Replicate its visual layout hierarchy, font weights, custom tailwind alignments, spacings, design patterns, and general UI vibes elegantly. ";
        }

        if ($pages_count > 1 || !empty($templates)) {
            $layout_target_prompt .= "We are compiling a multiline/multi-page site structure. Create " . $pages_count . " separate complete page structures. The needed pages are defined as: " . implode(', ', $templates) . ". 
            Since multiple pages are requested, you MUST return a valid JSON array of objects. Do NOT output raw standard HTML, write the final resulting objects list within a JSON structure.
            Example of expected JSON return:
            [
              {
                \"title\": \"Page Title 1 (e.g. Home)\",
                \"html\": \"<section class='...'>Home Page content styled beautifully</section>\"
              },
              {
                \"title\": \"Page Title 2\",
                \"html\": \"<section class='...'>Detailed page grid...</section>\"
              }
            ]
            Each HTML value must be independent, valid, stylized and fully populated with headers, sliders, forms or call-to-actions. Return raw JSON strictly without triple-backtick json frames.";
        } else {
            $layout_target_prompt .= "Instructions: " . $enhanced;
        }

        $parts[] = array('text' => $layout_target_prompt);

        $body_array = array(
            'contents' => array(
                array(
                    'parts' => $parts
                )
            ),
            'systemInstruction' => array(
                'parts' => array(
                    array('text' => $system_instruction)
                )
            )
        );

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=" . $api_key;
        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body_array),
            'timeout' => 45
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('error' => $response->get_error_message()));
        }

        $res_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($res_body['candidates'][0]['content']['parts'][0]['text'])) {
            $raw_text = $this->clean_markdown_html($res_body['candidates'][0]['content']['parts'][0]['text']);
            
            // Attempt to parse is JSON array
            $decoded_pages = json_decode($raw_text, true);
            if ($pages_count > 1 && is_array($decoded_pages)) {
                $mapped = array_map(function($p) {
                    if (isset($p['html'])) {
                        $p['html'] = $this->apply_isolate_wrapper($p['html']);
                    }
                    return $p;
                }, $decoded_pages);
                wp_send_json_success(array('pages' => $mapped));
            } else {
                if (is_array($decoded_pages) && isset($decoded_pages[0]['html'])) {
                    $mapped = array_map(function($p) {
                        if (isset($p['html'])) {
                            $p['html'] = $this->apply_isolate_wrapper($p['html']);
                        }
                        return $p;
                    }, $decoded_pages);
                    wp_send_json_success(array('pages' => $mapped));
                } else {
                    wp_send_json_success(array('html' => $this->apply_isolate_wrapper($raw_text)));
                }
            }
        } else {
            wp_send_json_error(array('error' => __('Verification: Call returned no components from Gemini API.', 'sabis-ai-studio')));
        }
    }

    public function copywrite_via_gemini_ajax() {
        check_ajax_referer('sabis_copywrite_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $text = sanitize_textarea_field($_POST['text']);
        $command = sanitize_text_field($_POST['command']);

        $system_prompt = "You are an expert SaaS copywriter. Transform the given text with action: " . $command . ". Return back ONLY rewritten string with zero explanation or quotes.";
        $user_prompt = "Transform text: " . $text;

        $res = $this->call_groq($system_prompt, $user_prompt);

        if (is_wp_error($res)) {
            // Failsafe auto-fallback routing to Google Gemini on Groq error/absence
            $res = $this->call_gemini($system_prompt, $user_prompt);
        }

        if (is_wp_error($res)) {
            wp_send_json_error(array('error' => $res->get_error_message()));
        }

        $output = preg_replace('/^["\']|["\']$/', '', $res);
        $output = $this->clean_markdown_html($output);
        wp_send_json_success(array('text' => $output));
    }

    public function translate_via_gemini_ajax() {
        check_ajax_referer('sabis_translate_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $text = $_POST['text'];

        $system_prompt = "You are a localization module. Translate leaf text nodes from English to exquisite marketing Urdu. You MUST isolate leaf nodes, leaving 100% of Tailwind classes and HTML tag bracket properties strictly unchanged.";
        $user_prompt = $text;

        $res = $this->call_groq($system_prompt, $user_prompt);

        if (is_wp_error($res)) {
            // Failsafe auto-fallback routing to Google Gemini on Groq error/absence
            $res = $this->call_gemini($system_prompt, $user_prompt);
        }

        if (is_wp_error($res)) {
            wp_send_json_error(array('error' => $res->get_error_message()));
        }

        $output = $this->clean_markdown_html($res);
        wp_send_json_success(array('text' => $output));
    }

    public function screenshot_to_code_ajax() {
        check_ajax_referer('sabis_vision_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $image = $_POST['image'];
        $theme = sanitize_text_field($_POST['theme']);
        $api_key = get_option(self::OPTION_API_KEY, '');

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Missing Gemini API Key', 'sabis-ai-studio')));
        }

        $image = str_replace('data:image/png;base64,', '', $image);
        $image = str_replace('data:image/jpeg;base64,', '', $image);
        $image = str_replace('data:image/jpg;base64,', '', $image);
        $image = str_replace(' ', '+', $image);

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'inlineData' => array(
                                'mimeType' => 'image/png',
                                'data' => $image
                            )
                        ),
                        array(
                            'text' => "Analyze layout screenshot. Output ONLY clean valid responsive semantic HTML and inline Tailwind CSS matching spatial grids, themes, typography, and accents. No markdown blocks, explanations, or quotes."
                        )
                    )
                )
            )
        );

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=" . $api_key;
        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 40
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('error' => $response->get_error_message()));
        }

        $res_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($res_body['candidates'][0]['content']['parts'][0]['text'])) {
            $html = $this->clean_markdown_html($res_body['candidates'][0]['content']['parts'][0]['text']);
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('error' => __('Failed extracting code from image.', 'sabis-ai-studio')));
        }
    }

    public function generate_component_ajax() {
        check_ajax_referer('sabis_comp_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $stripe_key = get_option(self::OPTION_STRIPE_KEY, '');

        $stripe_prompt_addon = !empty($stripe_key) 
            ? "CRITICAL NO-CODE PAYMENT ACTION: We have an active Stripe Publishable Key: '{$stripe_key}'. If the custom widget/component requested implies credit card inputs, pricing sliders, checkouts, or purchasing, you MUST automatically inject direct Stripe Smart Payment Elements. Load https://js.stripe.com/v3/ inside a script block, set up Stripe with key '{$stripe_key}', render fields, and handle click/submit callbacks elegantly to complete standalone transactions without WooCommerce."
            : "PAYMENT INTEGRATION: If payment is requested, construct a clean PayPal SDK or mock Stripe Elements button showing a detailed description on how to plug in Stripe keys.";

        $res = $this->call_groq(
            "You are a premium UI widget generator. Compile vanilla semantic HTML paired with lightweight CSS/vanilla javascript inside script tags or Alpine.js. Do not translate or discuss, return ONLY complete component code with zero markdown wrapping blocks. {$stripe_prompt_addon}",
            "Generate layout: " . $prompt
        );

        if (is_wp_error($res)) {
            wp_send_json_error(array('error' => $res->get_error_message()));
        }

        $html = $this->clean_markdown_html($res);
        wp_send_json_success(array('html' => $html));
    }

    public function generate_seo_meta($html) {
        $extracted_text = wp_strip_all_tags($html);
        $res = $this->call_groq(
            "Analyze page content and respond with direct JSON containing fields: 'meta_title', 'meta_description', 'json_ld' representing targeted business schema. Output raw valid JSON strictly without markdown frames.",
            "Content: " . substr($extracted_text, 0, 1000)
        );

        if (is_wp_error($res)) {
            return array();
        }

        $decoded = json_decode($this->clean_markdown_html($res), true);
        return is_array($decoded) ? $decoded : array();
    }

    private function optimize_and_minify_html($html, $prompt_hint = '') {
        // Strip out temporary inline HTML/comment nodes: <!-- ... -->
        $html = preg_replace('/<!--(.*?)-->/is', '', $html);

        // Strip excessive whitespace characters but preserve correct tag formatting
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        $html = preg_replace('/ +/', ' ', $html);
        $html = preg_replace('/\s*=\s*/', '=', $html);

        // Process images with missing/empty src attributes, or placeholder src targets
        $category = 'minimalist';
        if (!empty($prompt_hint)) {
            $prompt_lower = strtolower($prompt_hint);
            if (strpos($prompt_lower, 'coffee') !== false || strpos($prompt_lower, 'cafe') !== false) {
                $category = 'coffee';
            } elseif (strpos($prompt_lower, 'tech') !== false || strpos($prompt_lower, 'saas') !== false || strpos($prompt_lower, 'software') !== false || strpos($prompt_lower, 'developer') !== false) {
                $category = 'technology';
            } elseif (strpos($prompt_lower, 'portfolio') !== false || strpos($prompt_lower, 'design') !== false || strpos($prompt_lower, 'creative') !== false) {
                $category = 'design';
            } elseif (strpos($prompt_lower, 'store') !== false || strpos($prompt_lower, 'shop') !== false || strpos($prompt_lower, 'commerce') !== false) {
                $category = 'shopping';
            } elseif (strpos($prompt_lower, 'agency') !== false || strpos($prompt_lower, 'corporate') !== false || strpos($prompt_lower, 'business') !== false) {
                $category = 'business';
            }
        }

        $html = preg_replace_callback('/<img\s([^>]*?)>/is', function($matches) use ($category) {
            $attrs = $matches[1];
            
            $has_src = preg_match('/src=[\'"]([^\'"]*?)[\'"]/i', $attrs, $src_matches);
            $needs_placeholder = false;
            if (!$has_src) {
                $needs_placeholder = true;
            } else {
                $src_val = trim($src_matches[1]);
                if (empty($src_val) || $src_val === '#' || strpos($src_val, 'placeholder') !== false || strpos($src_val, 'via.placeholder') !== false) {
                    $needs_placeholder = true;
                }
            }

            if ($needs_placeholder) {
                $rand_photo_id = rand(100, 999);
                $unsplash_url = "https://images.unsplash.com/photo-" . ($rand_photo_id == 150 ? '1500000000000' : '1507525428034b') . "?auto=format&fit=crop&w=1200&q=80&sig=" . $rand_photo_id . "&q=" . urlencode($category);
                
                if ($has_src) {
                    $attrs = preg_replace('/src=[\'"]([^\'"]*?)[\'"]/i', 'src="' . $unsplash_url . '"', $attrs);
                } else {
                    $attrs .= ' src="' . $unsplash_url . '"';
                }
            }
            
            return '<img ' . trim($attrs) . '>';
        }, $html);

        return trim($html);
    }

    private function generate_vector_favicon_svg($prompt_hint = '') {
        $color_start = '#3b82f6';
        $color_end = '#1d4ed8';
        $letter = 'S';

        if (!empty($prompt_hint)) {
            $prompt_lower = strtolower($prompt_hint);
            $words = preg_split('/\s+/', trim($prompt_hint));
            if (count($words) > 0 && !empty($words[0])) {
                $letter = strtoupper(substr($words[0], 0, 1));
            }
            if (strpos($prompt_lower, 'coffee') !== false || strpos($prompt_lower, 'cafe') !== false) {
                $color_start = '#f59e0b';
                $color_end = '#78350f';
                $letter = '☕';
            } elseif (strpos($prompt_lower, 'store') !== false || strpos($prompt_lower, 'shop') !== false || strpos($prompt_lower, 'checkout') !== false) {
                $color_start = '#10b981';
                $color_end = '#065f46';
                $letter = '🛒';
            } elseif (strpos($prompt_lower, 'tech') !== false || strpos($prompt_lower, 'saas') !== false || strpos($prompt_lower, 'software') !== false) {
                $color_start = '#3b82f6';
                $color_end = '#1e3a8a';
                $letter = '⚡';
            } elseif (strpos($prompt_lower, 'portfolio') !== false || strpos($prompt_lower, 'design') !== false) {
                $color_start = '#ec4899';
                $color_end = '#9d174d';
                $letter = '🎨';
            } elseif (strpos($prompt_lower, 'restaurant') !== false || strpos($prompt_lower, 'food') !== false) {
                $color_start = '#ef4444';
                $color_end = '#991b1b';
                $letter = '🍽️';
            }
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">';
        $svg .= '<defs>';
        $svg .= '<linearGradient id="favGrad" x1="0%" y1="0%" x2="100%" y2="100%">';
        $svg .= '<stop offset="0%" stop-color="' . $color_start . '" />';
        $svg .= '<stop offset="100%" stop-color="' . $color_end . '" />';
        $svg .= '</linearGradient>';
        $svg .= '</defs>';
        $svg .= '<circle cx="50" cy="50" r="46" fill="url(#favGrad)" stroke="rgba(255,255,255,0.15)" stroke-width="4" />';
        if (mb_strlen($letter, 'UTF-8') === 1 && !preg_match('/[\x{1F300}-\x{1F6FF}]/u', $letter)) {
            $svg .= '<text x="50" y="65" font-family="\'Inter\', sans-serif" font-weight="900" font-size="44" fill="#ffffff" text-anchor="middle" dominant-baseline="middle">' . $letter . '</text>';
        } else {
            $svg .= '<text x="50" y="58" font-size="44" text-anchor="middle" dominant-baseline="middle">' . $letter . '</text>';
        }
        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function publish_visual_page_ajax() {
        check_ajax_referer('sabis_publish_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }

        if (isset($_POST['pages'])) {
            $pages = json_decode(stripslashes($_POST['pages']), true);
            if (is_array($pages)) {
                $published = array();
                $audit = "Server-side self-healing log: Multi-page mass publication started.";
                foreach ($pages as $p) {
                    $p_title = sanitize_text_field($p['title']);
                    $p_html = $p['html'];
                    
                    // Self-healing div checks for each page
                    $opens = substr_count($p_html, '<div');
                    $closes = substr_count($p_html, '</div>');
                    if ($opens > $closes) {
                        $p_html .= str_repeat('</div>', $opens - $closes);
                    }
                    
                    // Run minifier & placeholders injection
                    $p_html = $this->optimize_and_minify_html($p_html, $p_title);
                    $favicon_uri = $this->generate_vector_favicon_svg($p_title);

                    $seo_meta_title = $p_title;
                    $seo_meta_desc = "";
                    $json_ld = "";
                    
                    $seo = $this->generate_seo_meta($p_html);
                    if (!empty($seo)) {
                        $seo_meta_title = isset($seo['meta_title']) ? sanitize_text_field($seo['meta_title']) : $p_title;
                        $seo_meta_desc = isset($seo['meta_description']) ? sanitize_text_field($seo['meta_description']) : "";
                        $json_ld = isset($seo['json_ld']) ? $seo['json_ld'] : "";
                        if (is_array($json_ld)) {
                            $json_ld = wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                        }
                    }
                    
                    $sanitized_html = wp_kses_post($p_html);
                    $final_content = "
                        <div class='sabis-canvas-body-wrap' style='all: unset;'>
                            <link rel='shortcut icon' href='" . $favicon_uri . "' type='image/svg+xml'>
                            <script src='https://cdn.tailwindcss.com'></script>
                            " . $sanitized_html . "
                        </div>
                    ";
                    if (!empty($json_ld)) {
                        $final_content .= "\n<script type=\"application/ld+json\">\n" . $json_ld . "\n</script>";
                    }
                    
                    $post_id = wp_insert_post(array(
                        'post_title' => $p_title,
                        'post_content' => $final_content,
                        'post_status' => 'publish',
                        'post_type' => 'page',
                    ));
                    
                    if (!is_wp_error($post_id)) {
                        update_post_meta($post_id, '_sabis_meta_title', $seo_meta_title);
                        update_post_meta($post_id, '_sabis_meta_desc', $seo_meta_desc);
                        update_post_meta($post_id, '_sabis_json_ld_schema', $json_ld);
                        $published[] = array(
                            'title' => $p_title,
                            'permalink' => get_permalink($post_id)
                        );
                    }
                }
                wp_send_json_success(array(
                    'multi' => true,
                    'pages' => $published,
                    'audit' => $audit . " Successfully published " . count($published) . " pages."
                ));
                return;
            }
        }

        $html = $_POST['html'];
        $title = sanitize_text_field($_POST['title']);

        if (empty($html)) {
            wp_send_json_error(array('error' => __('Empty Canvas contents.', 'sabis-ai-studio')));
        }

        // Self-Healing div checks
        $opens = substr_count($html, '<div');
        $closes = substr_count($html, '</div>');
        $audit = "Server-side self-healing log: Verified " . $opens . " opens against " . $closes . " closes.";
        if ($opens > $closes) {
            $html .= str_repeat('</div>', $opens - $closes);
            $audit .= " Restructured and healed with balanced tags.";
        }

        // Run minifier & placeholders injection
        $html = $this->optimize_and_minify_html($html, $title);
        $favicon_uri = $this->generate_vector_favicon_svg($title);

        // 1-Click AI SEO parameters generation (Groq Llama 3)
        $meta_title = $title;
        $meta_desc = "";
        $json_ld = "";

        $seo = $this->generate_seo_meta($html);
        if (!empty($seo)) {
            $meta_title = isset($seo['meta_title']) ? sanitize_text_field($seo['meta_title']) : $title;
            $meta_desc = isset($seo['meta_description']) ? sanitize_text_field($seo['meta_description']) : "";
            $json_ld = isset($seo['json_ld']) ? $seo['json_ld'] : "";
            if (is_array($json_ld)) {
                $json_ld = wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        }

        $sanitized_html = wp_kses_post($html);
        $final_content = "
            <div class='sabis-canvas-body-wrap' style='all: unset;'>
                <link rel='shortcut icon' href='" . $favicon_uri . "' type='image/svg+xml'>
                <script src='https://cdn.tailwindcss.com'></script>
                " . $sanitized_html . "
            </div>
        ";

        if (!empty($json_ld)) {
            $final_content .= "\n<script type=\"application/ld+json\">\n" . $json_ld . "\n</script>";
        }

        $post_id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $final_content,
            'post_status' => 'publish',
            'post_type' => 'page',
        ));

        if (is_wp_error($post_id)) {
            wp_send_json_error(array('error' => $post_id->get_error_message()));
        }

        // Store Custom Meta Fields
        update_post_meta($post_id, '_sabis_meta_title', $meta_title);
        update_post_meta($post_id, '_sabis_meta_desc', $meta_desc);
        update_post_meta($post_id, '_sabis_json_ld_schema', $json_ld);

        $audit .= " Generated Premium Meta & JSON-LD schema.";

        wp_send_json_success(array(
            'permalink' => get_permalink($post_id),
            'post_id' => $post_id,
            'audit' => $audit
        ));
    }

    public function regenerate_section_via_gemini_ajax() {
        check_ajax_referer('sabis_regenerate_section_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $section_html = $_POST['section_html'];
        $instruction = sanitize_textarea_field($_POST['instruction']);
        $theme_preset = sanitize_text_field($_POST['theme']);
        $api_key = get_option(self::OPTION_API_KEY, '');
        $stripe_key = get_option(self::OPTION_STRIPE_KEY, '');

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Missing API Key', 'sabis-ai-studio')));
        }

        $stripe_prompt_addon = !empty($stripe_key) 
            ? "We have an active Stripe Publishable Key: '{$stripe_key}'. If checkout/payment features are being updated, bind this key '{$stripe_key}' securely using Stripe Elements or Stripe Checkout scripting."
            : "";

        $system_instruction = "You are an elite Tailwind master. Redesign the raw block provided according to instruction. Output ONLY valid pure HTML/Tailwind CSS tags matching preset: " . $theme_preset . ". {$stripe_prompt_addon} Do not write markdown blocks or explain.";
        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => "Current Block HTML:\n" . $section_html . "\nInstruction: " . $instruction)
                    )
                )
            ),
            'systemInstruction' => array(
                'parts' => array(
                    array('text' => $system_instruction)
                )
            )
        );

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=" . $api_key;
        $response = wp_remote_post($endpoint, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($body),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('error' => $response->get_error_message()));
        }

        $res_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($res_body['candidates'][0]['content']['parts'][0]['text'])) {
            $html = $this->clean_markdown_html($res_body['candidates'][0]['content']['parts'][0]['text']);
            $html = $this->apply_isolate_wrapper($html);
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('error' => __('Error contacting Gemini API.', 'sabis-ai-studio')));
        }
    }

    public function get_wordpress_page_ajax() {
        check_ajax_referer('sabis_get_page_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $page_id = isset($_POST['page_id']) ? intval($_POST['page_id']) : 0;
        if (!$page_id) {
            wp_send_json_error(array('error' => __('Invalid page ID.', 'sabis-ai-studio')));
        }
        $post = get_post($page_id);
        if (!$post || $post->post_type !== 'page') {
            wp_send_json_error(array('error' => __('Page not found.', 'sabis-ai-studio')));
        }

        $content = $post->post_content;

        // Try to locate the sabis-canvas-body-wrap element content
        $html = '';
        if (preg_match('/<div class=[\'"]sabis-canvas-body-wrap[\'"][^>]*>(.*?)<\/div>/is', $content, $matches)) {
            $html = trim($matches[1]);
            // Remove Tailwind script tag if included in matches to keep it clean
            $html = preg_replace('/<script[^>]*src=[\'"]https:\/\/cdn\.tailwindcss\.com[\'"][^>]*><\/script>/is', '', $html);
        } else {
            $html = trim($content);
        }

        wp_send_json_success(array(
            'html' => $html,
            'title' => $post->post_title
        ));
    }

    public function render_section_shortcode($atts) {
        $args = shortcode_atts(array('id' => 0), $atts);
        if (empty($args['id'])) return '';
        $sect = get_post($args['id']);
        if (!$sect || 'sabis_section' !== $sect->post_type) return '';
        return "
            <div class='sabis-shortcode-snippet-wrap' style='all:unset;'>
                <script src='https://cdn.tailwindcss.com'></script>
                " . wp_kses_post($sect->post_content) . "
            </div>
        ";
    }

    public function save_section_block_ajax() {
        check_ajax_referer('sabis_publish_nonce', 'nonce');
        if (!current_user_can('edit_pages')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $html = $_POST['html'];
        $title = sanitize_text_field($_POST['title']);

        // Optimize and minify block HTML before insertion to database
        $html = $this->optimize_and_minify_html($html, $title);

        $id = wp_insert_post(array(
            'post_title' => $title,
            'post_content' => $html,
            'post_status' => 'publish',
            'post_type' => 'sabis_section',
        ));
        if (!is_wp_error($id)) {
            wp_send_json_success(array('shortcode' => '[sabis_section id="' . $id . '"]'));
        } else {
            wp_send_json_error();
        }
    }

    public function auto_webp_upload_ajax() {
        check_ajax_referer('sabis_upload_nonce', 'nonce');
        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('error' => __('Permissions error.', 'sabis-ai-studio')));
        }
        $files = isset($_FILES['files']) ? $_FILES['files'] : $_FILES['file'];
        if (empty($files)) {
            wp_send_json_error();
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $urls = array();
        if (is_array($files['name'])) {
            foreach ($files['name'] as $k => $name) {
                if ($files['error'][$k] === UPLOAD_ERR_OK) {
                    $arr = array(
                        'name' => $files['name'][$k],
                        'type' => $files['type'][$k],
                        'tmp_name' => $files['tmp_name'][$k],
                        'error' => $files['error'][$k],
                        'size' => $files['size'][$k],
                    );
                    $movefile = wp_handle_upload($arr, array('test_form' => false));
                    if ($movefile && !isset($movefile['error'])) {
                        $urls[] = $movefile['url'];
                    }
                }
            }
        } else {
            if ($files['error'] === UPLOAD_ERR_OK) {
                $movefile = wp_handle_upload($files, array('test_form' => false));
                if ($movefile && !isset($movefile['error'])) {
                    $urls[] = $movefile['url'];
                }
            }
        }

        if (!empty($urls)) {
            wp_send_json_success($urls);
        } else {
            wp_send_json_error();
        }
    }

    public function auto_webp_media_handler($file, $time) {
        if (!preg_match('/(image\/jpeg|image\/png)/i', $file['type'])) {
            return $file;
        }
        $editor = wp_get_image_editor($file['file']);
        if (!is_wp_error($editor)) {
            $size = $editor->get_size();
            if ($size['width'] > 2000) {
                $editor->resize(1920, null, false);
            }
            $info = pathinfo($file['file']);
            $webp_fn = $info['filename'] . '.webp';
            $webp_fp = $info['dirname'] . '/' . $webp_fn;
            $saved = $editor->save($webp_fp, 'image/webp');
            if (!is_wp_error($saved)) {
                @unlink($file['file']);
                $file['file'] = $webp_fp;
                $file['url'] = str_replace($info['basename'], $webp_fn, $file['url']);
                $file['type'] = 'image/webp';
            }
        }
        return $file;
    }

    public function add_sabis_row_actions($actions, $post) {
        if (current_user_can('edit_pages') && in_array($post->post_type, array('page', 'post', 'sabis_section'))) {
            $link = admin_url('admin.php?page=sabis-ai-studio&sabis_edit_page=' . $post->ID);
            $actions['sabis_edit'] = '<a href="' . esc_url($link) . '" style="color:#8b5cf6; font-weight:bold; text-shadow:0 0 1px rgba(139,92,246,0.1);">✨ Edit with Sabis AI Studio</a>';
        }
        return $actions;
    }

    public function add_sabis_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('edit_pages')) {
            return;
        }

        $post_id = 0;
        if (is_admin()) {
            global $pagenow;
            if ('post.php' === $pagenow && isset($_GET['post'])) {
                $post_id = intval($_GET['post']);
            }
        } else {
            if (is_singular()) {
                $post_id = get_the_ID();
            }
        }

        $url = admin_url('admin.php?page=sabis-ai-studio');
        if ($post_id > 0) {
            $url = admin_url('admin.php?page=sabis-ai-studio&sabis_edit_page=' . $post_id);
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'sabis-ai-studio-shortcut',
            'title' => '<span class="ab-icon dashicons dashicons-superhero" style="color:#facc15;"></span> <span style="font-weight:700; color:#fff;">🚀 Edit with Sabis AI Studio</span>',
            'href'  => $url,
            'meta'  => array(
                'title' => __('Edit current template inside the design canvas', 'sabis-ai-studio')
            )
        ));
    }

    public function inject_gutenberg_sabis_buttons() {
        global $pagenow;
        if (!in_array($pagenow, array('post.php', 'post-new.php'))) {
            return;
        }

        $post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
        $url = admin_url('admin.php?page=sabis-ai-studio');
        if ($post_id > 0) {
            $url = admin_url('admin.php?page=sabis-ai-studio&sabis_edit_page=' . $post_id);
        }

        ?>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const checkGutenbergHeader = setInterval(function() {
                const editPostHeader = document.querySelector('.edit-post-header__settings') || document.querySelector('.interface-interface-skeleton__header');
                if (editPostHeader) {
                    clearInterval(checkGutenbergHeader);
                    
                    if (document.getElementById('sabis-gutenberg-shortcut')) return;
                    
                    const button = document.createElement('button');
                    button.id = 'sabis-gutenberg-shortcut';
                    button.type = 'button';
                    button.className = 'components-button is-primary';
                    button.style.backgroundColor = '#8b5cf6';
                    button.style.borderColor = '#8b5cf6';
                    button.style.fontWeight = 'bold';
                    button.style.color = '#fff';
                    button.style.borderRadius = '6px';
                    button.style.marginLeft = '10px';
                    button.style.marginRight = '10px';
                    button.style.display = 'inline-flex';
                    button.style.alignItems = 'center';
                    button.style.gap = '6px';
                    button.style.height = '32px';
                    button.style.padding = '0 12px';
                    button.innerHTML = '🚀 Edit with Sabis AI Studio';
                    
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        const wpEditorSaveBtn = document.querySelector('.editor-post-publish-button__button') || document.querySelector('.editor-post-save-draft');
                        if (wpEditorSaveBtn) {
                            wpEditorSaveBtn.click();
                        }
                        setTimeout(function() {
                            window.location.href = "<?php echo esc_js($url); ?>";
                        }, 600);
                    });
                    
                    editPostHeader.insertBefore(button, editPostHeader.firstChild);
                }
            }, 500);

            const classicTitle = document.getElementById('titlediv');
            if (classicTitle && !document.getElementById('sabis-classic-shortcut')) {
                const link = document.createElement('a');
                link.id = 'sabis-classic-shortcut';
                link.href = "<?php echo esc_js($url); ?>";
                link.className = 'button button-primary button-large';
                link.style.backgroundColor = '#8b5cf6';
                link.style.borderColor = '#7c3aed';
                link.style.color = '#fff';
                link.style.fontWeight = 'bold';
                link.style.marginLeft = '10px';
                link.style.display = 'inline-block';
                link.style.verticalAlign = 'middle';
                link.innerHTML = '🚀 Edit with Sabis AI Studio';
                
                link.addEventListener('click', function(e) {
                    const saveDraftBtn = document.getElementById('save-post');
                    if (saveDraftBtn) {
                        e.preventDefault();
                        saveDraftBtn.click();
                        setTimeout(function() {
                            window.location.href = "<?php echo esc_js($url); ?>";
                        }, 900);
                    }
                });

                classicTitle.appendChild(link);
            }
        });
        </script>
        <?php
    }
}

add_action('plugins_loaded', array('Sabis_AI_Studio', 'get_instance'));
