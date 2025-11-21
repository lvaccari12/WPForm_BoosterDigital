<?php
/**
 * Admin Area Handler
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Admin {

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('User Info Submissions', 'user-info-collector'),
            __('User Info', 'user-info-collector'),
            'manage_options',
            'uic-submissions',
            array($this, 'render_submissions_page'),
            'dashicons-feedback',
            30
        );

        add_submenu_page(
            'uic-submissions',
            __('All Submissions', 'user-info-collector'),
            __('All Submissions', 'user-info-collector'),
            'manage_options',
            'uic-submissions',
            array($this, 'render_submissions_page')
        );

        add_submenu_page(
            'uic-submissions',
            __('Settings', 'user-info-collector'),
            __('Settings', 'user-info-collector'),
            'manage_options',
            'uic-settings',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render submissions list page
     */
    public function render_submissions_page() {
        // Handle delete action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['submission_id']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_submission_' . $_GET['submission_id'])) {
                wp_delete_post($_GET['submission_id'], true);
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Submission deleted successfully.', 'user-info-collector') . '</p></div>';
            }
        }

        // Get all submissions
        $submissions = UIC_CPT::get_all_submissions();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('User Info Submissions', 'user-info-collector'); ?></h1>

            <?php if (empty($submissions)): ?>
                <p><?php esc_html_e('No submissions yet.', 'user-info-collector'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Full Name', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Telephone', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Email', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Description', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Date Submitted', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Actions', 'user-info-collector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($submissions as $submission): ?>
                            <tr>
                                <td><?php echo esc_html($submission['id']); ?></td>
                                <td><strong><?php echo esc_html($submission['full_name']); ?></strong></td>
                                <td><?php echo esc_html($submission['telephone']); ?></td>
                                <td><a href="mailto:<?php echo esc_attr($submission['email']); ?>"><?php echo esc_html($submission['email']); ?></a></td>
                                <td><?php echo esc_html(wp_trim_words($submission['description'], 10, '...')); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($submission['date']))); ?></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=uic-submissions&action=delete&submission_id=' . $submission['id']), 'delete_submission_' . $submission['id'])); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this submission?', 'user-info-collector'); ?>');">
                                        <?php esc_html_e('Delete', 'user-info-collector'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Handle settings save
        if (isset($_POST['uic_save_settings']) && check_admin_referer('uic_settings_save', 'uic_settings_nonce')) {
            $email_notifications = isset($_POST['uic_email_notifications']) ? 'yes' : 'no';
            $notification_email = sanitize_email($_POST['uic_notification_email']);

            update_option('uic_email_notifications', $email_notifications);
            update_option('uic_notification_email', $notification_email);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'user-info-collector') . '</p></div>';
        }

        $email_notifications = get_option('uic_email_notifications', 'yes');
        $notification_email = get_option('uic_notification_email', get_option('admin_email'));

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('User Info Collector Settings', 'user-info-collector'); ?></h1>

            <form method="post" action="">
                <?php wp_nonce_field('uic_settings_save', 'uic_settings_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="uic_email_notifications"><?php esc_html_e('Email Notifications', 'user-info-collector'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="uic_email_notifications"
                                   name="uic_email_notifications"
                                   value="yes"
                                   <?php checked($email_notifications, 'yes'); ?> />
                            <label for="uic_email_notifications">
                                <?php esc_html_e('Send email notification on new submission', 'user-info-collector'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="uic_notification_email"><?php esc_html_e('Notification Email', 'user-info-collector'); ?></label>
                        </th>
                        <td>
                            <input type="email"
                                   id="uic_notification_email"
                                   name="uic_notification_email"
                                   value="<?php echo esc_attr($notification_email); ?>"
                                   class="regular-text" />
                            <p class="description">
                                <?php esc_html_e('Email address to receive notifications (leave empty to use admin email)', 'user-info-collector'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="uic_save_settings" class="button button-primary">
                        <?php esc_html_e('Save Settings', 'user-info-collector'); ?>
                    </button>
                </p>
            </form>

            <hr />

            <h2><?php esc_html_e('Shortcode Usage', 'user-info-collector'); ?></h2>
            <p><?php esc_html_e('Use the following shortcode to display the form on any page or post:', 'user-info-collector'); ?></p>
            <code>[user_info_form]</code>
        </div>
        <?php
    }
}
