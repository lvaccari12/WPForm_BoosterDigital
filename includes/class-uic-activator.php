<?php
/**
 * Activation Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Activator {

    /**
     * Run on plugin activation
     */
    public static function activate() {
        // Register CPT so rewrite rules are added
        $cpt = new UIC_CPT();
        $cpt->register_post_type();

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options if needed
        if (false === get_option('uic_email_notifications')) {
            add_option('uic_email_notifications', 'yes');
        }

        if (false === get_option('uic_notification_email')) {
            add_option('uic_notification_email', get_option('admin_email'));
        }
    }
}
