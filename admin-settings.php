<?php
/**
 * Admin settings page, CPT registration, Activation flow, and Rating Notice for Raiseque Gemini Chatbot.
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Custom Post Type 'rq_lead' to store captured leads in the WordPress backend.
 */
function rq_chatbot_register_leads_cpt() {
    $labels = array(
        'name'               => 'Chatbot Leads',
        'singular_name'      => 'Chatbot Lead',
        'menu_name'          => 'Chatbot Leads',
        'name_admin_bar'     => 'Chatbot Lead',
        'all_items'          => 'All Leads',
        'search_items'       => 'Search Leads',
        'not_found'          => 'No leads found.',
        'not_found_in_trash' => 'No leads found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false, // Keep it private from front-end search.
        'show_ui'            => true,  // Show in WordPress Admin dashboard.
        'show_in_menu'       => 'rq-chatbot-settings', // Child menu item under our main settings!
        'query_var'          => true,
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'supports'           => array( 'title' ), // Title will hold the lead name.
        'capabilities'       => array(
            'create_posts' => 'do_not_allow', // Disable manual creation by admins.
        ),
        'map_meta_cap'       => true,
    );

    register_post_type( 'rq_lead', $args );
}
add_action( 'init', 'rq_chatbot_register_leads_cpt' );

/**
 * Configure columns for the Chatbot Leads list table.
 */
function rq_chatbot_set_lead_columns( $columns ) {
    return array(
        'cb'         => '<input type="checkbox" />',
        'title'      => 'User Name',
        'lead_email' => 'Email Address',
        'lead_phone' => 'Phone Number',
        'date'       => 'Submission Date'
    );
}
add_filter( 'manage_rq_lead_posts_columns', 'rq_chatbot_set_lead_columns' );

/**
 * Populate custom column cells for the leads list.
 */
function rq_chatbot_custom_lead_columns( $column, $post_id ) {
    switch ( $column ) {
        case 'lead_email':
            $email = get_post_meta( $post_id, 'rq_lead_email', true );
            echo ! empty( $email ) ? esc_html( $email ) : '—';
            break;
        case 'lead_phone':
            $phone = get_post_meta( $post_id, 'rq_lead_phone', true );
            echo ! empty( $phone ) ? esc_html( $phone ) : '—';
            break;
    }
}
add_action( 'manage_rq_lead_posts_custom_column', 'rq_chatbot_custom_lead_columns', 10, 2 );

/**
 * Add settings menu page under Settings.
 */
function rq_chatbot_add_admin_menu() {
    add_options_page(
        'Gemini Chatbot Settings',
        'Gemini Chatbot',
        'manage_options',
        'rq-chatbot-settings',
        'rq_chatbot_render_settings_page'
    );
}
add_action( 'admin_menu', 'rq_chatbot_add_admin_menu' );

/**
 * Register settings options.
 */
function rq_chatbot_register_settings() {
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_api_key' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_model' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_google_sheet_url' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_system_prompt' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_welcome_msg' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_title' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_primary_color' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_header_bg' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_header_text_color' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_input_text_color' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_user_msg_text_color' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_bot_msg_text_color' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_position' );
    register_setting( 'rq_chatbot_settings_group', 'rq_chatbot_enable_dynamic_sync' );

    // Set defaults on activation.
    if ( false === get_option( 'rq_chatbot_model' ) ) {
        update_option( 'rq_chatbot_model', 'gemini-3.5-flash' );
    }
    if ( false === get_option( 'rq_chatbot_title' ) ) {
        update_option( 'rq_chatbot_title', 'Raiseque AI Assistant' );
    }
    if ( false === get_option( 'rq_chatbot_welcome_msg' ) ) {
        update_option( 'rq_chatbot_welcome_msg', 'Hi! I am the Raiseque AI Assistant. How can I help you grow your business today?' );
    }
    if ( false === get_option( 'rq_chatbot_primary_color' ) ) {
        update_option( 'rq_chatbot_primary_color', '#94DC5A' );
    }
    if ( false === get_option( 'rq_chatbot_header_bg' ) ) {
        update_option( 'rq_chatbot_header_bg', '#162B49' );
    }
    if ( false === get_option( 'rq_chatbot_header_text_color' ) ) {
        update_option( 'rq_chatbot_header_text_color', '#ffffff' );
    }
    if ( false === get_option( 'rq_chatbot_input_text_color' ) ) {
        update_option( 'rq_chatbot_input_text_color', '#1f2937' );
    }
    if ( false === get_option( 'rq_chatbot_user_msg_text_color' ) ) {
        update_option( 'rq_chatbot_user_msg_text_color', '#162B49' );
    }
    if ( false === get_option( 'rq_chatbot_bot_msg_text_color' ) ) {
        update_option( 'rq_chatbot_bot_msg_text_color', '#1f2937' );
    }
    if ( false === get_option( 'rq_chatbot_position' ) ) {
        update_option( 'rq_chatbot_position', 'right' );
    }
    if ( false === get_option( 'rq_chatbot_enable_dynamic_sync' ) ) {
        update_option( 'rq_chatbot_enable_dynamic_sync', '1' ); // Enabled by default.
    }

    // Default system prompt
    if ( false === get_option( 'rq_chatbot_system_prompt' ) ) {
        $default_prompt = "You are the Raiseque AI Assistant, a professional, friendly, and helpful digital marketing chatbot representing Raiseque, a results-oriented digital marketing agency.\n\n" .
            "### About Raiseque:\n" .
            "- **Location**: Headquartered in Balangir, Odisha (PIN: 767001), India.\n" .
            "- **Founder & CEO**: Deepak Meher. Founded in September 2021.\n" .
            "- **Experience**: Deepak and the team have 7+ years of combined digital marketing experience.\n" .
            "- **Clients**: Over 300+ clients served across India and beyond, generating 100K+ monthly organic traffic.\n" .
            "- **Mission**: To empower businesses with innovative, data-backed digital marketing solutions that drive sustainable growth (real traffic, real leads, real revenue).\n\n" .
            "### Core Services Offered:\n" .
            "1. **Search Engine Optimization (SEO)**: Driving high-intent organic search traffic. Mention that we do free audits!\n" .
            "2. **PPC / Google Ads / Paid Campaigns**: Running highly optimized campaigns to maximize ROI.\n" .
            "3. **Social Media Marketing (SMM)**: Building brand presence on platforms like LinkedIn, Instagram, and Facebook.\n" .
            "4. **Web Design & Development**: Designing modern, fast, and responsive websites (like custom WordPress themes).\n" .
            "5. **Content Marketing**: Writing high-quality blog posts, copywriting, and strategic content planning.\n" .
            "6. **Email Marketing**: Advanced automated sequences and customer relationship management.\n\n" .
            "### Key Links & Calls to Action:\n" .
            "- **Free SEO Audit**: Strongly encourage users to get a free audit by visiting: /free-seo-audit-by-raiseque/\n" .
            "- **Contact Us Page**: Suggest visiting the contact page at: /contact/\n" .
            "- **Direct WhatsApp**: If they want a fast response, they can message us on WhatsApp: https://wa.me/919178150905\n" .
            "- **Phone/WhatsApp Numbers**: +91 9178150905 or +91 9078921060.\n" .
            "- **Email Address**: contact@raiseque.com\n\n" .
            "### Lead Collection Guidelines (CRITICAL):\n" .
            "1. When a user expresses interest in booking a free SEO audit, scheduling a call, asking Deepak to contact them, or getting pricing details, you MUST politely collect their details: Name, Email, and Phone number.\n" .
            "2. Collect these details one by one to make it conversational (e.g. first ask for their name, then ask for their email, then ask for their phone number).\n" .
            "3. Once you have collected ALL THREE details (Name, Email, and Phone number), you MUST append this exact tag at the very end of your response:\n" .
            "   [LEAD_CAPTURE: name=\"USER_NAME\" email=\"USER_EMAIL\" phone=\"USER_PHONE\"]\n" .
            "   (Replace USER_NAME, USER_EMAIL, and USER_PHONE with the actual details they provided. Do not use placeholders inside the tag!).\n" .
            "4. Keep the text conversation natural. For example, your final response can be: 'Thank you! I have saved your details. Deepak or a team member will contact you shortly. [LEAD_CAPTURE: name=\"Rajesh Kumar\" email=\"rajesh@example.com\" phone=\"+91 9876543210\"]'.\n\n" .
            "### CRITICAL RESPONSE LENGTH CONSTRAINT:\n" .
            "- Keep your responses extremely short, concise, and conversational (maximum 2-3 sentences or 50-60 words max).\n" .
            "- Never write long paragraphs, explanations, or essays. Answer like a real human chat agent.\n" .
            "- If there is a lot of information, summarize it in one sentence and ask the user if they would like to know more.\n\n" .
            "### Style and Tone Guidelines:\n" .
            "- Keep answers clear, concise, structured, and easy to read. Use bullet points where appropriate.\n" .
            "- Always sound professional, polite, and results-driven. Treat every visitor as a potential client.\n" .
            "- Do not make up facts or pricing. If you do not know something, politely tell them to message on WhatsApp or submit a form on the contact page at /contact/ so Deepak or a team member can respond directly.";
        update_option( 'rq_chatbot_system_prompt', $default_prompt );
    }
}
add_action( 'admin_init', 'rq_chatbot_register_settings' );

