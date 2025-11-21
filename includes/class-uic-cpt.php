<?php
/**
 * Custom Post Type Registration
 */

if (!defined('ABSPATH')) {
    exit;
}

class UIC_CPT {

    /**
     * Post type slug
     */
    const POST_TYPE = 'uic_submission';

    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'                  => _x('User Submissions', 'Post Type General Name', 'user-info-collector'),
            'singular_name'         => _x('User Submission', 'Post Type Singular Name', 'user-info-collector'),
            'menu_name'             => __('User Submissions', 'user-info-collector'),
            'name_admin_bar'        => __('User Submission', 'user-info-collector'),
            'archives'              => __('Submission Archives', 'user-info-collector'),
            'attributes'            => __('Submission Attributes', 'user-info-collector'),
            'all_items'             => __('All Submissions', 'user-info-collector'),
            'add_new_item'          => __('Add New Submission', 'user-info-collector'),
            'add_new'               => __('Add New', 'user-info-collector'),
            'new_item'              => __('New Submission', 'user-info-collector'),
            'edit_item'             => __('Edit Submission', 'user-info-collector'),
            'update_item'           => __('Update Submission', 'user-info-collector'),
            'view_item'             => __('View Submission', 'user-info-collector'),
            'view_items'            => __('View Submissions', 'user-info-collector'),
            'search_items'          => __('Search Submission', 'user-info-collector'),
            'not_found'             => __('Not found', 'user-info-collector'),
            'not_found_in_trash'    => __('Not found in Trash', 'user-info-collector'),
        );

        $args = array(
            'label'                 => __('User Submission', 'user-info-collector'),
            'description'           => __('User information submissions from front-end form', 'user-info-collector'),
            'labels'                => $labels,
            'supports'              => array('title'),
            'hierarchical'          => false,
            'public'                => false,
            'show_ui'               => true,
            'show_in_menu'          => false, // We'll add our own menu
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities'          => array(
                'create_posts' => 'do_not_allow',
            ),
            'map_meta_cap'          => true,
        );

        register_post_type(self::POST_TYPE, $args);
    }

    /**
     * Create a new submission post
     */
    public static function create_submission($data) {
        // Sanitize all data
        $full_name = sanitize_text_field($data['full_name']);
        $telephone = sanitize_text_field($data['telephone']);
        $email = sanitize_email($data['email']);
        $description = sanitize_textarea_field($data['description']);

        // Create post
        $post_id = wp_insert_post(array(
            'post_title'    => sprintf(
                /* translators: %s: submitter name */
                __('Submission from %s', 'user-info-collector'),
                $full_name
            ),
            'post_type'     => self::POST_TYPE,
            'post_status'   => 'publish',
            'post_author'   => 1,
        ));

        if (is_wp_error($post_id)) {
            return false;
        }

        // Add meta data
        update_post_meta($post_id, '_uic_full_name', $full_name);
        update_post_meta($post_id, '_uic_telephone', $telephone);
        update_post_meta($post_id, '_uic_email', $email);
        update_post_meta($post_id, '_uic_description', $description);
        update_post_meta($post_id, '_uic_submission_date', current_time('mysql'));

        return $post_id;
    }

    /**
     * Get submission data by post ID
     */
    public static function get_submission($post_id) {
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return false;
        }

        return array(
            'id'            => $post_id,
            'full_name'     => get_post_meta($post_id, '_uic_full_name', true),
            'telephone'     => get_post_meta($post_id, '_uic_telephone', true),
            'email'         => get_post_meta($post_id, '_uic_email', true),
            'description'   => get_post_meta($post_id, '_uic_description', true),
            'date'          => get_post_meta($post_id, '_uic_submission_date', true),
        );
    }

    /**
     * Get all submissions
     */
    public static function get_all_submissions($args = array()) {
        $defaults = array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $args = wp_parse_args($args, $defaults);
        $posts = get_posts($args);

        $submissions = array();
        foreach ($posts as $post) {
            $submissions[] = self::get_submission($post->ID);
        }

        return $submissions;
    }
}
