<?php
/**
 * Shortcode Registration and Form Processing
 * Uses WordPress admin-post.php best practices for form handling
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_Shortcode {

    /**
     * Constructor - Register hooks
     */
    public function __construct() {
        // Register form processing hooks using admin-post.php
        // admin_post_nopriv_ is for non-logged-in users (public forms)
        add_action('admin_post_nopriv_uic_submit_form', array($this, 'handle_form_submission'));

        // admin_post_ is for logged-in users
        add_action('admin_post_uic_submit_form', array($this, 'handle_form_submission'));
    }

    /**
     * Register the shortcode
     */
    public function register_shortcode() {
        add_shortcode('user_info_form', array($this, 'render_form'));
    }

    /**
     * Handle form submission via admin-post.php
     * This runs BEFORE page rendering, preventing header issues
     */
    public function handle_form_submission() {
        // Verify nonce for security
        if (!isset($_POST['uic_nonce']) ||
            !wp_verify_nonce($_POST['uic_nonce'], 'uic_form_submit')) {
            wp_die(__('Security check failed. Please try again.', 'user-info-collector'));
        }

        // Get and sanitize form data
        $submitted_data = array(
            'full_name'   => isset($_POST['uic_full_name']) ? sanitize_text_field($_POST['uic_full_name']) : '',
            'telephone'   => isset($_POST['uic_telephone']) ? sanitize_text_field($_POST['uic_telephone']) : '',
            'email'       => isset($_POST['uic_email']) ? sanitize_email($_POST['uic_email']) : '',
            'description' => isset($_POST['uic_description']) ? sanitize_textarea_field($_POST['uic_description']) : '',
        );

        // Validate form data
        $errors = $this->validate_form_data($submitted_data);

        // Get the redirect URL from hidden field (more reliable than wp_get_referer)
        $redirect_url = isset($_POST['uic_redirect_to']) ? esc_url_raw($_POST['uic_redirect_to']) : home_url();

        // Validate redirect URL is from our site (security check)
        if (strpos($redirect_url, home_url()) !== 0) {
            $redirect_url = home_url();
        }

        // If validation fails, redirect back with errors
        if (!empty($errors)) {
            // Store errors in transient for 60 seconds
            $transient_key = 'uic_errors_' . md5($redirect_url . time());
            set_transient($transient_key, array(
                'errors' => $errors,
                'data' => $submitted_data
            ), 60);

            // Redirect with error flag and transient key
            $redirect_url = add_query_arg(array(
                'uic_error' => '1',
                'uic_key' => $transient_key
            ), $redirect_url);

            wp_safe_redirect($redirect_url);
            exit;
        }

        // Save submission
        $post_id = UIC_CPT::create_submission($submitted_data);

        if ($post_id) {
            // Send email notification
            UIC_Email::send_notification($submitted_data, $post_id);

            // Redirect with success parameter
            $redirect_url = add_query_arg('uic_success', '1', $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        } else {
            // Redirect with error if save failed
            $redirect_url = add_query_arg('uic_error', 'save_failed', $redirect_url);
            wp_safe_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Validate form data
     * Returns array of errors (empty if validation passes)
     */
    private function validate_form_data($data) {
        $errors = array();

        // Validate full name
        if (empty(trim($data['full_name']))) {
            $errors['full_name'] = __('Full Name is required.', 'user-info-collector');
        }

        // Validate telephone
        $telephone = trim($data['telephone']);
        if (empty($telephone)) {
            $errors['telephone'] = __('Telephone is required.', 'user-info-collector');
        } elseif (!preg_match('/^[0-9\s\+\(\)\-]+$/', $telephone)) {
            $errors['telephone'] = __('Telephone must be a valid phone number.', 'user-info-collector');
        }

        // Validate email
        $email = trim($data['email']);
        if (empty($email)) {
            $errors['email'] = __('Email is required.', 'user-info-collector');
        } elseif (!is_email($email)) {
            $errors['email'] = __('Please enter a valid email address.', 'user-info-collector');
        }

        return $errors;
    }

    /**
     * Shortcode callback - Only renders, does not process
     */
    public function render_form($atts) {
        // Start output buffering
        ob_start();

        // Check for success message
        if (isset($_GET['uic_success']) && $_GET['uic_success'] === '1') {
            $this->display_success_message();
        }
        // Check for error and display form with errors
        elseif (isset($_GET['uic_error']) && isset($_GET['uic_key'])) {
            $transient_data = get_transient($_GET['uic_key']);
            if ($transient_data) {
                $this->display_form($transient_data['errors'], $transient_data['data']);
                // Delete transient after use
                delete_transient($_GET['uic_key']);
            } else {
                $this->display_form();
            }
        }
        // Check for generic error
        elseif (isset($_GET['uic_error'])) {
            $this->display_generic_error();
            $this->display_form();
        }
        // Display normal form
        else {
            $this->display_form();
        }

        return ob_get_clean();
    }

    /**
     * Display success message
     */
    private function display_success_message() {
        ?>
        <div class="uic-success-message">
            <p><?php esc_html_e('Thank you! Your information has been submitted successfully.', 'user-info-collector'); ?></p>
        </div>
        <?php
    }

    /**
     * Display generic error message
     */
    private function display_generic_error() {
        ?>
        <div class="uic-error-message">
            <p><strong><?php esc_html_e('An error occurred while saving your submission. Please try again.', 'user-info-collector'); ?></strong></p>
        </div>
        <?php
    }

    /**
     * Display the form
     */
    private function display_form($errors = array(), $submitted_data = array()) {
        // Get current page URL for redirect (without query params)
        global $wp;
        $current_url = home_url(add_query_arg(array(), $wp->request));
        ?>
        <div class="uic-form-wrapper">
            <?php if (!empty($errors)): ?>
                <div class="uic-error-message">
                    <p><strong><?php esc_html_e('Please fix the errors below and try again:', 'user-info-collector'); ?></strong></p>
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo esc_html($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="uic-form" novalidate>
                <?php wp_nonce_field('uic_form_submit', 'uic_nonce'); ?>

                <!-- Hidden field: tells admin-post.php which action to run -->
                <input type="hidden" name="action" value="uic_submit_form" />

                <!-- Hidden field: where to redirect after submission (replaces wp_get_referer) -->
                <input type="hidden" name="uic_redirect_to" value="<?php echo esc_url($current_url); ?>" />

                <!-- Full Name Field -->
                <div class="uic-form-field">
                    <label for="uic_full_name" class="uic-label">
                        <?php esc_html_e('Full Name', 'user-info-collector'); ?> <span class="uic-required">*</span>
                    </label>
                    <input
                        type="text"
                        id="uic_full_name"
                        name="uic_full_name"
                        class="uic-input <?php echo isset($errors['full_name']) ? 'uic-input-error' : ''; ?>"
                        value="<?php echo isset($submitted_data['full_name']) ? esc_attr($submitted_data['full_name']) : ''; ?>"
                        placeholder="<?php esc_attr_e('Enter your full name', 'user-info-collector'); ?>"
                        required
                    />
                    <?php if (isset($errors['full_name'])): ?>
                        <span class="uic-field-error"><?php echo esc_html($errors['full_name']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Telephone Field -->
                <div class="uic-form-field">
                    <label for="uic_telephone" class="uic-label">
                        <?php esc_html_e('Telephone', 'user-info-collector'); ?> <span class="uic-required">*</span>
                    </label>
                    <input
                        type="tel"
                        id="uic_telephone"
                        name="uic_telephone"
                        class="uic-input <?php echo isset($errors['telephone']) ? 'uic-input-error' : ''; ?>"
                        value="<?php echo isset($submitted_data['telephone']) ? esc_attr($submitted_data['telephone']) : ''; ?>"
                        placeholder="<?php esc_attr_e('Enter your phone number', 'user-info-collector'); ?>"
                        required
                    />
                    <?php if (isset($errors['telephone'])): ?>
                        <span class="uic-field-error"><?php echo esc_html($errors['telephone']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Email Field -->
                <div class="uic-form-field">
                    <label for="uic_email" class="uic-label">
                        <?php esc_html_e('Email', 'user-info-collector'); ?> <span class="uic-required">*</span>
                    </label>
                    <input
                        type="email"
                        id="uic_email"
                        name="uic_email"
                        class="uic-input <?php echo isset($errors['email']) ? 'uic-input-error' : ''; ?>"
                        value="<?php echo isset($submitted_data['email']) ? esc_attr($submitted_data['email']) : ''; ?>"
                        placeholder="<?php esc_attr_e('Enter your email address', 'user-info-collector'); ?>"
                        required
                    />
                    <?php if (isset($errors['email'])): ?>
                        <span class="uic-field-error"><?php echo esc_html($errors['email']); ?></span>
                    <?php endif; ?>
                </div>

                <!-- Description Field -->
                <div class="uic-form-field">
                    <label for="uic_description" class="uic-label">
                        <?php esc_html_e('Description', 'user-info-collector'); ?> <span class="uic-optional"><?php esc_html_e('(Optional)', 'user-info-collector'); ?></span>
                    </label>
                    <textarea
                        id="uic_description"
                        name="uic_description"
                        class="uic-textarea"
                        rows="5"
                        placeholder="<?php esc_attr_e('Enter additional information', 'user-info-collector'); ?>"
                    ><?php echo isset($submitted_data['description']) ? esc_textarea($submitted_data['description']) : ''; ?></textarea>
                </div>

                <!-- Submit Button -->
                <div class="uic-form-field">
                    <button type="submit" class="uic-submit-button">
                        <?php esc_html_e('Submit', 'user-info-collector'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
}
