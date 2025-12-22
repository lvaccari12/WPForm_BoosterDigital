<?php
/**
 * Plugin Name: User Info Collector
 * Plugin URI: https://example.com/user-info-collector
 * Description: A secure WordPress plugin to collect user information via front-end form with admin management
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: user-info-collector
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('UIC_VERSION', '1.0.0');
define('UIC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('UIC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('UIC_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
class User_Info_Collector {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get single instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->set_locale();
        $this->define_hooks();
    }

    /**
     * Load required dependencies
     */
    private function load_dependencies() {
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-activator.php';
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-cpt.php';
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-shortcode.php';
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-admin.php';
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-email.php';
        require_once UIC_PLUGIN_DIR . 'includes/class-uic-webhook.php';
    }

    /**
     * Load plugin text domain for translations
     */
    private function set_locale() {
        add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
    }

    /**
     * Load text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'user-info-collector',
            false,
            dirname(UIC_PLUGIN_BASENAME) . '/languages/'
        );
    }

    /**
     * Define all hooks
     */
    private function define_hooks() {
        // Activation hook
        register_activation_hook(__FILE__, array('UIC_Activator', 'activate'));

        // Initialize CPT
        $cpt = new UIC_CPT();
        add_action('init', array($cpt, 'register_post_type'));

        // Initialize Shortcode
        $shortcode = new UIC_Shortcode();
        add_action('init', array($shortcode, 'register_shortcode'));

        // Initialize Admin
        $admin = new UIC_Admin();
        add_action('admin_menu', array($admin, 'add_admin_menu'));

        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    }

    /**
     * Enqueue front-end styles and scripts
     */
    public function enqueue_styles() {
        // Enqueue intl-tel-input CSS from CDN
        wp_enqueue_style(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/css/intlTelInput.css',
            array(),
            '23.0.12'
        );

        // Enqueue plugin styles with auto-versioning based on file modification time
        $css_file = UIC_PLUGIN_DIR . 'assets/css/uic-styles.css';
        wp_enqueue_style(
            'uic-styles',
            UIC_PLUGIN_URL . 'assets/css/uic-styles.css',
            array('intl-tel-input'),
            file_exists($css_file) ? filemtime($css_file) : UIC_VERSION
        );

        // Enqueue intl-tel-input JS from CDN
        wp_enqueue_script(
            'intl-tel-input',
            'https://cdn.jsdelivr.net/npm/intl-tel-input@23.0.12/build/js/intlTelInput.min.js',
            array(),
            '23.0.12',
            true
        );

        // Enqueue custom initialization script with auto-versioning
        $js_file = UIC_PLUGIN_DIR . 'assets/js/uic-phone-input.js';
        wp_enqueue_script(
            'uic-phone-input',
            UIC_PLUGIN_URL . 'assets/js/uic-phone-input.js',
            array('intl-tel-input'),
            file_exists($js_file) ? filemtime($js_file) : UIC_VERSION,
            true
        );
    }
}

/**
 * Initialize the plugin
 */
function uic_run() {
    return User_Info_Collector::get_instance();
}

// Start the plugin
uic_run();
