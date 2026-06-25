=== Raiseque Ai Chatbot ===
Contributors: deepak-ku-meher
Tags: ai, chatbot, gemini, google gemini, leads, lead generation, auto sync, rag, chat assistant
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

An automated, zero platform lock-in AI chatbot for WordPress. Dynamically syncs database content, captures user leads, and logs them to Google Sheets.

== Description ==

Raiseque Ai Chatbot is a premium, secure, and completely free AI assistant for your WordPress website. Connected directly to the Google Gemini API (supporting Gemini 3.5 Flash), it dynamically queries and syncs your published posts and pages to answer any user queries in real-time.

= Conversational Lead Capture =
The chatbot detects user intent (like audit requests or contact) and conversationally collects their Name, Email, and Phone number. 

= WordPress Leads Dashboard =
Allows you to view, search, and manage captured leads directly inside the WordPress Admin Dashboard.

= Google Sheets Integration =
Syncs captured leads with your Google Sheet using a simple Apps Script webhook in real-time.

== Features ==

* **Auto-Sync Website Content:** Directly queries and cleans your website pages and posts to form the chatbot's knowledge base.
* **Conversational Lead Capture:** Collects user Name, Email, and Phone number naturally.
* **Google Sheets Sync:** Appends leads to a Google Sheet automatically.
* **WordPress Dashboard:** View all captured leads directly in WP Admin.
* **Granular Customization:** Control headers, welcome messages, widget position, and text/background colors.
* **Secure API Call Proxying:** Keeps your Google Gemini API key hidden and secure.
* **IP-based Rate Limiting:** Restricts spam queries (default 15/min per IP) to protect free tier quotas.

== Installation ==

1. Upload the `raiseque-gemini-chatbot` folder to the `/wp-content/plugins/` directory, or upload the ZIP file via WordPress Admin > Plugins > Add New.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Complete the account activation screen (enters your name and email to receive your free activation code).
4. Configure your Gemini API key and settings in **Settings** > **Gemini Chatbot**.

== Frequently Asked Questions ==

= Is this plugin completely free? =
Yes, the plugin is 100% free. By using Google Gemini's free tier, you can handle thousands of queries per month without any platform fees.

= How does the website content sync work? =
The plugin queries the WordPress database for published posts and pages, strips HTML and shortcodes, caches it for fast replies, and automatically clears the cache when you update or delete pages.

= How do I link my Google Sheet? =
Paste the Apps Script code (provided in settings) into your Google Sheet's Apps Script editor, deploy it as a Web App (access: anyone), and copy the deployment URL into the plugin settings.

== Changelog ==

= 1.0.0 =
* Initial release.
* Added dynamic website RAG content sync.
* Added conversational lead capture CPT dashboard.
* Added Google Sheets sync.
* Added granular color customization.
* Added Google Gemini 3.5 Flash support.
