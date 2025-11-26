<?php
/**
 * Webhook Handler - Send form data to external services
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Webhook {

    /**
     * Send form data to webhook URL
     *
     * @param array $form_data Form submission data
     * @param int $post_id Submission post ID
     * @param string $page_url URL of the page where form was submitted
     * @return bool|WP_Error
     */
    public static function send($form_data, $post_id, $page_url = '') {
        // Check if webhook is enabled
        if (!self::is_enabled()) {
            return false;
        }

        $webhook_url = self::get_webhook_url();

        if (empty($webhook_url)) {
            return false;
        }

        // Use provided page URL or fallback to current URL
        if (empty($page_url)) {
            $page_url = home_url($_SERVER['REQUEST_URI']);
        }

        // Prepare payload
        $payload = array(
            'event' => 'form_submission',
            'submission_id' => $post_id,
            'timestamp' => current_time('mysql'),
            'site_url' => get_bloginfo('url'),
            'site_name' => get_bloginfo('name'),
            'page_url' => esc_url_raw($page_url),
            'data' => array(
                'full_name' => sanitize_text_field($form_data['full_name']),
                'telephone' => sanitize_text_field($form_data['telephone']),
                'email' => sanitize_email($form_data['email']),
                'business_niche' => sanitize_text_field($form_data['business_niche']),
            ),
        );

        // Send webhook
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($payload),
            'timeout' => 15,
            'sslverify' => false,
        );

        $response = wp_remote_post($webhook_url, $args);

        // Log result
        if (is_wp_error($response)) {
            error_log('[UIC Webhook] Error: ' . $response->get_error_message());
            update_post_meta($post_id, '_webhook_status', 'failed');
            update_post_meta($post_id, '_webhook_error', $response->get_error_message());
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        update_post_meta($post_id, '_webhook_status', 'sent');
        update_post_meta($post_id, '_webhook_response_code', $status_code);
        update_post_meta($post_id, '_webhook_sent_at', current_time('mysql'));

        error_log('[UIC Webhook] Sent submission #' . $post_id . ' - HTTP ' . $status_code);

        return true;
    }

    /**
     * Test webhook connection
     *
     * @return array|WP_Error
     */
    public static function test() {
        $webhook_url = self::get_webhook_url();

        if (empty($webhook_url)) {
            return new WP_Error('no_url', 'No webhook URL configured');
        }

        $test_payload = array(
            'event' => 'test',
            'message' => 'Test from ' . get_bloginfo('name'),
            'timestamp' => current_time('mysql'),
        );

        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($test_payload),
            'timeout' => 15,
            'sslverify' => false,
        );

        $response = wp_remote_post($webhook_url, $args);

        if (is_wp_error($response)) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code($response);

        if ($status_code >= 200 && $status_code < 300) {
            return array(
                'success' => true,
                'message' => 'Webhook test successful (HTTP ' . $status_code . ')',
            );
        }

        return new WP_Error('test_failed', 'Webhook returned HTTP ' . $status_code);
    }

    /**
     * Check if webhook is enabled
     *
     * @return bool
     */
    public static function is_enabled() {
        return (bool) get_option('uic_webhook_enabled', false);
    }

    /**
     * Get webhook URL
     *
     * @return string
     */
    public static function get_webhook_url() {
        return get_option('uic_webhook_url', '');
    }
}
