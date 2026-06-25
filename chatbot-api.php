<?php
/**
 * REST API Endpoint, Content Sync, and Lead Capture for Raiseque Gemini Chatbot.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API route.
 */
function rq_chatbot_register_rest_route() {
    register_rest_route(
        'rq-chatbot/v1',
        '/chat',
        array(
            'methods'             => 'POST',
            'callback'            => 'rq_chatbot_handle_api_chat',
            'permission_callback' => '__return_true', // Publicly accessible endpoint.
        )
    );
}
add_action( 'rest_api_init', 'rq_chatbot_register_rest_route' );

/**
 * Dynamically fetch all website pages and posts, clean content, and cache the result.
 */
function rq_chatbot_get_website_content() {
    $cached_content = get_transient( 'rq_chatbot_site_content' );
    if ( false !== $cached_content ) {
        return $cached_content;
    }

    $site_text = "\n\n### CURRENT WEBSITE CONTENT (USE THIS TO ANSWER USER QUESTIONS):\n";
    $max_chars = 80000; // Strict budget to prevent exceeding Gemini free tier token limits (250k input tokens/min).
    $current_chars = 0;

    // 1. Fetch Pages (contain core business info like services, contact, about us)
    $pages_query = new WP_Query( array(
        'post_type'      => 'page',
        'post_status'    => 'publish',
        'posts_per_page' => 40,
        'orderby'        => 'menu_order ID',
        'order'          => 'ASC'
    ) );

    if ( $pages_query->have_posts() ) {
        while ( $pages_query->have_posts() ) {
            $pages_query->the_post();
            
            if ( $current_chars >= $max_chars ) {
                break;
            }

            $title = get_the_title();
            $permalink = wp_make_link_relative( get_permalink() );
            $content = get_the_content();
            
            $content = strip_shortcodes( $content );
            $content = wp_strip_all_tags( $content );
            $content = preg_replace( '/\s+/', ' ', $content );
            $content = trim( $content );

            // Truncate individual page content if it's excessively long
            if ( strlen( $content ) > 6000 ) {
                $content = substr( $content, 0, 6000 ) . '... [content truncated]';
            }

            $entry = "Page Path: " . $permalink . "\n";
            $entry .= "Title: " . $title . "\n";
            $entry .= "Content: " . $content . "\n";
            $entry .= "-----------------------------------\n";

            $site_text .= $entry;
            $current_chars += strlen( $entry );
        }
        wp_reset_postdata();
    }

    // 2. Fetch recent Posts (blog articles, news) up to 10 if there is budget remaining
    if ( $current_chars < $max_chars ) {
        $posts_query = new WP_Query( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 10,
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        if ( $posts_query->have_posts() ) {
            while ( $posts_query->have_posts() ) {
                $posts_query->the_post();
                
                if ( $current_chars >= $max_chars ) {
                    break;
                }

                $title = get_the_title();
                $permalink = wp_make_link_relative( get_permalink() );
                $content = get_the_content();
                
                $content = strip_shortcodes( $content );
                $content = wp_strip_all_tags( $content );
                $content = preg_replace( '/\s+/', ' ', $content );
                $content = trim( $content );

                if ( strlen( $content ) > 4000 ) {
                    $content = substr( $content, 0, 4000 ) . '... [content truncated]';
                }

                $entry = "Post Path: " . $permalink . "\n";
                $entry .= "Title: " . $title . "\n";
                $entry .= "Content: " . $content . "\n";
                $entry .= "-----------------------------------\n";

                $site_text .= $entry;
                $current_chars += strlen( $entry );
            }
            wp_reset_postdata();
        }
    }

    if ( $current_chars === 0 ) {
        $site_text .= "No posts or pages found on this website.\n";
    }

    // Cache the result for 24 hours (86400 seconds)
    set_transient( 'rq_chatbot_site_content', $site_text, DAY_IN_SECONDS );

    return $site_text;
}

/**
 * Clear website content cache on updates.
 */
function rq_chatbot_clear_content_cache() {
    delete_transient( 'rq_chatbot_site_content' );
}
add_action( 'save_post', 'rq_chatbot_clear_content_cache' );
add_action( 'deleted_post', 'rq_chatbot_clear_content_cache' );

/**
 * Save captured lead to the WordPress database.
 */
function rq_chatbot_save_lead( $name, $email, $phone ) {
    $lead_id = wp_insert_post(
        array(
            'post_title'   => sanitize_text_field( $name ),
            'post_type'    => 'rq_lead',
            'post_status'  => 'publish',
        )
    );

    if ( $lead_id && ! is_wp_error( $lead_id ) ) {
        update_post_meta( $lead_id, 'rq_lead_email', sanitize_email( $email ) );
        update_post_meta( $lead_id, 'rq_lead_phone', sanitize_text_field( $phone ) );
        return $lead_id;
    }
    return false;
}

/**
 * Asynchronously forward lead data to the configured Google Sheet web app webhook.
 */
function rq_chatbot_send_to_google_sheet( $name, $email, $phone ) {
    $sheet_url = get_option( 'rq_chatbot_google_sheet_url' );
    if ( empty( $sheet_url ) ) {
        return;
    }

    $payload = array(
        'name'  => sanitize_text_field( $name ),
        'email' => sanitize_email( $email ),
        'phone' => sanitize_text_field( $phone ),
        'date'  => current_time( 'mysql' )
    );

    wp_safe_remote_post(
        $sheet_url,
        array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
            'body'      => wp_json_encode( $payload ),
            'blocking'  => false, // Run asynchronously so it doesn't block frontend execution.
        )
    );
}

