<?php
/**
 * Plugin Name:       ClonePress
 * Plugin URI:        https://ilmosys.com
 * Description:       Duplicates posts, pages, and custom post types with a single click.
 * Author:            ilmosys
 * Author URI:        https://www.ilmosys.com
 * Version:           1.0.2
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       clonepress
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Hook to add duplicate link to post row actions for posts, pages, and custom post types
add_filter('post_row_actions', 'clonepress_duplicate_post_link', 10, 2);
add_filter('page_row_actions', 'clonepress_duplicate_post_link', 10, 2);

// Add duplicate link to custom post types
add_action('admin_init', 'clonepress_add_duplicate_link_to_custom_post_types');

function clonepress_add_duplicate_link_to_custom_post_types() {
    $post_types = get_post_types(array('_builtin' => false), 'names');
    foreach ($post_types as $post_type) {
        add_filter("{$post_type}_row_actions", 'clonepress_duplicate_post_link', 10, 2);
    }
}

function clonepress_duplicate_post_link($actions, $post) {
    if (current_user_can('edit_posts')) {
        $options = get_option('clonepress_settings');
        $duplicate_label = isset($options['duplicate_label']) ? $options['duplicate_label'] : __('Duplicate');
        $actions['duplicate'] = '<a href="' . wp_nonce_url('admin.php?action=clonepress_duplicate_post&post=' . $post->ID, basename(__FILE__), 'clonepress_duplicate_nonce') . '" title="' . esc_attr($duplicate_label) . '" rel="permalink">' . esc_html($duplicate_label) . '</a>';
    }
    return $actions;
}

// Handle the duplication process
add_action('admin_action_clonepress_duplicate_post', 'clonepress_duplicate_post');

function clonepress_duplicate_post() {
    global $wpdb;

    if (!isset($_GET['post']) || !isset($_GET['clonepress_duplicate_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['clonepress_duplicate_nonce'])), basename(__FILE__))) {
        wp_die('No post to duplicate has been supplied!');
    }

    $post_id = absint($_GET['post']);
    $post = get_post($post_id);

    if ($post) {
        // Get settings
        $options = get_option('clonepress_settings');
        $post_status = isset($options['duplicate_post_status']) ? $options['duplicate_post_status'] : 'draft';
        $suffix = isset($options['duplicate_suffix']) ? $options['duplicate_suffix'] : ' (Copy)';

        // Create new post
        $new_post = array(
            'post_title'   => $post->post_title . $suffix,
            'post_content' => $post->post_content,
            'post_status'  => $post_status,
            'post_type'    => $post->post_type,
            'post_author'  => $post->post_author,
        );

        $new_post_id = wp_insert_post($new_post);

        if ($new_post_id) {
            // Duplicate post meta using a single SQL query
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                     SELECT %d, meta_key, meta_value
                     FROM {$wpdb->postmeta} WHERE post_id = %d",
                    $new_post_id, $post_id
                )
            );

            // Check if Elementor-related meta keys exist in the new post
            $elementor_css = get_post_meta($new_post_id, '_elementor_css', false);
            $elementor_cache = get_post_meta($new_post_id, '_elementor_element_cache', false);

            if ( !empty( $elementor_css ) ) {
                delete_post_meta($new_post_id, '_elementor_css');
            }

            if ( !empty($elementor_cache ) ) {
                delete_post_meta($new_post_id, '_elementor_element_cache');
            }

            // Duplicate taxonomies
            $taxonomies = get_object_taxonomies($post->post_type);
            foreach ($taxonomies as $taxonomy) {
                $terms = wp_get_object_terms($post_id, $taxonomy, ['fields' => 'ids']);
                wp_set_object_terms($new_post_id, $terms, $taxonomy);
            }

            // Redirect after duplication
            $redirect = $options['duplicate_redirect'] ?? 'list';
            $referer = wp_get_referer(); // Get the previous page URL
            if ($redirect == 'edit') {
                wp_redirect(admin_url('post.php?action=edit&post=' . $new_post_id));
            } elseif (!empty($referer)) {
                wp_redirect($referer); // Redirect back to the previous page
            } else {
                wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
            }
            exit;
        }
    } else {
        wp_die('Post creation failed, could not find original post.');
    }
}

// Add admin menu
add_action('admin_menu', 'clonepress_add_admin_menu');

function clonepress_add_admin_menu() {
    add_options_page(
        'ClonePress Settings',
        'ClonePress',
        'manage_options',
        'clonepress',
        'clonepress_options_page'
    );
}

function clonepress_options_page() {
    ?>
    <div class="wrap">
        <h1>ClonePress Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('clonepress_settings');
            do_settings_sections('clonepress');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings with proper sanitization
add_action('admin_init', 'clonepress_settings_init');

function clonepress_settings_init() {
    register_setting(
        'clonepress_settings',
        'clonepress_settings',
        array(
            'type' => 'array',
            'sanitize_callback' => 'clonepress_sanitize_settings',
            'default' => array('duplicate_post_status' => 'draft', 'duplicate_label' => __('Duplicate'), 'duplicate_redirect' => 'list', 'duplicate_suffix' => ' (Copy)')
        )
    );

    add_settings_section(
        'clonepress_settings_section',
        'Settings',
        'clonepress_settings_section_callback',
        'clonepress'
    );

    add_settings_field(
        'clonepress_duplicate_post_status',
        'Duplicate Post / Page Status',
        'clonepress_duplicate_post_status_render',
        'clonepress',
        'clonepress_settings_section'
    );

    add_settings_field(
        'clonepress_duplicate_label',
        'Duplicate Label Text',
        'clonepress_duplicate_label_render',
        'clonepress',
        'clonepress_settings_section'
    );

    add_settings_field(
        'clonepress_duplicate_redirect',
        'Redirect After Duplication',
        'clonepress_duplicate_redirect_render',
        'clonepress',
        'clonepress_settings_section'
    );

    add_settings_field(
        'clonepress_duplicate_suffix',
        'Duplicate Post Suffix',
        'clonepress_duplicate_suffix_render',
        'clonepress',
        'clonepress_settings_section'
    );
}

/**
 * Sanitize settings
 *
 * @param array $input The value being saved.
 * @return array The sanitized value.
 */
