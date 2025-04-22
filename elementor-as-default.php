<?php

/**
 * Plugin Name: Elementor as Default Editor
 * Description: Make Elementor the default editor for all pages and posts.
 * Version: 1.0
 * Plugin URI: https://github.com/ngearing/Elementor-as-default
 * Author: Ngearing
 * Author URI: https://github.com/ngearing
 * Text Domain: ead
 * Plugins: elementor
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


/**
 * Check if Elementor is active.
 *
 * @return bool
 */
function ead_elementor_active( $post = null ) {
    if ( ! class_exists( 'Elementor\Plugin' ) ) {
        return false;
    }

    if ( is_int( $post ) ) {
        $post = get_post( $post );
    }

    $document = \Elementor\Plugin::$instance->documents->get( $post->ID );

    if ( $document && $document->is_built_with_elementor() && $document->is_editable_by_current_user() ) {
        return true;
    }

    return false;
}


/**
 * Get the post types that should be editable with Elementor.
 *
 * @return array
 */
function ead_get_editable_post_types() {
    return array(
        'page',
        'post',
        'elementor_library',
    );
}


/**
 * Check if user is on edit.php page.
 * 
 * @return bool
 */
function ead_is_edit_page() {
    global $pagenow;

    return ( 'edit.php' === $pagenow );
}


/**
 * Check if post type is editable.
 * 
 * @return bool
 */
function ead_is_editable_post_type( $post ) {
    return in_array( get_post_type( $post ), ead_get_editable_post_types(), true );
}


/**
 * Change the 'Edit' link to Elementor editor.
 * 
 * @param int|WP_Post $post    Optional. Post ID or post object. Default is the global `$post`.
 * @return string|null The edit post link for the given post. Null if the post type does not exist or does not allow an editing UI.
 */
function ead_change_edit_link( $link, $post ) {
    if ( 
        ! ead_elementor_active($post) 
        || ! ead_is_edit_page() 
        || ! ead_is_editable_post_type( $post ) 
    ) {
        return $link;
    }

    return admin_url( 'post.php?post=' . $post . '&action=elementor' );
}
add_filter( 'get_edit_post_link', 'ead_change_edit_link', 20, 2 );


/**
 * Change the Edit with Elementor link to Edit with Wordpress.
 * 
 * @param string[] $actions An array of row action links. Defaults are
 *                          'Edit', 'Quick Edit', 'Restore', 'Trash',
 *                          'Delete Permanently', 'Preview', and 'View'.
 * @param WP_Post  $post    The post object.
 * 
 * @return string[] The modified array of row action links.
 */
function ead_change_elementor_edit_link( $actions, $post ) {
    if ( 
        ! ead_elementor_active( $post ) 
        || ! ead_is_edit_page() 
        || ! ead_is_editable_post_type( $post ) 
    ) {
        return $actions;
    }

    // Remove the Edit with Elementor link. If not admin.
    if ( ! current_user_can( 'activate_plugins', $post->ID ) ) {
        unset( $actions['edit_with_elementor'] );
    }

    if ( isset($actions['edit_with_elementor'])) {
        // Change the Edit with Elementor link to Edit with WordPress.
        $actions['edit_with_elementor'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				admin_url('post.php?post=' . $post->ID . '&action=edit'),
				__('Edit with WordPress', 'ead')
			);
    }

    return $actions;
}
add_filter('page_row_actions', 'ead_change_elementor_edit_link', 20, 2);
add_filter('post_row_actions', 'ead_change_elementor_edit_link', 20, 2);


/**
 * Change Exit to Dashboard link to All posts.
 */
function ead_change_dashboard_link( $url, $doc ) {
    return $doc->get_all_post_type_url();
}
add_filter( 'elementor/document/urls/exit_to_dashboard', 'ead_change_dashboard_link', 20, 2 );