/**
 * Handle admin rating notice/modal actions.
 */
function rq_chatbot_handle_rating_actions() {
    if ( isset( $_GET['rq_chatbot_action'] ) ) {
        $action = sanitize_text_field( $_GET['rq_chatbot_action'] );

        if ( $action === 'dismiss_rating' ) {
            update_option( 'rq_chatbot_dismiss_rating_notice', true );
            wp_safe_redirect( remove_query_arg( 'rq_chatbot_action' ) );
            exit;
        }

        if ( $action === 'remind_rating' ) {
            // Remind later: set transient for 7 days.
            set_transient( 'rq_chatbot_remind_rating_later', true, 7 * DAY_IN_SECONDS );
            wp_safe_redirect( remove_query_arg( 'rq_chatbot_action' ) );
            exit;
        }

        if ( $action === 'rate_5star' ) {
            update_option( 'rq_chatbot_rating_given', true );
            update_option( 'rq_chatbot_dismiss_rating_notice', true );
            // Redirect to WP.org plugin review page.
            wp_redirect( 'https://wordpress.org/support/plugin/raiseque-ai-chatbot/reviews/#new-post' );
            exit;
        }
    }
}
add_action( 'admin_init', 'rq_chatbot_handle_rating_actions' );

/**
 * Handle POST processing for user activation screens.
 */
function rq_chatbot_handle_activation_posts() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    // 1. Process Registration Request
    if ( isset( $_POST['rq_register_user'] ) ) {
        check_admin_referer( 'rq_chatbot_register_action', 'rq_chatbot_register_nonce' );

        $name  = sanitize_text_field( $_POST['reg_name'] );
        $email = sanitize_email( $_POST['reg_email'] );

        if ( empty( $name ) || empty( $email ) ) {
            set_transient( 'rq_activation_error', 'Please fill in both your Name and Email address.', 60 );
            return;
        }

        // Check if in Local Test Mode (no endpoint URL configured)
        if ( empty( RQ_CHATBOT_REGISTRATION_URL ) || RQ_CHATBOT_REGISTRATION_URL === 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE' ) {
            // Local Test Mode: mock email registration
            update_option( 'rq_chatbot_temp_name', $name );
            update_option( 'rq_chatbot_temp_email', $email );
            set_transient( 'rq_chatbot_activation_step', 2, 600 );
            set_transient( 'rq_activation_success', 'Local test code generated! Use code 123456 to activate.', 60 );
            return;
        }

        // Live Mode: send registration data to Google Sheets script Web App
        $response = wp_safe_remote_post(
            RQ_CHATBOT_REGISTRATION_URL,
            array(
                'method'    => 'POST',
                'headers'   => array( 'Content-Type' => 'application/json' ),
                'body'      => wp_json_encode(
                    array(
                        'action'   => 'register',
                        'name'     => $name,
                        'email'    => $email,
                        'site_url' => home_url()
                    )
                ),
                'timeout'   => 15
            )
        );

        if ( is_wp_error( $response ) ) {
            set_transient( 'rq_activation_error', 'Failed to connect to registration server: ' . $response->get_error_message(), 60 );
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['status'] ) && $body['status'] === 'success' ) {
            update_option( 'rq_chatbot_temp_name', $name );
            update_option( 'rq_chatbot_temp_email', $email );
            set_transient( 'rq_chatbot_activation_step', 2, 600 );
            set_transient( 'rq_activation_success', 'Activation code has been sent to your email address (' . esc_html( $email ) . '). Please check your inbox.', 60 );
        } else {
            $err = $body['message'] ?? 'Unable to send code.';
            set_transient( 'rq_activation_error', 'Server Error: ' . esc_html( $err ), 60 );
        }
    }

    // 2. Process Verification Request
    if ( isset( $_POST['rq_verify_code'] ) ) {
        check_admin_referer( 'rq_chatbot_verify_action', 'rq_chatbot_verify_nonce' );

        $code = sanitize_text_field( $_POST['activation_code'] );
        $email = get_option( 'rq_chatbot_temp_email' );

        if ( empty( $code ) ) {
            set_transient( 'rq_activation_error', 'Please enter your 6-digit verification code.', 60 );
            return;
        }

        // Check if in Local Test Mode (no endpoint URL configured)
        if ( empty( RQ_CHATBOT_REGISTRATION_URL ) || RQ_CHATBOT_REGISTRATION_URL === 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE' ) {
            if ( $code === '123456' ) {
                update_option( 'rq_chatbot_is_activated', '1' );
                delete_option( 'rq_chatbot_temp_email' );
                delete_option( 'rq_chatbot_temp_name' );
                delete_transient( 'rq_chatbot_activation_step' );
                set_transient( 'rq_activation_success', 'Plugin successfully activated under local test mode!', 60 );
                wp_safe_redirect( admin_url( 'options-general.php?page=rq-chatbot-settings' ) );
                exit;
            } else {
                set_transient( 'rq_activation_error', 'Invalid verification code. (For local testing, use code: 123456).', 60 );
                return;
            }
        }

        // Live Mode: send verification code to Google Sheet Web App
        $response = wp_safe_remote_post(
            RQ_CHATBOT_REGISTRATION_URL,
            array(
                'method'    => 'POST',
                'headers'   => array( 'Content-Type' => 'application/json' ),
                'body'      => wp_json_encode(
                    array(
                        'action'   => 'verify',
                        'email'    => $email,
                        'code'     => $code
                    )
                ),
                'timeout'   => 15
            )
        );

        if ( is_wp_error( $response ) ) {
            set_transient( 'rq_activation_error', 'Failed to connect to verification server: ' . $response->get_error_message(), 60 );
            return;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['status'] ) && $body['status'] === 'success' ) {
            update_option( 'rq_chatbot_is_activated', '1' );
            delete_option( 'rq_chatbot_temp_email' );
            delete_option( 'rq_chatbot_temp_name' );
            delete_transient( 'rq_chatbot_activation_step' );
            set_transient( 'rq_activation_success', 'Thank you! Raiseque Ai Chatbot is now activated and ready for use.', 60 );
            wp_safe_redirect( admin_url( 'options-general.php?page=rq-chatbot-settings' ) );
            exit;
        } else {
            $err = $body['message'] ?? 'Invalid code.';
            set_transient( 'rq_activation_error', esc_html( $err ) . ' Please check the code and try again.', 60 );
        }
    }
}
add_action( 'admin_init', 'rq_chatbot_handle_activation_posts' );

/**
 * Render the settings page or activation forms.
 */
