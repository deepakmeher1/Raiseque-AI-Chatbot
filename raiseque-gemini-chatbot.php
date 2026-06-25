<?php
/**
 * Plugin Name: Raiseque Ai Chatbot
 * Plugin URI: https://raiseque.com
 * Description: An automated AI chatbot for WordPress. Dynamically syncs your website pages and posts to answer any user queries in real-time using Google Gemini, featuring custom colors, secure server-side proxying, and IP-based rate limiting.
 * Version: 1.0.0
 * Author: Deepak Ku Meher
 * Author URI: https://raiseque.com
 * License: GPL2
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define Constants.
define( 'RQ_CHATBOT_VERSION', '1.0.0' );
define( 'RQ_CHATBOT_PATH', plugin_dir_path( __FILE__ ) );
define( 'RQ_CHATBOT_URL', plugin_dir_url( __FILE__ ) );

/**
 * Configure your Google Apps Script Web App URL below for user registrations.
 * If left as the placeholder, the plugin will run in "Local Test Mode" (verification code is 123456).
 */
define( 'RQ_CHATBOT_REGISTRATION_URL', 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE' );

/**
 * Configure your GitHub repository below for automatic updates.
 */
define( 'RQ_CHATBOT_GITHUB_OWNER', 'deepakmeher1' );
define( 'RQ_CHATBOT_GITHUB_REPO', 'Raiseque-AI-Chatbot' );

// Include required files.
require_once RQ_CHATBOT_PATH . 'admin-settings.php';
require_once RQ_CHATBOT_PATH . 'chatbot-api.php';

/**
 * Enqueue scripts and styles on the frontend.
 */
function rq_chatbot_enqueue_scripts() {
    // Only enqueue if the plugin is activated/registered.
    if ( get_option( 'rq_chatbot_is_activated' ) !== '1' ) {
        return;
    }

    // Enqueue Stylesheet.
    wp_enqueue_style(
        'rq-chatbot-style',
        RQ_CHATBOT_URL . 'chatbot-widget.css',
        array(),
        RQ_CHATBOT_VERSION
    );

    // Enqueue Javascript.
    wp_enqueue_script(
        'rq-chatbot-script',
        RQ_CHATBOT_URL . 'chatbot-widget.js',
        array(),
        RQ_CHATBOT_VERSION,
        true
    );

    // Dynamic settings from database.
    $primary_color = get_option( 'rq_chatbot_primary_color', '#94DC5A' );
    $header_bg = get_option( 'rq_chatbot_header_bg', '#162B49' );
    $header_text = get_option( 'rq_chatbot_header_text_color', '#ffffff' );
    $input_text = get_option( 'rq_chatbot_input_text_color', '#1f2937' );
    $user_msg_text = get_option( 'rq_chatbot_user_msg_text_color', '#162B49' );
    $bot_msg_text = get_option( 'rq_chatbot_bot_msg_text_color', '#1f2937' );

    $welcome_msg = get_option( 'rq_chatbot_welcome_msg', 'Hi! I am the Raiseque AI Assistant. How can I help you grow your business today?' );
    $bot_title = get_option( 'rq_chatbot_title', 'Raiseque AI Assistant' );
    $position = get_option( 'rq_chatbot_position', 'right' );

    // Inline CSS variables.
    $custom_css = "
        :root {
            --rq-chat-primary: " . esc_attr( $primary_color ) . ";
            --rq-chat-header-bg: " . esc_attr( $header_bg ) . ";
            --rq-chat-header-text: " . esc_attr( $header_text ) . ";
            --rq-chat-input-text: " . esc_attr( $input_text ) . ";
            --rq-chat-user-text: " . esc_attr( $user_msg_text ) . ";
            --rq-chat-bot-text: " . esc_attr( $bot_msg_text ) . ";
        }
    ";
    wp_add_inline_style( 'rq-chatbot-style', $custom_css );

    // Localize data for JS.
    wp_localize_script(
        'rq-chatbot-script',
        'rqChatbotSettings',
        array(
            'apiUrl'     => esc_url_raw( get_rest_url( null, '/rq-chatbot/v1/chat' ) ),
            'welcomeMsg' => esc_html( $welcome_msg ),
            'botTitle'   => esc_html( $bot_title ),
            'position'   => esc_attr( $position ),
            'nonce'      => wp_create_nonce( 'wp_rest' )
        )
    );
}
add_action( 'wp_enqueue_scripts', 'rq_chatbot_enqueue_scripts' );

/**
 * Add settings action link next to Activate/Deactivate.
 */
function rq_chatbot_plugin_action_links( $links ) {
    $settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=rq-chatbot-settings' ) ) . '">' . __( 'Settings', 'rq-chatbot' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'rq_chatbot_plugin_action_links' );

/**
 * Add custom row meta links (View details | Getting Started | Documentation) next to Version.
 */
function rq_chatbot_plugin_row_meta( $links, $file ) {
    if ( strpos( $file, 'raiseque-gemini-chatbot.php' ) !== false ) {
        $new_links = array(
            'view_details'    => '<a href="' . esc_url( admin_url( 'options-general.php?page=rq-chatbot-settings#tab-info' ) ) . '">' . __( 'View details', 'rq-chatbot' ) . '</a>',
            'getting_started' => '<a href="' . esc_url( admin_url( 'options-general.php?page=rq-chatbot-settings#tab-info' ) ) . '">' . __( 'Getting Started', 'rq-chatbot' ) . '</a>',
            'documentation'   => '<a href="' . esc_url( admin_url( 'options-general.php?page=rq-chatbot-settings#tab-info' ) ) . '">' . __( 'Documentation', 'rq-chatbot' ) . '</a>'
        );
        $links = array_merge( $links, $new_links );
    }
    return $links;
}
add_filter( 'plugin_row_meta', 'rq_chatbot_plugin_row_meta', 10, 2 );

/**
 * Check for updates from GitHub Raw CDN info.json.
 */
function rq_chatbot_check_github_updates( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }

    $repo_owner = defined( 'RQ_CHATBOT_GITHUB_OWNER' ) ? RQ_CHATBOT_GITHUB_OWNER : 'deepakmeher1';
    $repo_name  = defined( 'RQ_CHATBOT_GITHUB_REPO' ) ? RQ_CHATBOT_GITHUB_REPO : 'Raiseque-AI-Chatbot';

    $info_url = "https://raw.githubusercontent.com/{$repo_owner}/{$repo_name}/main/info.json";

    $args = array(
        'timeout' => 10,
        'headers' => array(
            'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
        )
    );

    $response = wp_safe_remote_get( $info_url, $args );
    if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
        return $transient;
    }

    $info = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! $info || empty( $info['version'] ) ) {
        return $transient;
    }

    $new_version = ltrim( $info['version'], 'v' );
    $plugin_slug = 'raiseque-gemini-chatbot/raiseque-gemini-chatbot.php';

    if ( version_compare( RQ_CHATBOT_VERSION, $new_version, '<' ) ) {
        $obj = new stdClass();
        $obj->slug        = 'raiseque-gemini-chatbot';
        $obj->plugin      = $plugin_slug;
        $obj->new_version = $new_version;
        $obj->url         = $info['homepage'];
        $obj->package     = $info['download_url'];

        $transient->response[ $plugin_slug ] = $obj;
    }

    return $transient;
}
add_filter( 'pre_set_site_transient_update_plugins', 'rq_chatbot_check_github_updates' );

