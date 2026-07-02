<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package new_blog
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function new_blog_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	// Adds a class of no-sidebar when there is no sidebar present.
	if ( ! is_active_sidebar( 'sidebar-1' ) ) {
		$classes[] = 'no-sidebar';
	}

	return $classes;
}
add_filter( 'body_class', 'new_blog_body_classes' );

/**
 * Add a pingback url auto-discovery header for single posts, pages, or attachments.
 */
function new_blog_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
	}
}
add_action( 'wp_head', 'new_blog_pingback_header' );

function new_blog_editor_styles() {

	$classic_editor_styles = array(
		'/css/editor-style.css',
	);

	add_editor_style( $classic_editor_styles );

}

add_action( 'init', 'new_blog_editor_styles' );

function new_blog_modal_popup()
{
    $postId         = isset( $_POST['postID']  ) ?  $_POST['postID'] : 0;
    $modalHeader    = '<a class="btn btn-primary" href="'.esc_url(get_the_permalink( $postId ) ).'"><i class="fa fa-arrows-alt" aria-hidden="true"></i></a>';
    $content_post   = get_post($postId);
    $modalBody      = $content_post->post_content;
    $modalFooter    = '<button type="button" class="close" data-dismiss="modal">&times;</button>';
    $return     = [
        'modalHeader'   => $modalHeader,
        'modalBody'     => $modalBody,
        'modalFooter'   => $modalFooter
    ];
    return wp_send_json($return);
    die();
}
add_action('wp_ajax_nopriv_new_blog_modal_popup', 'new_blog_modal_popup');
add_action('wp_ajax_new_blog_modal_popup', 'new_blog_modal_popup');