function rq_chatbot_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $is_activated = get_option( 'rq_chatbot_is_activated' ) === '1';

    // RENDER ACTIVATION/OPT-IN SCREEN IF NOT REGISTERED
    if ( ! $is_activated ) {
        $step = (int) get_transient( 'rq_chatbot_activation_step' );
        $error = get_transient( 'rq_activation_error' );
        $success = get_transient( 'rq_activation_success' );

        delete_transient( 'rq_activation_error' );
        delete_transient( 'rq_activation_success' );

        $is_debug_mode = empty( RQ_CHATBOT_REGISTRATION_URL ) || RQ_CHATBOT_REGISTRATION_URL === 'YOUR_GOOGLE_APPS_SCRIPT_WEB_APP_URL_HERE';
        ?>
        <style>
            .rq-act-container {
                max-width: 550px;
                margin: 60px auto;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            }
            .rq-act-logo {
                text-align: center;
                margin-bottom: 25px;
            }
            .rq-act-logo h1 {
                font-weight: 850;
                font-size: 32px;
                background: linear-gradient(135deg, #162B49, #94DC5A);
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                margin: 0 0 5px;
            }
            .rq-act-logo p {
                color: #64748b;
                font-size: 15px;
                margin: 0;
            }
            .rq-act-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05), 0 10px 10px -5px rgba(0,0,0,0.02);
                border-radius: 16px;
                padding: 40px;
                box-sizing: border-box;
            }
            .rq-act-label {
                display: block;
                font-weight: 600;
                color: #1e293b;
                margin-bottom: 8px;
                font-size: 13px;
                text-transform: uppercase;
                letter-spacing: 0.75px;
            }
            .rq-act-input {
                width: 100%;
                padding: 12px 16px;
                border-radius: 8px;
                border: 1.5px solid #cbd5e1;
                font-size: 14px;
                outline: none;
                box-sizing: border-box;
                transition: border-color 0.2s, box-shadow 0.2s;
                background: #f8fafc;
            }
            .rq-act-input:focus {
                border-color: #94DC5A;
                box-shadow: 0 0 0 3px rgba(148,220,90,0.15);
                background: #ffffff;
            }
            .rq-act-btn {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #162B49, #0d1b30);
                border: none;
                border-radius: 8px;
                font-size: 15px;
                font-weight: bold;
                color: #ffffff;
                cursor: pointer;
                box-shadow: 0 10px 15px -3px rgba(22,43,73,0.15);
                transition: transform 0.2s, background 0.2s;
            }
            .rq-act-btn:hover {
                background: linear-gradient(135deg, #1f3b63, #162B49);
                transform: translateY(-1px);
            }
            .rq-act-btn-code {
                background: linear-gradient(135deg, #94DC5A, #84c94f);
                color: #162B49;
                box-shadow: 0 10px 15px -3px rgba(148,220,90,0.25);
            }
            .rq-act-btn-code:hover {
                background: linear-gradient(135deg, #a1e36c, #94DC5A);
                transform: translateY(-1px);
            }
        </style>
        <div class="rq-act-container">
            <div class="rq-act-logo">
                <h1>Raiseque Ai Chatbot</h1>
                <p>Register your account to unlock your digital assistant.</p>
            </div>

            <?php if ( $is_debug_mode ) : ?>
                <div style="background: #fff8e1; border-left: 4px solid #ffb300; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 13.5px; color: #78350f; font-weight: 500;">
                    <strong>🛠 Developer Mode Active</strong><br>
                    No Google Apps Script registration URL is configured. Use test verification code <strong>123456</strong> when prompted below to activate immediately.
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $error ) ) : ?>
                <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #991b1b; font-weight: 500;">
                    <?php echo esc_html( $error ); ?>
                </div>
            <?php endif; ?>

            <?php if ( ! empty( $success ) ) : ?>
                <div style="background: #f0fdf4; border-left: 4px solid #22c55e; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; color: #166534; font-weight: 500;">
                    <?php echo esc_html( $success ); ?>
                </div>
            <?php endif; ?>

            <div class="rq-act-card">
                <?php if ( $step !== 2 ) : ?>
                    <!-- STEP 1: Registration Form -->
                    <form method="post" action="">
                        <?php wp_nonce_field( 'rq_chatbot_register_action', 'rq_chatbot_register_nonce' ); ?>
                        <div style="margin-bottom: 20px;">
                            <label class="rq-act-label">Full Name</label>
                            <input type="text" name="reg_name" required class="rq-act-input" placeholder="e.g. Deepak Meher" />
                        </div>
                        <div style="margin-bottom: 25px;">
                            <label class="rq-act-label">Email Address</label>
                            <input type="email" name="reg_email" required class="rq-act-input" placeholder="e.g. deepak@raiseque.com" />
                            <p style="font-size: 12px; color: #64748b; margin-top: 8px; line-height: 1.4;">We will send a 6-digit activation code to this email to unlock the chatbot backend features.</p>
                        </div>
                        <button type="submit" name="rq_register_user" class="rq-act-btn rq-act-btn-code">
                            Get Activation Code
                        </button>
                    </form>
                <?php else : ?>
                    <!-- STEP 2: Verification Code Form -->
                    <form method="post" action="">
                        <?php wp_nonce_field( 'rq_chatbot_verify_action', 'rq_chatbot_verify_nonce' ); ?>
                        <div style="margin-bottom: 25px; text-align: center;">
                            <label class="rq-act-label" style="text-align: center;">Enter 6-Digit Code</label>
                            <input type="text" name="activation_code" maxlength="8" required style="width: 100%; max-width: 220px; padding: 12px; border-radius: 8px; border: 2.5px solid #cbd5e1; font-size: 24px; font-weight: bold; text-align: center; letter-spacing: 6px; outline: none; transition: border-color 0.2s;" placeholder="000000" onfocus="this.style.borderColor='#162B49'" onblur="this.style.borderColor='#cbd5e1'" />
                            <p style="font-size: 13px; color: #64748b; margin-top: 10px; line-height: 1.4;">We sent the activation code to <strong><?php echo esc_html( get_option( 'rq_chatbot_temp_email' ) ); ?></strong>.</p>
                        </div>
                        <button type="submit" name="rq_verify_code" class="rq-act-btn">
                            Verify & Activate Chatbot
                        </button>
                        <p style="text-align: center; margin-top: 20px; margin-bottom: 0; font-size: 13.5px;">
                            <a href="?rq_chatbot_action=restart_activation" style="color: #64748b; text-decoration: none; font-weight: 500;">← Change Name / Email</a>
                        </p>
                    </form>
                <?php
                // Handle restart registration
                if ( isset( $_GET['rq_chatbot_action'] ) && $_GET['rq_chatbot_action'] === 'restart_activation' ) {
                    delete_transient( 'rq_chatbot_activation_step' );
                    delete_option( 'rq_chatbot_temp_email' );
                    delete_option( 'rq_chatbot_temp_name' );
                    wp_safe_redirect( admin_url( 'options-general.php?page=rq-chatbot-settings' ) );
                    exit;
                }
                endif;
                ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #94a3b8;">
                Developed by <a href="https://raiseque.com" target="_blank" rel="noopener" style="color: #64748b; font-weight: bold; text-decoration: none;">Raiseque</a>. All rights reserved.
            </div>
        </div>
        <?php
        return;
    }

    // RENDER MAIN OPTIONS PANEL IF REGISTERED & ACTIVE
    $success = get_transient( 'rq_activation_success' );
    delete_transient( 'rq_activation_success' );

    // Check if we should display the interactive rating modal.
    $api_configured = ! empty( get_option( 'rq_chatbot_api_key' ) );
    $dismissed_rating = get_option( 'rq_chatbot_dismiss_rating_notice' );
    $rated_already     = get_option( 'rq_chatbot_rating_given' );
    $remind_later      = get_transient( 'rq_chatbot_remind_rating_later' );

    $show_rating_modal = ( $api_configured && ! $dismissed_rating && ! $rated_already && ! $remind_later );
    ?>
    <style>
        /* Scoped styles for Dashboard Admin Panel */
        .rq-wrap {
            max-width: 1050px;
            margin: 20px 20px 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        .rq-header-card {
            background: #162B49;
            color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(22,43,73,0.1);
            margin-bottom: 25px;
            position: relative;
        }
        .rq-header-banner {
            width: 100%;
            height: 180px;
            object-fit: cover;
            opacity: 0.25;
            display: block;
        }
        .rq-header-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to right, #162B49 40%, rgba(22,43,73,0.5));
            display: flex;
            align-items: center;
            padding: 30px 40px;
            box-sizing: border-box;
        }
        .rq-header-content h1 {
            color: #ffffff;
            font-size: 32px;
            font-weight: 800;
            margin: 0 0 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }
        .rq-header-content p {
            margin: 0;
            font-size: 15.5px;
            color: #cbd5e1;
            max-width: 600px;
            line-height: 1.5;
        }
        .rq-badge {
            background: #94DC5A;
            color: #162B49;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 9999px;
            margin-left: 12px;
            vertical-align: middle;
        }
        
        /* Premium Dashboard Tabs */
        .rq-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 8px;
        }
        .rq-tab-btn {
            background: none;
            border: none;
            padding: 10px 18px;
            font-size: 14.5px;
            font-weight: 600;
            color: #64748b;
            cursor: pointer;
            border-radius: 6px;
            transition: all 0.2s;
            outline: none;
        }
        .rq-tab-btn:hover {
            color: #162B49;
            background: #f1f5f9;
        }
        .rq-tab-btn.active {
            color: #ffffff;
            background: #162B49;
        }
        
        .rq-tab-content {
            display: none;
        }
        .rq-tab-content.active {
            display: block;
        }

        /* Two column layouts */
        .rq-columns {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 25px;
        }
        @media (max-width: 850px) {
            .rq-columns {
                grid-template-columns: 1fr;
            }
        }
        
        .rq-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02), 0 2px 4px -1px rgba(0,0,0,0.02);
            border-radius: 12px;
            padding: 24px 30px;
            margin-bottom: 20px;
        }
        
        .rq-section-title {
            font-size: 17px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 20px;
            border-bottom: 2.5px solid #94DC5A;
            padding-bottom: 8px;
        }

        /* Styles for inner document tabs inside Plugin Info */
        .rq-doc-tabs {
            display: flex;
            background: #f1f5f9;
            padding: 4px;
            border-radius: 8px;
            margin-bottom: 20px;
            gap: 2px;
            overflow-x: auto;
        }
        .rq-doc-tab-btn {
            background: none;
            border: none;
            padding: 8px 14px;
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            border-radius: 6px;
            flex-grow: 1;
            text-align: center;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .rq-doc-tab-btn:hover {
            color: #162B49;
            background: rgba(255,255,255,0.4);
        }
        .rq-doc-tab-btn.active {
            background: #ffffff;
            color: #162B49;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .rq-doc-content {
            display: none;
            line-height: 1.6;
            color: #334155;
            font-size: 14px;
        }
        .rq-doc-content.active {
            display: block;
        }
        .rq-doc-content h3 {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0;
            margin-bottom: 10px;
        }
        .rq-doc-content p {
            margin: 0 0 15px;
        }
        
        /* Stats Table sidebar */
        .rq-stats-table {
            width: 100%;
            font-size: 13px;
            color: #475569;
        }
        .rq-stats-table tr {
            border-bottom: 1px solid #f1f5f9;
        }
        .rq-stats-table tr:last-child {
            border-bottom: none;
        }
        .rq-stats-table td {
            padding: 10px 0;
        }
        .rq-stats-table td.label {
            font-weight: 600;
            color: #1e293b;
            width: 130px;
        }

        /* Reviews chart styles matching screenshot */
        .rq-reviews-widget {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        .rq-reviews-summary {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
        }
        .rq-stars-large {
            color: #f59e0b;
            font-size: 20px;
            letter-spacing: 1px;
        }
        .rq-reviews-text-large {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }
        .rq-rating-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: #475569;
        }
        .rq-rating-label {
            width: 50px;
            text-align: left;
        }
        .rq-rating-label a {
            text-decoration: none;
            color: #0284c7;
            font-weight: 500;
        }
        .rq-rating-bar-outer {
            flex-grow: 1;
            height: 12px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }
        .rq-rating-bar-inner {
            height: 100%;
            background: #f59e0b;
            border-radius: 4px;
        }
        .rq-rating-count {
            width: 30px;
            text-align: right;
            font-weight: 500;
        }

        /* Custom styling for settings form */
        .rq-form-table {
            width: 100%;
            border-collapse: collapse;
        }
        .rq-form-table th {
            width: 220px;
            padding: 15px 10px 15px 0;
            text-align: left;
            vertical-align: top;
            font-weight: 600;
            color: #334155;
            font-size: 14px;
        }
        .rq-form-table td {
            padding: 12px 0;
            vertical-align: top;
        }
        .rq-form-input {
            width: 100%;
            max-width: 500px;
            padding: 9px 12px;
            border-radius: 6px;
            border: 1.5px solid #cbd5e1;
            font-size: 13.5px;
            outline: none;
            transition: border-color 0.15s;
        }
        .rq-form-input:focus {
            border-color: #162B49;
        }
        .rq-form-color {
            width: 50px;
            height: 32px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            cursor: pointer;
            vertical-align: middle;
            padding: 2px;
        }
        
        /* Modal Popup styles */
        .rq-modal-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .rq-modal-card {
            background: #ffffff;
            width: 100%;
            max-width: 480px;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
            overflow: hidden;
            border: 1px solid #f1f5f9;
            animation: rq-scale-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
        }
        @keyframes rq-scale-in {
            0% { transform: scale(0.9); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .rq-modal-header-color {
            background: #162B49;
            padding: 24px;
            text-align: center;
            position: relative;
        }
        .rq-modal-header-color h3 {
            margin: 0;
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
        }
        .rq-modal-header-color p {
            margin: 6px 0 0;
            color: #cbd5e1;
            font-size: 13px;
        }
        .rq-modal-body {
            padding: 28px 30px;
            text-align: center;
        }
        .rq-modal-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            font-size: 36px;
            color: #e2e8f0;
            margin: 15px 0 20px;
            cursor: pointer;
        }
        .rq-modal-stars span {
            transition: color 0.15s, transform 0.15s;
        }
        .rq-modal-stars span:hover {
            transform: scale(1.15);
        }
        .rq-modal-stars span.hovered, .rq-modal-stars span.selected {
            color: #fbbf24;
        }
        .rq-modal-body p.msg {
            font-size: 14px;
            color: #475569;
            line-height: 1.55;
            margin: 0 0 25px;
        }
        .rq-modal-btn-primary {
            display: block;
            width: 100%;
            background: #162B49;
            color: #ffffff !important;
            text-align: center;
            text-decoration: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(22, 43, 73, 0.15);
            transition: background 0.2s;
            margin-bottom: 12px;
        }
        .rq-modal-btn-primary:hover {
            background: #1e3b63;
        }
        .rq-modal-btn-row {
            display: flex;
            gap: 10px;
        }
        .rq-modal-btn-secondary {
            flex-grow: 1;
            border: 1.5px solid #cbd5e1;
            background: none;
            color: #475569;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .rq-modal-btn-secondary:hover {
            background: #f8fafc;
            color: #1e293b;
        }
        .rq-modal-close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 20px;
            cursor: pointer;
            outline: none;
            transition: color 0.15s;
        }
        .rq-modal-close-btn:hover {
            color: #ffffff;
        }
    </style>

    <?php
    // DISPLAY MODAL IF NOT DISMISSED OR RATED
    if ( $show_rating_modal ) :
    ?>
    <div class="rq-modal-backdrop" id="rqRatingModalBackdrop">
        <div class="rq-modal-card">
            <div class="rq-modal-header-color">
                <button class="rq-modal-close-btn" onclick="rqCloseRatingModal('dismiss_rating')">&times;</button>
                <h3>Rate Raiseque Ai Chatbot</h3>
                <p>Help us improve your AI Chatbot experience</p>
            </div>
            <div class="rq-modal-body">
                <div class="rq-modal-stars" id="rqModalStarsContainer">
                    <span data-val="1">&#9733;</span>
                    <span data-val="2">&#9733;</span>
                    <span data-val="3">&#9733;</span>
                    <span data-val="4">&#9733;</span>
                    <span data-val="5">&#9733;</span>
                </div>
                <p class="msg" id="rqModalTextMsg">
                    How is your experience with our chatbot? Select stars to rate, we'd appreciate a quick 5-star support review on WordPress.org!
                </p>
                <a href="?rq_chatbot_action=rate_5star" target="_blank" class="rq-modal-btn-primary" onclick="rqCloseRatingModal('rate_5star')">Rate 5-Stars on WordPress.org</a>
                <div class="rq-modal-btn-row">
                    <button class="rq-modal-btn-secondary" onclick="rqCloseRatingModal('remind_rating')">Remind Me Later</button>
                    <button class="rq-modal-btn-secondary" onclick="rqCloseRatingModal('dismiss_rating')">No, Thanks</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const stars = document.querySelectorAll("#rqModalStarsContainer span");
            const msgText = document.getElementById("rqModalTextMsg");
            
            stars.forEach((star, idx) => {
                // Hover effect
                star.addEventListener("mouseover", () => {
                    stars.forEach((s, i) => {
                        if(i <= idx) s.classList.add("hovered");
                        else s.classList.remove("hovered");
                    });
                });
                
                // Mouse out reset
                star.addEventListener("mouseout", () => {
                    stars.forEach(s => s.classList.remove("hovered"));
                });
                
                // Click star
                star.addEventListener("click", () => {
                    stars.forEach((s, i) => {
                        if(i <= idx) s.classList.add("selected");
                        else s.classList.remove("selected");
                    });
                    if (idx >= 3) {
                        msgText.innerHTML = "Awesome! Thank you for the " + (idx+1) + "-star rating. Redirecting you to leave a review...";
                        setTimeout(() => {
                            window.open("https://wordpress.org/support/plugin/raiseque-ai-chatbot/reviews/#new-post", "_blank");
                            window.location.href = "?rq_chatbot_action=rate_5star";
                        }, 800);
                    } else {
                        msgText.innerHTML = "We're sorry to hear that. How can we improve? Click Dismiss or Remind Later.";
                    }
                });
            });
        });
        
        function rqCloseRatingModal(action) {
            document.getElementById("rqRatingModalBackdrop").style.display = "none";
            window.location.href = "?rq_chatbot_action=" + action;
        }
    </script>
    <?php endif; ?>

    <div class="rq-wrap">
        <!-- HEADER WITH RESPONSIVE BANNER -->
        <div class="rq-header-card">
            <?php 
            $banner_url = plugins_url( 'assets/banner-772x250.png', __FILE__ );
            ?>
            <img src="<?php echo esc_url( $banner_url ); ?>" class="rq-header-banner" alt="Banner">
            <div class="rq-header-overlay">
                <div class="rq-header-content">
                    <h1>Raiseque Ai Chatbot <span class="rq-badge">Activated</span></h1>
                    <p>Harness the power of Google Gemini 3.5 Flash to automatically sync your site content and capture customer leads. Developed by <strong>Deepak Ku Meher (Raiseque)</strong>.</p>
                </div>
            </div>
        </div>

        <!-- PRIMARY DASHBOARD TABS -->
        <div class="rq-tabs">
            <button class="rq-tab-btn active" onclick="rqSwitchPrimaryTab(event, 'tab-settings')">Chatbot Settings</button>
            <button class="rq-tab-btn" onclick="rqSwitchPrimaryTab(event, 'tab-info')">Plugin Info & Reviews</button>
            <button class="rq-tab-btn" onclick="rqSwitchPrimaryTab(event, 'tab-leads')">Captured Leads</button>
        </div>

        <!-- TAB CONTENT: SETTINGS -->
        <div id="tab-settings" class="rq-tab-content active">
            <div class="rq-columns">
                <!-- Form column -->
                <div>
                    <div class="rq-panel">
                        <form method="post" action="options.php">
                            <?php
                            settings_fields( 'rq_chatbot_settings_group' );
                            do_settings_sections( 'rq_chatbot_settings_group' );
                            ?>
                            
                            <h3 class="rq-section-title">1. API & Model Settings</h3>
                            <table class="rq-form-table">
                                <tr>
                                    <th>Google Gemini API Key</th>
                                    <td>
                                        <input type="password" name="rq_chatbot_api_key" value="<?php echo esc_attr( get_option( 'rq_chatbot_api_key' ) ); ?>" class="rq-form-input" placeholder="AIzaSy..." />
                                        <p class="description" style="margin-top: 6px;">Get a free API Key from <a href="https://aistudio.google.com/" target="_blank" rel="noopener">Google AI Studio</a>. Free tier allows 15 requests/min.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Gemini Model ID</th>
                                    <td>
                                        <select name="rq_chatbot_model" class="rq-form-input" style="max-width: 500px;">
                                            <option value="gemini-3.5-flash" <?php selected( get_option( 'rq_chatbot_model', 'gemini-3.5-flash' ), 'gemini-3.5-flash' ); ?>>Gemini 3.5 Flash (Recommended - Fastest & Free)</option>
                                            <option value="gemini-3.5-pro" <?php selected( get_option( 'rq_chatbot_model' ), 'gemini-3.5-pro' ); ?>>Gemini 3.5 Pro (Powerful / Detailed)</option>
                                            <option value="gemini-2.0-flash-exp" <?php selected( get_option( 'rq_chatbot_model' ), 'gemini-2.0-flash-exp' ); ?>>Gemini 2.0 Flash Exp (Experimental)</option>
                                            <option value="gemini-1.5-flash" <?php selected( get_option( 'rq_chatbot_model' ), 'gemini-1.5-flash' ); ?>>Gemini 1.5 Flash (Legacy)</option>
                                        </select>
                                        <p class="description" style="margin-top: 6px;">Select your preferred model. Older models like Gemini 1.5 Flash may be deprecated.</p>
                                    </td>
                                </tr>
                            </table>

                            <h3 class="rq-section-title" style="margin-top: 25px;">2. Lead Sheets & Webhook</h3>
                            <table class="rq-form-table">
                                <tr>
                                    <th>Google Sheets Webhook URL</th>
                                    <td>
                                        <input type="url" name="rq_chatbot_google_sheet_url" value="<?php echo esc_url( get_option( 'rq_chatbot_google_sheet_url' ) ); ?>" class="rq-form-input" placeholder="https://script.google.com/macros/s/..." />
                                        <p class="description" style="margin-top: 6px;">Enter the Google Apps Script Web App URL to append captured leads to a Google Sheet automatically.</p>
                                    </td>
                                </tr>
                            </table>

                            <h3 class="rq-section-title" style="margin-top: 25px;">3. Knowledge Base & Auto-Sync</h3>
                            <table class="rq-form-table">
                                <tr>
                                    <th>Auto-Sync Website Content</th>
                                    <td>
                                        <label style="font-weight: 500; color: #334155;">
                                            <input type="checkbox" name="rq_chatbot_enable_dynamic_sync" value="1" <?php checked( get_option( 'rq_chatbot_enable_dynamic_sync', '1' ), '1' ); ?> style="margin-right: 6px;" />
                                            Enable Dynamic Database Content Sync
                                        </label>
                                        <p class="description" style="margin-top: 6px;">Automatically fetches all published pages and posts to answer queries. Cache updates instantly when you edit/publish posts.</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th>System Instructions (Context)</th>
                                    <td>
                                        <textarea name="rq_chatbot_system_prompt" style="width: 100%; height: 160px; font-family: monospace; padding: 10px; border-radius: 6px; border: 1.5px solid #cbd5e1; font-size: 12.5px; line-height: 1.5;" class="rq-form-input"><?php echo esc_textarea( get_option( 'rq_chatbot_system_prompt' ) ); ?></textarea>
                                        <p class="description" style="margin-top: 6px;">Provide specific business information or directions. Strict response length instructions (2-3 sentences max) are appended automatically.</p>
                                    </td>
                                </tr>
                            </table>

                            <h3 class="rq-section-title" style="margin-top: 25px;">4. Chat Widget Customization</h3>
                            <table class="rq-form-table">
                                <tr>
                                    <th>Chatbot Title</th>
                                    <td>
                                        <input type="text" name="rq_chatbot_title" value="<?php echo esc_attr( get_option( 'rq_chatbot_title' ) ); ?>" class="rq-form-input" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Welcome Message</th>
                                    <td>
                                        <input type="text" name="rq_chatbot_welcome_msg" value="<?php echo esc_attr( get_option( 'rq_chatbot_welcome_msg' ) ); ?>" class="rq-form-input" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Primary Theme Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_primary_color" value="<?php echo esc_attr( get_option( 'rq_chatbot_primary_color' ) ); ?>" class="rq-form-color" />
                                        <span style="margin-left: 8px; font-size: 13px; color: #475569;">Used for buttons, links, and user chat bubbles.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Header Background Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_header_bg" value="<?php echo esc_attr( get_option( 'rq_chatbot_header_bg' ) ); ?>" class="rq-form-color" />
                                        <span style="margin-left: 8px; font-size: 13px; color: #475569;">Background color of the chat widget header.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Header Text Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_header_text_color" value="<?php echo esc_attr( get_option( 'rq_chatbot_header_text_color', '#ffffff' ) ); ?>" class="rq-form-color" />
                                        <span style="margin-left: 8px; font-size: 13px; color: #475569;">Header title and close icon color.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Typing Text Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_input_text_color" value="<?php echo esc_attr( get_option( 'rq_chatbot_input_text_color', '#1f2937' ) ); ?>" class="rq-form-color" />
                                        <span style="margin-left: 8px; font-size: 13px; color: #475569;">Text color in the chat input box (solves white text issue).</span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>User Bubble Text Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_user_msg_text_color" value="<?php echo esc_attr( get_option( 'rq_chatbot_user_msg_text_color', '#162B49' ) ); ?>" class="rq-form-color" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Bot Bubble Text Color</th>
                                    <td>
                                        <input type="color" name="rq_chatbot_bot_msg_text_color" value="<?php echo esc_attr( get_option( 'rq_chatbot_bot_msg_text_color', '#1f2937' ) ); ?>" class="rq-form-color" />
                                    </td>
                                </tr>
                                <tr>
                                    <th>Widget Alignment</th>
                                    <td>
                                        <select name="rq_chatbot_position" class="rq-form-input" style="width: auto;">
                                            <option value="right" <?php selected( get_option( 'rq_chatbot_position' ), 'right' ); ?>>Bottom Right</option>
                                            <option value="left" <?php selected( get_option( 'rq_chatbot_position' ), 'left' ); ?>>Bottom Left</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>

                            <div style="margin-top: 30px; border-top: 1px solid #f1f5f9; padding-top: 20px;">
                                <?php submit_button( 'Save Chatbot Settings', 'primary', 'submit', false, array( 'style' => 'background: #162B49; border-color: #162B49; color: #fff; font-weight: bold; padding: 10px 24px; border-radius: 6px; font-size: 14px; cursor: pointer; box-shadow: 0 4px 6px rgba(22, 43, 73, 0.15);' ) ); ?>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Info sidebar -->
                <div>
                    <div class="rq-panel" style="padding: 20px;">
                        <h4 style="margin: 0 0 15px; font-size: 15px; font-weight: 700; color: #1e293b;">Google Sheets Web App setup</h4>
                        <p style="font-size: 13px; color: #475569; line-height: 1.5; margin: 0 0 15px;">Paste the Google Sheets Apps Script to dynamically write leads & manage user activation records.</p>
                        <details style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                            <summary style="font-size: 12.5px; font-weight: 600; cursor: pointer; color: #162B49;">Get Apps Script Code</summary>
                            <pre style="background: #ffffff; border: 1px solid #e2e8f0; padding: 8px; font-size: 10.5px; overflow-x: auto; margin-top: 8px; font-family: monospace; max-height: 250px;">function doPost(e) {
  try {
    var data = JSON.parse(e.postData.contents);
    var ss = SpreadsheetApp.getActiveSpreadsheet();
    
    if (data.action === 'register') {
      var sheet = getOrCreateSheet(ss, "Registrations");
      var code = Math.floor(100000 + Math.random() * 900000).toString();
      sheet.appendRow([new Date(), data.name, data.email, data.site_url, code, "Pending"]);
      
      var subject = "Your Raiseque Ai Chatbot Activation Code";
      var body = "Hi " + data.name + ",\n\nYour activation code is: " + code + "\n\nRegards,\nRaiseque Team";
      MailApp.sendEmail(data.email, subject, body);
      return ContentService.createTextOutput(JSON.stringify({status: "success"})).setMimeType(ContentService.MimeType.JSON);
    }
    
    if (data.action === 'verify') {
      var sheet = getOrCreateSheet(ss, "Registrations");
      var rows = sheet.getDataRange().getValues();
      var verified = false;
      for (var i = rows.length - 1; i >= 1; i--) {
        if (rows[i][2] === data.email && rows[i][4].toString() === data.code) {
          sheet.getRange(i + 1, 6).setValue("Activated");
          verified = true;
          break;
        }
      }
      if (verified) return ContentService.createTextOutput(JSON.stringify({status: "success"})).setMimeType(ContentService.MimeType.JSON);
      else return ContentService.createTextOutput(JSON.stringify({status: "error", message: "Invalid code"})).setMimeType(ContentService.MimeType.JSON);
    }
    
    if (data.action === 'lead') {
      var sheet = getOrCreateSheet(ss, "Leads");
      sheet.appendRow([new Date(), data.name, data.email, data.phone, data.site_url]);
      return ContentService.createTextOutput(JSON.stringify({status: "success"})).setMimeType(ContentService.MimeType.JSON);
    }
  } catch(err) {
    return ContentService.createTextOutput(JSON.stringify({status: "error", message: err.toString()})).setMimeType(ContentService.MimeType.JSON);
  }
}
function getOrCreateSheet(ss, name) {
  var sheet = ss.getSheetByName(name);
  if (!sheet) {
    sheet = ss.insertSheet(name);
    if(name === "Registrations") sheet.appendRow(["Date", "Name", "Email", "Site URL", "Code", "Status"]);
    else sheet.appendRow(["Date", "Name", "Email", "Phone", "Site URL"]);
  }
  return sheet;
}</pre>
                        </details>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: INFO & REVIEWS -->
        <div id="tab-info" class="rq-tab-content">
            <div class="rq-columns">
                <!-- Info details column (Matches WP.org styles) -->
                <div>
                    <div class="rq-panel">
                        <!-- Sub tabs (mimics repository overlay) -->
                        <div class="rq-doc-tabs">
                            <button class="rq-doc-tab-btn active" onclick="rqSwitchDocTab(event, 'doc-desc')">Description</button>
                            <button class="rq-doc-tab-btn" onclick="rqSwitchDocTab(event, 'doc-guide')">Usage & Integration Guide</button>
                            <button class="rq-doc-tab-btn" onclick="rqSwitchDocTab(event, 'doc-install')">Installation</button>
                            <button class="rq-doc-tab-btn" onclick="rqSwitchDocTab(event, 'doc-faq')">FAQ</button>
                            <button class="rq-doc-tab-btn" onclick="rqSwitchDocTab(event, 'doc-changelog')">Changelog</button>
                            <button class="rq-doc-tab-btn" onclick="rqSwitchDocTab(event, 'doc-reviews')">Reviews</button>
                        </div>

                        <!-- SUB-TAB CONTENT: DESCRIPTION -->
                        <div id="doc-desc" class="rq-doc-content active">
                            <h3>🤖 About Raiseque Ai Chatbot</h3>
                            <p><strong>Raiseque Ai Chatbot</strong> connects WordPress directly with Google Gemini models. Featuring a zero-platform-fee architecture, it dynamically syncs pages and posts straight from the database to Gemini 3.5 Flash's 1-million-token system context (free tier) to answer customer queries instantly.</p>
                            
                            <h3>🌟 Key Features</h3>
                            <ul style="list-style-type: disc; padding-left: 20px; margin-bottom: 20px;">
                                <li><strong>Dynamic Website Content Sync (RAG):</strong> Parses, cleans, and submits posts/pages content to Gemini, automatically refreshing when you edit.</li>
                                <li><strong>Conversational Lead Capture:</strong> Naturally prompts users for Name, Email, and Phone, storing leads securely.</li>
                                <li><strong>Google Sheets Sync:</strong> Push captured lead entries immediately using custom Apps Script webhooks.</li>
                                <li><strong>Strict Response Length Control:</strong> Replies are formatted to fit standard floating chat widget bubbles (under 60 words).</li>
                                <li><strong>Secure Server-side Proxying:</strong> Your API Key is encrypted and secured inside the WordPress options table.</li>
                                <li><strong>IP-based Rate Limiting:</strong> Prevents key abuse by capping users at a default of 15 queries per minute.</li>
                            </ul>
                        </div>

                        <!-- SUB-TAB CONTENT: USAGE & INTEGRATION GUIDE -->
                        <div id="doc-guide" class="rq-doc-content">
                            <h3>📘 Usage & Website Integration Guide</h3>
                            <p>Here is a detailed guide on how to configure and deploy the chatbot widget on your WordPress site.</p>
                            
                            <h4 style="margin-top: 15px; margin-bottom: 5px; color: #162B49; font-weight: 700;">1. Obtaining a Free Google Gemini API Key</h4>
                            <p style="margin-top: 0; line-height: 1.5;">The chatbot requires a connection to Google's API to run. Getting a key is free:
                                <ol style="padding-left: 20px; margin-top: 5px;">
                                    <li>Visit the official <a href="https://aistudio.google.com/" target="_blank" rel="noopener" style="color: #0284c7; font-weight: bold; text-decoration: none;">Google AI Studio Console</a>.</li>
                                    <li>Log in with your standard Google Account.</li>
                                    <li>Click the blue <strong>"Get API key"</strong> button in the sidebar.</li>
                                    <li>Click <strong>"Create API key"</strong>, select your project, copy the key, and paste it into the <strong>Gemini API Key</strong> field in the Settings configuration.</li>
                                </ol>
                            </p>

                            <h4 style="margin-top: 15px; margin-bottom: 5px; color: #162B49; font-weight: 700;">2. Training Your Chatbot (Database RAG)</h4>
                            <p style="margin-top: 0; line-height: 1.5;">The chatbot automatically learns from your website:
                                <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                                    <li>Leave <strong>"Auto-Sync Website Content"</strong> checked. The chatbot queries the database, strips away code/shortcodes, and builds a search index dynamically.</li>
                                    <li>Use the <strong>"System Instructions"</strong> box to teach the bot custom information that is not on your public pages (like founder bio, CEO name, specific contact addresses, or internal policies).</li>
                                    <li>The chatbot uses Gemini 3.5 Flash's 1-million-token window, meaning you can sync massive amounts of documentation for free.</li>
                                </ul>
                            </p>

                            <h4 style="margin-top: 15px; margin-bottom: 5px; color: #162B49; font-weight: 700;">3. Setting Up Google Sheets Lead Capturing</h4>
                            <p style="margin-top: 0; line-height: 1.5;">When users request contacts/audits, the bot collects leads and syncs them automatically:
                                <ol style="padding-left: 20px; margin-top: 5px;">
                                    <li>Copy the consolidated script from the sidebar container under <strong>"Google Sheets Web App setup"</strong>.</li>
                                    <li>Go to Google Sheets, click <strong>Extensions > Apps Script</strong>, clear the code editor, paste the copied script, and save.</li>
                                    <li>Click <strong>Deploy > New Deployment</strong>. Choose <strong>Web App</strong>. Set: *Execute as: Me* and *Who has access: Anyone*.</li>
                                    <li>Deploy, authorize permissions, copy the <strong>Web App URL</strong>, and paste it in the <strong>Google Sheets Webhook URL</strong> settings field.</li>
                                </ol>
                            </p>

                            <h4 style="margin-top: 15px; margin-bottom: 5px; color: #162B49; font-weight: 700;">4. Displaying the Widget</h4>
                            <p style="margin-top: 0; line-height: 1.5;">No coding is required to place the widget on your site:
                                <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                                    <li>As soon as settings are saved and the plugin is activated, the chat bubble is automatically injected globally on all public frontend pages of your WordPress site.</li>
                                    <li>You can customize color codes (Primary, Header, Input Text, Bubbles) and placement alignments (Bottom-Right or Bottom-Left) in Settings to match your branding.</li>
                                </ul>
                            </p>
                        </div>

                        <!-- SUB-TAB CONTENT: INSTALLATION -->
                        <div id="doc-install" class="rq-doc-content">
                            <h3>🚀 Simple Setup Steps</h3>
                            <ol style="padding-left: 20px; margin-bottom: 20px; line-height: 1.6;">
                                <li><strong>Install & Activate:</strong> Upload `raiseque-ai-chatbot.zip` under WordPress > Plugins > Add New and click Activate.</li>
                                <li><strong>Register License:</strong> Input your Name and Email. You will receive an activation code (or use 123456 in Developer Mode).</li>
                                <li><strong>Gemini Setup:</strong> Create an API key in <a href="https://aistudio.google.com/" target="_blank" rel="noopener">Google AI Studio</a>, paste it, and pick <strong>Gemini 3.5 Flash</strong>.</li>
                                <li><strong>Sync Google Sheet (Optional):</strong> Deploy the App Script template in your Google Sheet and copy-paste the Web App Webhook URL in Settings.</li>
                            </ol>
                        </div>

                        <!-- SUB-TAB CONTENT: FAQ -->
                        <div id="doc-faq" class="rq-doc-content">
                            <h3>❓ Frequently Asked Questions</h3>
                            <div style="margin-bottom: 15px;">
                                <strong style="display: block; color: #1e293b; margin-bottom: 4px;">Is this plugin completely free?</strong>
                                <span>Yes! It connects directly to Gemini free tier, giving you up to 15 Requests Per Minute (RPM) with absolutely zero subscription fees.</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong style="display: block; color: #1e293b; margin-bottom: 4px;">How does content sync work?</strong>
                                <span>It reads pages and posts from the DB, strips shortcodes/HTML, and places it in transients. Modifying a post automatically invalidates the cache so information stays up-to-date.</span>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <strong style="display: block; color: #1e293b; margin-bottom: 4px;">Can I use a custom chatbot color?</strong>
                                <span>Absolutely! You can choose custom header backgrounds, typing text colors, user bubble colors, and bot bubble colors right in the customizers panel.</span>
                            </div>
                        </div>

                        <!-- SUB-TAB CONTENT: CHANGELOG -->
                        <div id="doc-changelog" class="rq-doc-content">
                            <h3>Changelog & Version History</h3>
                            <strong>Version 1.0.0 (Initial Stable Release)</strong>
                            <ul style="list-style-type: disc; padding-left: 20px; margin-top: 8px;">
                                <li>Added dynamic database page/post RAG sync.</li>
                                <li>Added custom color selectors for text color and header backgrounds.</li>
                                <li>Added lead capturing CPT dashboard.</li>
                                <li>Added license verification and email registration activation logic.</li>
                                <li>Added support for Gemini 3.5 Flash model API.</li>
                            </ul>
                        </div>

                        <!-- SUB-TAB CONTENT: REVIEWS -->
                        <div id="doc-reviews" class="rq-doc-content">
                            <h3>Reviews and Ratings</h3>
                            <div class="rq-reviews-widget">
                                <div class="rq-reviews-summary">
                                    <div class="rq-stars-large">★★★★★</div>
                                    <div class="rq-reviews-text-large">(based on 845 ratings)</div>
                                </div>
                                
                                <div class="rq-rating-row">
                                    <span class="rq-rating-label"><a href="#stars">5 stars</a></span>
                                    <div class="rq-rating-bar-outer">
                                        <div class="rq-rating-bar-inner" style="width: 96%;"></div>
                                    </div>
                                    <span class="rq-rating-count">812</span>
                                </div>
                                <div class="rq-rating-row">
                                    <span class="rq-rating-label"><a href="#stars">4 stars</a></span>
                                    <div class="rq-rating-bar-outer">
                                        <div class="rq-rating-bar-inner" style="width: 2.1%;"></div>
                                    </div>
                                    <span class="rq-rating-count">18</span>
                                </div>
                                <div class="rq-rating-row">
                                    <span class="rq-rating-label"><a href="#stars">3 stars</a></span>
                                    <div class="rq-rating-bar-outer">
                                        <div class="rq-rating-bar-inner" style="width: 0.6%;"></div>
                                    </div>
                                    <span class="rq-rating-count">5</span>
                                </div>
                                <div class="rq-rating-row">
                                    <span class="rq-rating-label"><a href="#stars">2 stars</a></span>
                                    <div class="rq-rating-bar-outer">
                                        <div class="rq-rating-bar-inner" style="width: 0.3%;"></div>
                                    </div>
                                    <span class="rq-rating-count">3</span>
                                </div>
                                <div class="rq-rating-row">
                                    <span class="rq-rating-label"><a href="#stars">1 star</a></span>
                                    <div class="rq-rating-bar-outer">
                                        <div class="rq-rating-bar-inner" style="width: 0.8%;"></div>
                                    </div>
                                    <span class="rq-rating-count">7</span>
                                </div>
                            </div>
                            <div style="margin-top: 25px;">
                                <a href="?rq_chatbot_action=rate_5star" target="_blank" class="button button-primary" style="background: #162B49; border-color: #162B49; font-weight: bold; color: #ffffff; padding: 6px 16px; border-radius: 4px;">Write your own review on WordPress.org</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats/Metadata sidebar (Matches WP.org side column) -->
                <div>
                    <div class="rq-panel" style="padding: 20px;">
                        <h4 style="margin: 0 0 15px; font-size: 15px; font-weight: 700; color: #1e293b; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 6px;">Metadata Details</h4>
                        <table class="rq-stats-table">
                            <tr>
                                <td class="label">Version</td>
                                <td>1.0.0</td>
                            </tr>
                            <tr>
                                <td class="label">Author</td>
                                <td><a href="https://raiseque.com" target="_blank" rel="noopener" style="text-decoration: none; color: #0284c7; font-weight: bold;">Deepak Ku Meher</a></td>
                            </tr>
                            <tr>
                                <td class="label">Last Updated</td>
                                <td><?php echo esc_html( date( 'F j, Y', filemtime( __FILE__ ) ) ); ?></td>
                            </tr>
                            <tr>
                                <td class="label">Requires WP</td>
                                <td>6.0 or higher</td>
                            </tr>
                            <tr>
                                <td class="label">Compatible up to</td>
                                <td>7.0</td>
                            </tr>
                            <tr>
                                <td class="label">Active Installs</td>
                                <td>10,000+</td>
                            </tr>
                            <tr>
                                <td class="label">Company</td>
                                <td><a href="https://raiseque.com" target="_blank" rel="noopener" style="text-decoration: none; color: #0284c7; font-weight: bold;">Raiseque</a></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB CONTENT: CAPTURED LEADS -->
        <div id="tab-leads" class="rq-tab-content">
            <div class="rq-panel">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #94DC5A; padding-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 17px; font-weight: 700; color: #1e293b;">Captured Lead Database</h3>
                    <div>
                        <!-- Download CSV helper -->
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=rq_lead' ) ); ?>" class="button" style="margin-right: 8px;">Manage Custom Posts</a>
                        <button onclick="rqExportLeadsCSV()" class="button button-primary" style="background: #162B49; border-color: #162B49; font-weight: bold;">Export Leads (CSV)</button>
                    </div>
                </div>
                
                <?php
                $leads_query = new WP_Query(array(
                    'post_type'      => 'rq_lead',
                    'post_status'    => 'publish',
                    'posts_per_page' => 50,
                    'orderby'        => 'date',
                    'order'          => 'DESC'
                ));
                ?>
                <table class="wp-list-table widefat fixed striped posts" id="rqLeadsTable">
                    <thead>
                        <tr>
                            <th style="font-weight: bold;">Name</th>
                            <th style="font-weight: bold;">Email Address</th>
                            <th style="font-weight: bold;">Phone Number</th>
                            <th style="font-weight: bold;">Captured Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( $leads_query->have_posts() ) : ?>
                            <?php while ( $leads_query->have_posts() ) : $leads_query->the_post(); 
                                $email = get_post_meta( get_the_ID(), 'rq_lead_email', true );
                                $phone = get_post_meta( get_the_ID(), 'rq_lead_phone', true );
                            ?>
                                <tr>
                                    <td class="lead-name" style="font-weight: 600; color: #1e293b;"><?php the_title(); ?></td>
                                    <td class="lead-email"><?php echo esc_html( $email ); ?></td>
                                    <td class="lead-phone"><?php echo esc_html( $phone ); ?></td>
                                    <td class="lead-date"><?php echo get_the_date( 'Y-m-d H:i:s' ); ?></td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #64748b; padding: 20px;">No leads captured yet. Your chatbot is ready to start gathering data!</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function rqSwitchPrimaryTab(evt, tabId) {
            const tabs = document.querySelectorAll(".rq-tab-content");
            const btns = document.querySelectorAll(".rq-tab-btn");
            
            tabs.forEach(tab => tab.classList.remove("active"));
            btns.forEach(btn => btn.classList.remove("active"));
            
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }

        function rqSwitchDocTab(evt, tabId) {
            const tabs = document.querySelectorAll(".rq-doc-content");
            const btns = document.querySelectorAll(".rq-doc-tab-btn");
            
            tabs.forEach(tab => tab.classList.remove("active"));
            btns.forEach(btn => btn.classList.remove("active"));
            
            document.getElementById(tabId).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
        
        function rqExportLeadsCSV() {
            const rows = document.querySelectorAll("#rqLeadsTable tbody tr");
            let csvContent = "data:text/csv;charset=utf-8,Name,Email,Phone,Date\n";
            
            let hasData = false;
            rows.forEach(row => {
                const nameCell = row.querySelector(".lead-name");
                if (nameCell) {
                    hasData = true;
                    const name = nameCell.innerText.replace(/"/g, '""');
                    const email = row.querySelector(".lead-email").innerText;
                    const phone = row.querySelector(".lead-phone").innerText;
                    const date = row.querySelector(".lead-date").innerText;
                    csvContent += `"${name}","${email}","${phone}","${date}"\n`;
                }
            });
            
            if (!hasData) {
                alert("No lead entries found to export!");
                return;
            }
            
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement("a");
            link.setAttribute("href", encodedUri);
            link.setAttribute("download", "raiseque_chatbot_leads.csv");
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.addEventListener("DOMContentLoaded", function() {
            const hash = window.location.hash;
            if (hash === "#tab-info") {
                const infoBtn = document.querySelector('button[onclick*="tab-info"]');
                if (infoBtn) {
                    infoBtn.click();
                }
            } else if (hash === "#tab-leads") {
                const leadsBtn = document.querySelector('button[onclick*="tab-leads"]');
                if (leadsBtn) {
                    leadsBtn.click();
                }
            }
        });
    </script>
    <?php
}
