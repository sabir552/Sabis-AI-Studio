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
        add_action('wp_ajax_sabis_screenshot_to_code', array($this, 'screenshot_to_code_ajax'));
        add_action('wp_ajax_sabis_generate_component', array($this, 'generate_component_ajax'));

        add_shortcode('sabis_section', array($this, 'render_section_shortcode'));
        add_filter('wp_handle_upload', array($this, 'auto_webp_media_handler'), 10, 2);
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
                box-sizing: border-box; 
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
                padding: 24px; 
                background: #0f172a; 
                color: #f1f5f9;
                min-height: calc(100vh - 40px); 
            }
            .sabis-dashboard-wrap h1, .sabis-dashboard-wrap h2, .sabis-dashboard-wrap h3, .sabis-dashboard-wrap h4 {
                color: #f1f5f9 !important;
            }
            .sabis-card { 
                background: rgba(30, 41, 59, 0.7); 
                backdrop-filter: blur(12px);
                -webkit-backdrop-filter: blur(12px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 10px 30px 0 rgba(0, 0, 0, 0.25); 
                border-radius: 16px; 
                padding: 28px; 
                margin-bottom: 24px; 
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .sabis-card:hover {
                box-shadow: 0 12px 40px 0 rgba(0, 0, 0, 0.35);
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
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'sabis-ai-studio') . '</p></div>';
        }

        $api_key = get_option(self::OPTION_API_KEY, '');
        $groq_api_key = get_option(self::OPTION_GROQ_KEY, '');
        $webhook_url = get_option(self::OPTION_WEBHOOK_URL, '');
        ?>
        <div class="sabis-dashboard-wrap">
            <h1 class="wp-heading-inline" style="font-weight: 800; font-size: 32px; letter-spacing: -0.025em; margin-bottom: 24px; display: inline-flex; align-items: center;">
                <!-- Stunning Inline SVG Logo -->
                <svg class="sabis-svg-logo" width="40" height="40" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 14px; filter: drop-shadow(0 0 8px rgba(34, 211, 238, 0.5));">
                    <defs>
                        <linearGradient id="sabis-grad" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stop-color="#06b6d4" />
                            <stop offset="100%" stop-color="#8b5cf6" />
                        </linearGradient>
                    </defs>
                    <circle cx="50" cy="50" r="45" stroke="url(#sabis-grad)" stroke-width="4" fill="rgba(15, 23, 42, 0.82)" />
                    <polygon points="50,22 74,40 64,74 36,74 26,40" stroke="url(#sabis-grad)" stroke-width="2" fill="none" />
                    <path d="M50,30 L65,42 L59,62 L41,62 L35,42 Z" fill="url(#sabis-grad)" opacity="0.8" />
                    <circle cx="50" cy="48" r="6" fill="#ffffff" />
                    <path d="M50,15 L50,22 M85,50 L78,50 M50,85 L50,78 M15,50 L22,50" stroke="#06b6d4" stroke-width="3" stroke-linecap="round" />
                </svg>
                <span>Sabis AI Studio</span>
                <span style="font-size: 14px; font-weight: 500; color: #8b5cf6; background: rgba(139, 92, 246, 0.15); padding: 4px 10px; border-radius: 9999px; margin-left:14px; border: 1px solid rgba(139, 92, 246, 0.3);">
                    v1.1.0 Dual-AI Hybrid
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
                    <!-- Text Send Button -->
                    <button type="button" id="sabis-send-btn" class="sabis-send-btn" title="Send text prompt to generator">🚀</button>
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
                        <select id="sabis-theme-preset" style="padding:4px 8px; border-radius:4px; font-size:12px;">
                            <option value="Minimal Light">Minimal Light</option>
                            <option value="Cyberpunk Dark">Cyberpunk Dark</option>
                            <option value="Royal Gold">Royal Gold</option>
                        </select>
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
                micBtn.style.display = 'none';
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
                updateStatus('Dual-AI Router routing using Gemini 2.5 Pro...', 'loading');
                generateBtn.disabled = true;
                if(sendBtn) sendBtn.disabled = true;

                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'sabis_generate_page_via_gemini',
                        prompt: userPrompt,
                        theme: document.getElementById('sabis-theme-preset').value,
                        nonce: '<?php echo wp_create_nonce("sabis_gemini_nonce"); ?>'
                    },
                    success: function(response) {
                        generateBtn.disabled = false;
                        if(sendBtn) sendBtn.disabled = false;
                        if (response.success && response.data.html) {
                            editor.setComponents(response.data.html);
                            // Auto reset to standard margins wrapper
                            const preset = document.getElementById('sabis-theme-preset').value;
                            const wrapper = editor.getWrapper();
                            if (wrapper) {
                                wrapper.removeClass('bg-slate-950 text-cyan-400 bg-zinc-50 text-zinc-900 bg-slate-900 text-yellow-100');
                                if (preset === 'Cyberpunk Dark') {
                                    wrapper.addClass('bg-slate-950 text-cyan-400');
                                } else if (preset === 'Minimal Light') {
                                    wrapper.addClass('bg-zinc-50 text-zinc-900');
                                } else if (preset === 'Royal Gold') {
                                    wrapper.addClass('bg-slate-900 text-yellow-100');
                                }
                            }
                            updateStatus('AI Hybrid Design Synced!', 'ready');
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
            sendBtn.addEventListener('click', handleAILayoutGeneration);

            // Theme Accents Controller
            document.getElementById('sabis-theme-preset').addEventListener('change', function() {
                const preset = this.value;
                const wrapper = editor.getWrapper();
                if (!wrapper) return;
                wrapper.removeClass('bg-slate-950 text-cyan-400 bg-zinc-50 text-zinc-900 bg-slate-900 text-yellow-100');
                if (preset === 'Cyberpunk Dark') {
                    wrapper.addClass('bg-slate-950 text-cyan-400');
                } else if (preset === 'Minimal Light') {
                    wrapper.addClass('bg-zinc-50 text-zinc-900');
                } else if (preset === 'Royal Gold') {
                    wrapper.addClass('bg-slate-900 text-yellow-100');
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
            });

            viewTablet.addEventListener('click', function() {
                clearActiveViewportClass();
                viewTablet.classList.add('active');
                editor.setDevice('tablet');
            });

            viewMobile.addEventListener('click', function() {
                clearActiveViewportClass();
                viewMobile.classList.add('active');
                editor.setDevice('mobile');
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
                        attributes: { title: 'AI Redesigns via Gemini 2.5 Pro' }
                    });
                    model.set('toolbar', toolbar);
                }
            });
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

    private function call_groq($system_prompt, $user_prompt) {
        $groq_api_key = get_option(self::OPTION_GROQ_KEY, '');
        if (empty($groq_api_key)) {
            return new WP_Error('missing_key', __('Groq API key is missing.', 'sabis-ai-studio'));
        }

        $payload = array(
            'model' => 'llama3-8b-8192',
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
        $prompt = sanitize_textarea_field($_POST['prompt']);
        $theme_preset = sanitize_text_field($_POST['theme']);
        $api_key = get_option(self::OPTION_API_KEY, '');

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

        $system_instruction = "You are an elite, modern Web Designer and No-Code Tailwind CSS Master. Output ONLY clean, valid, standalone semantic HTML tags with inline Tailwind classes. Ensure the design follows requested accents theme correctly. Do NOT return markdown blocks or any conversational explanations.";

        $body_array = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array('text' => "Design complete block styled forpreset: [ " . $theme_preset . " ]. Instructions: " . $enhanced)
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
            'body' => wp_json_encode($body_array),
            'timeout' => 30
        ));

        if (is_wp_error($response)) {
            wp_send_json_error(array('error' => $response->get_error_message()));
        }

        $res_body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($res_body['candidates'][0]['content']['parts'][0]['text'])) {
            $html = $this->clean_markdown_html($res_body['candidates'][0]['content']['parts'][0]['text']);
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('error' => __('Verification: Call returned no components from Gemini API.', 'sabis-ai-studio')));
        }
    }

    public function copywrite_via_gemini_ajax() {
        check_ajax_referer('sabis_copywrite_nonce', 'nonce');
        $text = sanitize_textarea_field($_POST['text']);
        $command = sanitize_text_field($_POST['command']);

        $res = $this->call_groq(
            "You are an expert SaaS copywriter. Transform the given text with action: " . $command . ". Return back ONLY rewritten string with zero explanation or quotes.",
            "Transform text: " . $text
        );

        if (is_wp_error($res)) {
            wp_send_json_error(array('error' => $res->get_error_message()));
        }

        $output = preg_replace('/^["\']|["\']$/', '', $res);
        $output = $this->clean_markdown_html($output);
        wp_send_json_success(array('text' => $output));
    }

    public function translate_via_gemini_ajax() {
        check_ajax_referer('sabis_translate_nonce', 'nonce');
        $text = $_POST['text'];

        $res = $this->call_groq(
            "You are a localization module. Translate leaf text nodes from English to exquisite marketing Urdu. You MUST isolate leaf nodes, leaving 100% of Tailwind classes and HTML tag bracket properties strictly unchanged.",
            $text
        );

        if (is_wp_error($res)) {
            wp_send_json_error(array('error' => $res->get_error_message()));
        }

        $output = $this->clean_markdown_html($res);
        wp_send_json_success(array('text' => $output));
    }

    public function screenshot_to_code_ajax() {
        check_ajax_referer('sabis_vision_nonce', 'nonce');
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
        $prompt = sanitize_textarea_field($_POST['prompt']);

        $res = $this->call_groq(
            "You are a premium UI widget generator. Compile vanilla semantic HTML paired with lightweight CSS/vanilla javascript inside script tags or Alpine.js. Do not translate or discuss, return ONLY complete component code with zero markdown wrapping blocks.",
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

    public function publish_visual_page_ajax() {
        check_ajax_referer('sabis_publish_nonce', 'nonce');
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
        $section_html = $_POST['section_html'];
        $instruction = sanitize_textarea_field($_POST['instruction']);
        $theme_preset = sanitize_text_field($_POST['theme']);
        $api_key = get_option(self::OPTION_API_KEY, '');

        if (empty($api_key)) {
            wp_send_json_error(array('error' => __('Missing API Key', 'sabis-ai-studio')));
        }

        $system_instruction = "You are an elite Tailwind master. Redesign the raw block provided according to instruction. Output ONLY valid pure HTML/Tailwind CSS tags matching preset: " . $theme_preset . ". Do not write markdown blocks or explain.";
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
            wp_send_json_success(array('html' => $html));
        } else {
            wp_send_json_error(array('error' => __('Error contacting Gemini API.', 'sabis-ai-studio')));
        }
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
        $html = $_POST['html'];
        $title = sanitize_text_field($_POST['title']);
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
}

add_action('plugins_loaded', array('Sabis_AI_Studio', 'get_instance'));