/**
 * REST API callback to process chat request.
 */
function rq_chatbot_handle_api_chat( WP_REST_Request $request ) {
    // 1. IP-Based Rate Limiting to prevent spam on the free API key.
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $transient_key = 'rq_chat_limit_' . sanitize_key( $user_ip );
    $request_count = (int) get_transient( $transient_key );

    if ( $request_count >= 15 ) { // Max 15 requests per minute per IP.
        return new WP_REST_Response(
            array(
                'reply' => "You have reached the maximum number of queries allowed per minute. Please wait a moment and try again."
            ),
            429
        );
    }
    set_transient( $transient_key, $request_count + 1, 60 ); // Reset in 60 seconds.

    // 2. Validate API key.
    $api_key = get_option( 'rq_chatbot_api_key' );
    if ( empty( $api_key ) ) {
        return new WP_REST_Response(
            array(
                'reply' => "System Configuration Error: Gemini API key is missing. Please ask the administrator to configure it in WordPress Settings > Gemini Chatbot."
            ),
            200 // Return 200 so the chatbot displays the error message gracefully in the UI.
        );
    }

    // 3. Get message and history.
    $params = $request->get_json_params();
    $user_message = isset( $params['message'] ) ? sanitize_text_field( $params['message'] ) : '';
    $history      = isset( $params['history'] ) ? (array) $params['history'] : array();

    if ( empty( $user_message ) ) {
        return new WP_REST_Response( array( 'reply' => "I did not receive any message." ), 400 );
    }

    // 4. Construct Gemini Contents format.
    $system_prompt = get_option( 'rq_chatbot_system_prompt' );
    
    // Dynamically append website content if enabled
    $enable_sync = get_option( 'rq_chatbot_enable_dynamic_sync', '1' );
    if ( $enable_sync === '1' ) {
        $system_prompt .= rq_chatbot_get_website_content();
    }

    // Append strict formatting and length constraints at the end of the system instructions
    $system_prompt .= "\n\n### CRITICAL RESPONSE LENGTH CONSTRAINT:\n" .
        "- You must keep your responses extremely short, concise, and direct.\n" .
        "- Your reply must be maximum 2 to 3 sentences (under 60 words total).\n" .
        "- Never write essays, long lists, or multiple paragraphs. If the user needs more details, ask them first (e.g. 'Would you like more details on this?').\n" .
        "- Answer in a friendly, helpful, chat-agent style. Be to-the-point.";

    $gemini_contents = array();

    // Map conversation history.
    foreach ( $history as $chat ) {
        $role = ( isset( $chat['sender'] ) && $chat['sender'] === 'user' ) ? 'user' : 'model';
        $text = isset( $chat['text'] ) ? sanitize_text_field( $chat['text'] ) : '';
        
        if ( ! empty( $text ) ) {
            $gemini_contents[] = array(
                'role'  => $role,
                'parts' => array(
                    array( 'text' => $text )
                )
            );
        }
    }

    // Append the latest user message.
    $gemini_contents[] = array(
        'role'  => 'user',
        'parts' => array(
            array( 'text' => $user_message )
        )
    );

    // 5. Retrieve model option.
    $model = get_option( 'rq_chatbot_model', 'gemini-3.5-flash' );
    $gemini_url = 'https://generativelanguage.googleapis.com/v1beta/models/' . esc_attr( $model ) . ':generateContent?key=' . esc_attr( $api_key );

    $body_payload = array(
        'contents' => $gemini_contents
    );

    // Add System Instructions if available.
    if ( ! empty( $system_prompt ) ) {
        $body_payload['systemInstruction'] = array(
            'parts' => array(
                array( 'text' => $system_prompt )
            )
        );
    }

    $response = wp_safe_remote_post(
        $gemini_url,
        array(
            'method'    => 'POST',
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
            'body'      => wp_json_encode( $body_payload ),
            'timeout'   => 15,
        )
    );

    // Handle Network/API connection errors.
    if ( is_wp_error( $response ) ) {
        return new WP_REST_Response(
            array(
                'reply' => "Oops! I encountered an error connecting to my brain (" . esc_html( $response->get_error_message() ) . "). Please try again."
            ),
            200
        );
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    $response_body = wp_remote_retrieve_body( $response );
    $data = json_decode( $response_body, true );

    if ( $response_code !== 200 ) {
        $error_msg = $data['error']['message'] ?? 'Unknown Gemini API Error';
        error_log( 'Gemini API Chatbot Error: ' . $error_msg );
        return new WP_REST_Response(
            array(
                'reply' => "I am having trouble answering right now (Error: " . esc_html( $error_msg ) . "). Please try again shortly or contact us at contact@raiseque.com."
            ),
            200
        );
    }

    // 6. Parse reply.
    $reply_text = '';
    if ( isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
        $reply_text = $data['candidates'][0]['content']['parts'][0]['text'];

        // Robust regex to extract captured lead data: [LEAD_CAPTURE: name="x" email="y" phone="z"]
        $pattern = '/\[LEAD_CAPTURE:\s*name\s*=\s*["\']([^"\']+)["\']\s*email\s*=\s*["\']([^"\']+)["\']\s*phone\s*=\s*["\']([^"\']+)["\']\]/i';
        if ( preg_match( $pattern, $reply_text, $matches ) ) {
            $lead_name  = $matches[1];
            $lead_email = $matches[2];
            $lead_phone = $matches[3];

            // Save lead in WordPress CPT
            rq_chatbot_save_lead( $lead_name, $lead_email, $lead_phone );

            // Forward to Google Sheet URL Webhook
            rq_chatbot_send_to_google_sheet( $lead_name, $lead_email, $lead_phone );

            // Strip the technical tag from the displayed reply
            $reply_text = preg_replace( '/\[LEAD_CAPTURE:[^\]]+\]/i', '', $reply_text );
            $reply_text = trim( $reply_text );
        }
    } else {
        $reply_text = "I couldn't process a valid response. Please try rephrasing your question.";
    }

    return new WP_REST_Response(
        array(
            'reply' => $reply_text
        ),
        200
    );
}