/**
 * Handle display of plugin details in the updates popup modal from GitHub.
 */
function rq_chatbot_github_plugin_popup_info( $res, $action, $args ) {
    if ( $action !== 'plugin_information' ) {
        return $res;
    }

    if ( isset( $args->slug ) && $args->slug === 'raiseque-gemini-chatbot' ) {
        $repo_owner = defined( 'RQ_CHATBOT_GITHUB_OWNER' ) ? RQ_CHATBOT_GITHUB_OWNER : 'deepakmeher1';
        $repo_name  = defined( 'RQ_CHATBOT_GITHUB_REPO' ) ? RQ_CHATBOT_GITHUB_REPO : 'Raiseque-AI-Chatbot';

        $info_url = "https://raw.githubusercontent.com/{$repo_owner}/{$repo_name}/main/info.json";
        
        $response = wp_safe_remote_get( $info_url, array(
            'timeout' => 10,
            'headers' => array(
                'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . home_url(),
            )
        ) );
        
        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $info = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( $info ) {
                $res = new stdClass();
                $res->name           = $info['name'];
                $res->slug           = 'raiseque-gemini-chatbot';
                $res->version        = $info['version'];
                $res->author         = 'Deepak Ku Meher (Raiseque)';
                $res->homepage       = $info['homepage'];
                $res->download_link  = $info['download_url'];
                $res->sections       = array(
                    'description' => isset( $info['sections']['description'] ) ? $info['sections']['description'] : '',
                    'changelog'   => isset( $info['sections']['changelog'] ) ? $info['sections']['changelog'] : ''
                );
                return $res;
            }
        }
    }

    return $res;
}
add_filter( 'plugins_api', 'rq_chatbot_github_plugin_popup_info', 20, 3 );

/**
 * Ensure the extracted folder is renamed correctly to 'raiseque-gemini-chatbot'
 * during update installation (using the source selection filter).
 */
function rq_chatbot_rename_github_zipball( $source, $remote_source, $upgrader, $hook_extra = array() ) {
    global $wp_filesystem;
    
    // Only proceed if our main plugin file exists inside the extracted source folder
    if ( $wp_filesystem->exists( $source . 'raiseque-gemini-chatbot.php' ) ) {
        $parent = trailingslashit( dirname( $source ) );
        $new_source = $parent . 'raiseque-gemini-chatbot/';
        
        if ( $source !== $new_source ) {
            if ( $wp_filesystem->move( $source, $new_source, true ) ) {
                return $new_source;
            }
        }
    }
    return $source;
}
add_filter( 'upgrader_source_selection', 'rq_chatbot_rename_github_zipball', 10, 4 );

