/* global jQuery */

( function ($) {
	'use strict';


    /**
     * Globals
     */
    var thumb_modal;
    var $setLink;
    var $setLinkParent;
    var imgHtml;


	/**
	 *  Get the HTML for the post thumbnail
	 *
	 *  @since 1.0.0
	 *
	 *  @var object image Image data
	 */
	function fitwoBuildThumbnailHtml( image ){

		var imgHtml = '';

		if( "undefined" != typeof image ){
            imgHtml = $('<img />').attr({
                'id'   : "term-img-" + image.id,
                'src'  : image.url,
                'class': 'term-feat-img',
                'data-term-img': image.id,
				'style': 'max-width: 100%; height: auto;'
            });
		};

		return imgHtml;
	}


	/**
	 *  Ajax callback for generating thumbnail HTML
	 *
	 *  @since 1.0.0
	 *
	 *  @param object     image Image select in the media manager.
	 *  @param DOM object parent Meta box that holds the thumbnail.
	 */
	function fittwoAjaxBuildThumbnailHtml( image, parent ){

		var requestData = {
			action   : 'fitwo-get-post-thumbnail-html',
			thumbnail_id : image.id,
			post_id     : null,
		};

		wp.ajax.send( requestData.action, {
			success: function( html ){
				parent.html( html );
			},
			error  : function(){
				alert( 'there was an error setting the thumnail' );
			},
			data   : requestData
		});

	}


	/**
	 *  Selecting an image
	 *
	 *  @since 1.0.0
	 */
	$( '#postimage2div' ).on( 'click', '.fitwo-set-thumbnail', function ( e ) {

		var $setLink = $( e.currentTarget ),
			$setLinkParent = $setLink.closest( '.inside' );

		e.preventDefault();

        // Open the modal
        if ( thumb_modal ) {
            thumb_modal.open();
            return;
        }

        // Create the media frame
        thumb_modal = wp.media.frames.thumb_modal = wp.media({
            title: $setLink.data('choose'),
            library: {type: 'image'},
            button: {text: $setLink.data('update')},
            multiple: false
        });

		// Picking an image
        thumb_modal.on('select', function () {

            // Get the image
            var image = thumb_modal.state().get( 'selection' ).first().toJSON();

			// build the thumbnail image
			fittwoAjaxBuildThumbnailHtml( image, $setLinkParent );

		});

        // Open the modal
        thumb_modal.open();

	});


	/**
	 *  Deleting an image
	 *
	 *  @since 1.0.0
	 */
	$( '#postimage2div' ).on( 'click', '.fitwo-del-thumbnail', function ( e ) {

		var $setLink = $( e.currentTarget ),
			$setLinkParent = $setLink.closest( '.inside' ),
			image = {}
			image.id = '-1';

		e.preventDefault();

		fittwoAjaxBuildThumbnailHtml( image, $setLinkParent );

	});


})(jQuery);