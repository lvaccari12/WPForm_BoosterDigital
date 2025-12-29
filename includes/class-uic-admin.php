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
            __('Business Niches', 'user-info-collector'),
            __('Business Niches', 'user-info-collector'),
            'manage_options',
            'uic-business-niches',
            array($this, 'render_business_niches_page')
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
                            <th><?php esc_html_e('Business Niche', 'user-info-collector'); ?></th>
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
                                <td><?php echo esc_html($submission['business_niche']); ?></td>
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
     * Render business niches management page
     */
    public function render_business_niches_page() {
        // Handle add niche action
        if (isset($_POST['uic_add_niche']) && check_admin_referer('uic_add_niche', 'uic_add_niche_nonce')) {
            $new_niche = sanitize_text_field($_POST['uic_new_niche']);

            if (!empty($new_niche)) {
                $niches = get_option('uic_business_niches', array());

                // Check for duplicates
                if (!in_array($new_niche, $niches)) {
                    $niches[] = $new_niche;
                    update_option('uic_business_niches', $niches);
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Business niche added successfully.', 'user-info-collector') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('This business niche already exists.', 'user-info-collector') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Please enter a business niche name.', 'user-info-collector') . '</p></div>';
            }
        }

        // Handle delete niche action
        if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['niche']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce($_GET['_wpnonce'], 'delete_niche_' . $_GET['niche'])) {
                $niches = get_option('uic_business_niches', array());
                $niche_to_delete = sanitize_text_field($_GET['niche']);

                $key = array_search($niche_to_delete, $niches);
                if ($key !== false) {
                    unset($niches[$key]);
                    $niches = array_values($niches); // Re-index array
                    update_option('uic_business_niches', $niches);
                    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Business niche deleted successfully.', 'user-info-collector') . '</p></div>';
                }
            }
        }

        $niches = get_option('uic_business_niches', array());

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Business Niches', 'user-info-collector'); ?></h1>
            <p><?php esc_html_e('Manage the business niche options that appear in your form dropdown.', 'user-info-collector'); ?></p>

            <div style="background: #fff; padding: 20px; margin: 20px 0; border: 1px solid #ccc; border-radius: 4px;">
                <h2><?php esc_html_e('Add New Business Niche', 'user-info-collector'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('uic_add_niche', 'uic_add_niche_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="uic_new_niche"><?php esc_html_e('Business Niche Name', 'user-info-collector'); ?></label>
                            </th>
                            <td>
                                <input type="text"
                                       id="uic_new_niche"
                                       name="uic_new_niche"
                                       class="regular-text"
                                       placeholder="<?php esc_attr_e('e.g., Real Estate Agents', 'user-info-collector'); ?>" />
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <button type="submit" name="uic_add_niche" class="button button-primary">
                            <?php esc_html_e('Add Business Niche', 'user-info-collector'); ?>
                        </button>
                    </p>
                </form>
            </div>

            <h2><?php esc_html_e('Current Business Niches', 'user-info-collector'); ?></h2>

            <?php if (empty($niches)): ?>
                <p><?php esc_html_e('No business niches configured yet.', 'user-info-collector'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php esc_html_e('#', 'user-info-collector'); ?></th>
                            <th><?php esc_html_e('Business Niche', 'user-info-collector'); ?></th>
                            <th style="width: 150px;"><?php esc_html_e('Actions', 'user-info-collector'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($niches as $index => $niche): ?>
                            <tr>
                                <td><?php echo esc_html($index + 1); ?></td>
                                <td><strong><?php echo esc_html($niche); ?></strong></td>
                                <td>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=uic-business-niches&action=delete&niche=' . urlencode($niche)), 'delete_niche_' . $niche)); ?>"
                                       class="button button-small"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this business niche?', 'user-info-collector'); ?>');">
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

            // Webhook settings
            $webhook_enabled = isset($_POST['uic_webhook_enabled']) ? true : false;
            $webhook_url = esc_url_raw($_POST['uic_webhook_url']);

            update_option('uic_email_notifications', $email_notifications);
            update_option('uic_notification_email', $notification_email);
            update_option('uic_webhook_enabled', $webhook_enabled);
            update_option('uic_webhook_url', $webhook_url);

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully.', 'user-info-collector') . '</p></div>';
        }

        // Handle webhook test
        if (isset($_POST['uic_test_webhook']) && check_admin_referer('uic_test_webhook', 'uic_test_webhook_nonce')) {
            $result = UIC_Webhook::test();

            if (is_wp_error($result)) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Webhook Test Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($result['message']) . '</p></div>';
            }
        }

        $email_notifications = get_option('uic_email_notifications', 'yes');
        $notification_email = get_option('uic_notification_email', get_option('admin_email'));
        $webhook_enabled = get_option('uic_webhook_enabled', false);
        $webhook_url = get_option('uic_webhook_url', '');

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

                <h2><?php esc_html_e('Webhook Integration', 'user-info-collector'); ?></h2>
                <p><?php esc_html_e('Send form submissions to external services in real-time.', 'user-info-collector'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="uic_webhook_enabled"><?php esc_html_e('Enable Webhook', 'user-info-collector'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox"
                                   id="uic_webhook_enabled"
                                   name="uic_webhook_enabled"
                                   value="1"
                                   <?php checked($webhook_enabled, true); ?> />
                            <label for="uic_webhook_enabled">
                                <?php esc_html_e('Send data to webhook URL on each submission', 'user-info-collector'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="uic_webhook_url"><?php esc_html_e('Webhook URL', 'user-info-collector'); ?></label>
                        </th>
                        <td>
                            <input type="url"
                                   id="uic_webhook_url"
                                   name="uic_webhook_url"
                                   value="<?php echo esc_attr($webhook_url); ?>"
                                   class="regular-text"
                                   placeholder="https://your-service.com/webhook" />
                            <p class="description">
                                <?php esc_html_e('Enter the URL where form data should be sent', 'user-info-collector'); ?>
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

            <?php if (!empty($webhook_url)): ?>
            <hr />
            <h2><?php esc_html_e('Test Webhook', 'user-info-collector'); ?></h2>
            <p><?php esc_html_e('Send a test request to verify your webhook is working.', 'user-info-collector'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('uic_test_webhook', 'uic_test_webhook_nonce'); ?>
                <p class="submit">
                    <button type="submit" name="uic_test_webhook" class="button button-secondary">
                        <?php esc_html_e('Test Webhook', 'user-info-collector'); ?>
                    </button>
                </p>
            </form>
            <?php endif; ?>

            <hr />

            <h2><?php esc_html_e('Shortcode Usage', 'user-info-collector'); ?></h2>
            <p><?php esc_html_e('Use the following shortcode to display the form on any page or post:', 'user-info-collector'); ?></p>
            <code>[user_info_form]</code>
        </div>
        <?php
    }
}
