<?php
/**
 * Webhook Manager - Handles sending form data to N8N
 * Implements WordPress best practices for webhook integration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Webhook {

    /**
     * Send form data to N8N webhook
     *
     * @param array $form_data The form submission data
     * @param int $post_id The submission post ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public static function send_to_n8n($form_data, $post_id) {
        // Get webhook URL from settings
        $webhook_url = get_option('uic_n8n_webhook_url', '');

        // Check if webhook is enabled
        if (empty($webhook_url) || !self::is_webhook_enabled()) {
            return false;
        }

        // Validate webhook URL
        if (!wp_http_validate_url($webhook_url)) {
            error_log('[UIC Webhook] Invalid webhook URL configured');
            return new WP_Error('invalid_url', __('Invalid webhook URL', 'user-info-collector'));
        }

        // Prepare payload
        $payload = self::prepare_payload($form_data, $post_id);

        // Build request arguments
        $args = array(
            'method'      => 'POST',
            'headers'     => array(
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'User-Agent'   => 'WordPress-UIC-Plugin/' . UIC_VERSION,
            ),
            'body'        => wp_json_encode($payload),
            'timeout'     => 30,
            'sslverify'   => true,
            'httpversion' => '1.1',
        );

        // Add authentication if configured
        $api_key = get_option('uic_n8n_api_key', '');
        if (!empty($api_key)) {
            $args['headers']['Authorization'] = 'Bearer ' . $api_key;
        }

        // Send the webhook request
        $response = wp_remote_post($webhook_url, $args);

        // Handle response
        return self::handle_response($response, $post_id, $webhook_url);
    }

    /**
     * Prepare payload data for N8N
     *
     * @param array $form_data Form submission data
     * @param int $post_id Submission post ID
     * @return array Structured payload
     */
    private static function prepare_payload($form_data, $post_id) {
        return array(
            // Metadata
            'meta' => array(
                'source'         => 'WordPress - User Info Collector',
                'plugin_version' => UIC_VERSION,
                'site_url'       => get_bloginfo('url'),
                'site_name'      => get_bloginfo('name'),
                'timestamp'      => current_time('mysql'),
                'post_id'        => $post_id,
            ),

            // Form data
            'data' => array(
                'full_name'   => sanitize_text_field($form_data['full_name']),
                'telephone'   => sanitize_text_field($form_data['telephone']),
                'email'       => sanitize_email($form_data['email']),
                'description' => sanitize_textarea_field($form_data['description']),
            ),

            // Event information
            'event' => array(
                'type' => 'form_submission',
                'id'   => uniqid('uic_', true),
            ),
        );
    }

    /**
     * Handle webhook response
     *
     * @param array|WP_Error $response The HTTP response
     * @param int $post_id Submission post ID
     * @param string $webhook_url The webhook URL
     * @return bool|WP_Error
     */
    private static function handle_response($response, $post_id, $webhook_url) {
        // Check for WP_Error
        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();
            self::log_webhook_failure($post_id, $webhook_url, $error_message);
            return $response;
        }

        // Get response code
        $status_code = wp_remote_retrieve_response_code($response);

        // Success: 2xx status codes
        if ($status_code >= 200 && $status_code < 300) {
            self::log_webhook_success($post_id, $webhook_url, $status_code);
            return true;
        }

        // Failed with HTTP error
        $error_message = sprintf('HTTP %d: %s', $status_code, wp_remote_retrieve_body($response));
        self::log_webhook_failure($post_id, $webhook_url, $error_message);

        return new WP_Error('webhook_failed', $error_message);
    }

    /**
     * Log successful webhook delivery
     *
     * @param int $post_id Submission post ID
     * @param string $webhook_url Webhook URL
     * @param int $status_code HTTP status code
     */
    private static function log_webhook_success($post_id, $webhook_url, $status_code) {
        update_post_meta($post_id, '_uic_webhook_status', 'sent');
        update_post_meta($post_id, '_uic_webhook_sent_at', current_time('mysql'));
        update_post_meta($post_id, '_uic_webhook_url', $webhook_url);
        update_post_meta($post_id, '_uic_webhook_response_code', $status_code);

        error_log(sprintf(
            '[UIC Webhook] Successfully sent submission #%d to N8N (HTTP %d)',
            $post_id,
            $status_code
        ));
    }

    /**
     * Log webhook failure
     *
     * @param int $post_id Submission post ID
     * @param string $webhook_url Webhook URL
     * @param string $error_message Error message
     */
    private static function log_webhook_failure($post_id, $webhook_url, $error_message) {
        update_post_meta($post_id, '_uic_webhook_status', 'failed');
        update_post_meta($post_id, '_uic_webhook_error', $error_message);
        update_post_meta($post_id, '_uic_webhook_failed_at', current_time('mysql'));

        error_log(sprintf(
            '[UIC Webhook] Failed to send submission #%d to N8N: %s',
            $post_id,
            $error_message
        ));
    }

    /**
     * Check if webhook is enabled
     *
     * @return bool
     */
    public static function is_webhook_enabled() {
        return (bool) get_option('uic_n8n_webhook_enabled', false);
    }

    /**
     * Test webhook connection
     *
     * @return array|WP_Error Result of test
     */
    public static function test_webhook() {
        $webhook_url = get_option('uic_n8n_webhook_url', '');

        if (empty($webhook_url)) {
            return new WP_Error('no_url', __('No webhook URL configured', 'user-info-collector'));
        }

        if (!wp_http_validate_url($webhook_url)) {
            return new WP_Error('invalid_url', __('Invalid webhook URL', 'user-info-collector'));
        }

        // Send test payload
        $test_payload = array(
            'test' => true,
            'message' => 'Test webhook from ' . get_bloginfo('name'),
            'timestamp' => current_time('mysql'),
            'site_url' => get_bloginfo('url'),
        );

        $args = array(
            'method'      => 'POST',
            'headers'     => array(
                'Content-Type' => 'application/json',
                'User-Agent'   => 'WordPress-UIC-Plugin/' . UIC_VERSION,
            ),
            'body'        => wp_json_encode($test_payload),
            'timeout'     => 15,
            'sslverify'   => true,
        );

        // Add API key if configured
        $api_key = get_option('uic_n8n_api_key', '');
        if (!empty($api_key)) {
            $args['headers']['Authorization'] = 'Bearer ' . $api_key;
        }

        $response = wp_remote_post($webhook_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'message' => sprintf(__('Webhook test successful! (HTTP %d)', 'user-info-collector'), $status_code),
                'status_code' => $status_code,
            );
        }

        return new WP_Error(
            'test_failed',
            sprintf(__('Webhook test failed with HTTP %d', 'user-info-collector'), $status_code)
        );
    }

    /**
     * Get webhook status for a submission
     *
     * @param int $post_id Submission post ID
     * @return array Webhook status information
     */
    public static function get_webhook_status($post_id) {
        return array(
            'status'        => get_post_meta($post_id, '_uic_webhook_status', true),
            'sent_at'       => get_post_meta($post_id, '_uic_webhook_sent_at', true),
            'failed_at'     => get_post_meta($post_id, '_uic_webhook_failed_at', true),
            'error'         => get_post_meta($post_id, '_uic_webhook_error', true),
            'response_code' => get_post_meta($post_id, '_uic_webhook_response_code', true),
            'url'           => get_post_meta($post_id, '_uic_webhook_url', true),
        );
    }
}
