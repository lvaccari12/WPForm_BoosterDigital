<?php
/**
 * Email Notification Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Email {

    /**
     * Send notification email on new submission
     */
    public static function send_notification($data, $post_id) {
        // Check if email notifications are enabled
        $email_enabled = get_option('uic_email_notifications', 'yes');

        if ($email_enabled !== 'yes') {
            return;
        }

        // Get recipient email
        $to = get_option('uic_notification_email', get_option('admin_email'));

        if (empty($to) || !is_email($to)) {
            $to = get_option('admin_email');
        }

        // Prepare email subject
        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] New User Info Form Submission', 'user-info-collector'),
            get_bloginfo('name')
        );

        // Prepare email body
        $body = sprintf(
            __('A new user info form submission has been received:', 'user-info-collector') . "\n\n" .
            __('Full Name:', 'user-info-collector') . " %s\n" .
            __('Telephone:', 'user-info-collector') . " %s\n" .
            __('Email:', 'user-info-collector') . " %s\n" .
            __('Description:', 'user-info-collector') . "\n%s\n\n" .
            __('Submitted on:', 'user-info-collector') . " %s\n\n" .
            __('View submission in admin:', 'user-info-collector') . "\n%s",
            sanitize_text_field($data['full_name']),
            sanitize_text_field($data['telephone']),
            sanitize_email($data['email']),
            sanitize_textarea_field($data['description']),
            current_time(get_option('date_format') . ' ' . get_option('time_format')),
            admin_url('admin.php?page=uic-submissions')
        );

        // Set headers
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        );

        // Send email
        wp_mail($to, $subject, $body, $headers);
    }
}
