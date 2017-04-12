<?php
/**
 *  Featured Image II
 *
 *  @package Featured_Image_II
 *
 *  @license     http://www.gnu.org/licenses/gpl-2.0.txt GPL-2.0+
 *  @version     1.0.0
 *
 *  Plugin Name: Featured Image II
 *  Plugin URI:
 *  Description: Adds a secondary Featured Image meta box to the post editor.
 *  Version:     1.0.0
 *  Author:      Darrin Boutote
 *  Author URI:  http://darrinb.com
 *  Text Domain:
 *  Domain Path:
 *  License:     GPL-2.0+
 *  License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }


// load js
function fi2_enqueue_admin_scripts( $hook ){

	if( 'post.php' == $hook || 'post-new.php' === $hook ){
		wp_enqueue_media();
		wp_enqueue_script( 'fi2-images', plugins_url( '/js/admin.js', __FILE__ ), '', '', true );
	}
}
add_action( 'admin_enqueue_scripts',  'fi2_enqueue_admin_scripts' );


/**
 *  Register the meta box
 *
 *  @since 1.0.0
 */
function fi2_register_meta_box(){
	
	$screen = get_current_screen();
	
	$add_meta_box = apply_filters( 'fi2_register_meta_box', true, $screen );
	
	if( $add_meta_box ){
		add_meta_box('postimage2div', esc_html( 'Second Featured Image' ), 'fitwo_inner_meta', null, 'side', 'low' );
	}
	
}
add_action( 'add_meta_boxes', 'fi2_register_meta_box' );


/**
 * Display post thumbnail meta box.
 *
 * @since 1.0.0
 *
 * @param WP_Post $post A post object.
 */
function fitwo_inner_meta( $post ){
	$thumbnail_id = get_post_meta( $post->ID, '_fitwo_thumbnail_id', true );
	echo fitwo_mb_thumbnail_html( $thumbnail_id, $post->ID );
}


/**
 *  Output thumbnail meta box
 *
 *  @since 1.0.0
 *
 *  @param int   $thumbnail_id ID of the attachment used for thumbnail
 *  @param mixed $post         The post ID or object associated with the thumbnail, defaults to global $post.
 *
 *  @return string html
 */
function fitwo_mb_thumbnail_html( $thumbnail_id = null, $post = null ){

	$content = '';


	$content .= wp_nonce_field(
		basename( __FILE__ ),
		"fitwo-box-nonce",
		true,
		false
		);

	$thumbnail_html = __( 'Set image' );

	if ( $thumbnail_id && get_post( $thumbnail_id ) ) {
		$size = isset( $_wp_additional_image_sizes['post-thumbnail'] ) ? 'post-thumbnail' : array( 266, 266 );
		$size = apply_filters( 'admin_post_thumbnail_size', $size, $thumbnail_id, $post );
		$thumbnail_html = wp_get_attachment_image( $thumbnail_id, $size );
	}

	$content .= '<style type="text/css">
		.fitwo-set-thumbnail { display: inline-block; max-width: 100%; }
		.fitwo-set-thumbnail img {
			background-image: -webkit-linear-gradient(45deg, #c4c4c4 25%, transparent 25%, transparent 75%, #c4c4c4 75%, #c4c4c4), -webkit-linear-gradient(45deg, #c4c4c4 25%, transparent 25%, transparent 75%, #c4c4c4 75%, #c4c4c4);
			background-image: linear-gradient(45deg, #c4c4c4 25%, transparent 25%, transparent 75%, #c4c4c4 75%, #c4c4c4), linear-gradient(45deg, #c4c4c4 25%, transparent 25%, transparent 75%, #c4c4c4 75%, #c4c4c4);
			background-position: 0 0, 10px 10px;
			-webkit-background-size: 20px 20px;
			background-size: 20px 20px;
			height: auto;
			max-width: 100%;
			vertical-align: top;
			width: auto;
		}
	</style>';

	$content .= sprintf( '<p class="hide-if-no-js"><a title="%1$s" href="#" id="%2$s" data-update="%1$s" data-choose="%3$s" data-delete="%4$s" class="fitwo-set-thumbnail">%5$s</a></p>',
		'Set secondary featured image',
		'fitwo-set-thumbnail',
		'Featured Image',
		'Remove featured image',
		$thumbnail_html
	);

	if( $thumbnail_id ){
		$content .= '<p class="hide-if-no-js howto"">' . __( 'Click the image to edit or update' ) . '</p>';
		$content .= sprintf( '<p class="hide-if-no-js"><a title="%1$s" href="#" id="%2$s" class="fitwo-del-thumbnail">%3$s</a></p>',
			'Remove featured image',
			'fitwo-del-thumbnail',
			__( 'Remove featured image' )
		);
	}

	$content .= sprintf( '<input type="hidden" name="_fitwo_thumbnail_id" id="fitwo-thumbnail-id" value="%s" />',
		esc_attr( $thumbnail_id ? $thumbnail_id : '-1' )
	);

	return $content;

}


/**
 *  Ajax callback for generating thumbnail HTML
 *
 *  @uses fitwo_mb_thumbnail_html();
 *  @uses wp_send_json_success();
 *
 *  @since 1.0.0
 */
function fitwo_ajax_mb_thumbnail_html(){

	$post_id = ( ! empty( $_POST['post_id'] ) ) ? intval( $_POST['post_id'] ) : 0 ;
	$thumbnail_id = ( ! empty( $_POST['thumbnail_id'] ) ) ? intval( $_POST['thumbnail_id'] ) : 0 ;

	// For backward compatibility, -1 refers to no featured image.
	if ( -1 === $thumbnail_id ) {
		$thumbnail_id = null;
	}

	$return = fitwo_mb_thumbnail_html( $thumbnail_id, $post_id );

	wp_send_json_success( $return );
}
add_action( 'wp_ajax_nopriv_fitwo-get-post-thumbnail-html', 'fitwo_ajax_mb_thumbnail_html', 0, 2 );
add_action( 'wp_ajax_fitwo-get-post-thumbnail-html', 'fitwo_ajax_mb_thumbnail_html', 0, 2 );


/**
 *  Save the stored meta data
 *
 *  @since 1.0.0
 *
 */
function fitwo_save_meta_box( $post_id, $post ){

	if( ! get_post( $post_id ) ){
		return $post_id;
	}

	if( 'auto-draft' === $post->post_status || 'revision' == $post->post_type ){
		return $post_id;
	}

	/* skip auto-running jobs */
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return $post_id; }
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ){ return $post_id; }
	if ( defined( 'DOING_CRON' ) && DOING_CRON ){ return $post_id; }

    if ( ! isset( $_POST["fitwo-box-nonce"] ) || ! wp_verify_nonce( $_POST["fitwo-box-nonce"], basename( __FILE__ ) ) ){
		return $post_id;
	}

	/* check if user can edit */
	$ptype = get_post_type_object( $post->post_type );
	if ( ! current_user_can( $ptype->cap->edit_posts ) ) {
		return $post_id;
	}

	if( ! empty( $_POST['_fitwo_thumbnail_id'] ) ) :
		if( '-1' ===  $_POST['_fitwo_thumbnail_id'] ){
			delete_post_meta( $post_id, "_fitwo_thumbnail_id" );
		} else {
			update_post_meta( $post_id, "_fitwo_thumbnail_id", intval( $_POST['_fitwo_thumbnail_id'] ) );
		}
	endif;

}
add_action( 'save_post', 'fitwo_save_meta_box', 10, 2 );