function clonepress_sanitize_settings($input) {
    $sanitized_input = array();
    
    if (isset($input['duplicate_post_status'])) {
        $allowed_values = array('draft', 'publish');
        $sanitized_input['duplicate_post_status'] = in_array($input['duplicate_post_status'], $allowed_values) 
            ? sanitize_text_field($input['duplicate_post_status'])
            : 'draft';
    }
    
    if (isset($input['duplicate_label'])) {
        $sanitized_input['duplicate_label'] = sanitize_text_field($input['duplicate_label']);
        if (empty($sanitized_input['duplicate_label'])) {
            $sanitized_input['duplicate_label'] = __('Duplicate');
        }
    }

    if (isset($input['duplicate_redirect'])) {
        $allowed_values = array('list', 'edit');
        $sanitized_input['duplicate_redirect'] = in_array($input['duplicate_redirect'], $allowed_values) 
            ? sanitize_text_field($input['duplicate_redirect'])
            : 'list';
    }

    if (isset($input['duplicate_suffix'])) {
        $sanitized_input['duplicate_suffix'] = sanitize_text_field($input['duplicate_suffix']);
        if (empty($sanitized_input['duplicate_suffix'])) {
            $sanitized_input['duplicate_suffix'] = ' (Copy)';
        }
    }

    return $sanitized_input;
}

function clonepress_settings_section_callback() {
    echo 'Configure the settings for the ClonePress Plugin.';
}

function clonepress_duplicate_post_status_render() {
    $options = get_option('clonepress_settings');
    if (!is_array($options)) {
        $options = array('duplicate_post_status' => 'draft', 'duplicate_label' => __('Duplicate'), 'duplicate_redirect' => 'list', 'duplicate_suffix' => ' (Copy)');
    }
    ?>
    <select name="clonepress_settings[duplicate_post_status]">
        <option value="draft" <?php selected(isset($options['duplicate_post_status']) ? $options['duplicate_post_status'] : '', 'draft'); ?>>Draft</option>
        <option value="publish" <?php selected(isset($options['duplicate_post_status']) ? $options['duplicate_post_status'] : '', 'publish'); ?>>Publish</option>
    </select>
    <?php
}

function clonepress_duplicate_label_render() {
    $options = get_option('clonepress_settings');
    if (!is_array($options)) {
        $options = array('duplicate_post_status' => 'draft', 'duplicate_label' => __('Duplicate'), 'duplicate_redirect' => 'list', 'duplicate_suffix' => ' (Copy)');
    }
    ?>
    <input type="text" name="clonepress_settings[duplicate_label]" value="<?php echo esc_attr(isset($options['duplicate_label']) ? $options['duplicate_label'] : __('Duplicate')); ?>" />
    <?php
}

function clonepress_duplicate_redirect_render() {
    $options = get_option('clonepress_settings');
    if (!is_array($options)) {
        $options = array('duplicate_post_status' => 'draft', 'duplicate_label' => __('Duplicate'), 'duplicate_redirect' => 'list', 'duplicate_suffix' => ' (Copy)');
    }
    ?>
    <select name="clonepress_settings[duplicate_redirect]">
        <option value="list" <?php selected(isset($options['duplicate_redirect']) ? $options['duplicate_redirect'] : '', 'list'); ?>>Post List</option>
        <option value="edit" <?php selected(isset($options['duplicate_redirect']) ? $options['duplicate_redirect'] : '', 'edit'); ?>>Edit Page</option>
    </select>
    <?php
}

function clonepress_duplicate_suffix_render() {
    $options = get_option('clonepress_settings');
    if (!is_array($options)) {
        $options = array('duplicate_post_status' => 'draft', 'duplicate_label' => __('Duplicate'), 'duplicate_redirect' => 'list', 'duplicate_suffix' => ' (Copy)');
    }
    ?>
    <input type="text" name="clonepress_settings[duplicate_suffix]" value="<?php echo esc_attr(isset($options['duplicate_suffix']) ? $options['duplicate_suffix'] : ' (Copy)'); ?>" />
    <?php
}

// Add settings link on plugin page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'clonepress_add_settings_link');

function clonepress_add_settings_link($links) {
    $settings_link = '<a href="options-general.php?page=clonepress">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}