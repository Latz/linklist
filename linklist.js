const $j = jQuery.noConflict();

/**
 * Submits the bulk-edit LinkList state for the selected posts via AJAX.
 * @listens click#bulk_edit
 */
$j( '#bulk_edit' ).click( function () {
	const $bulk_row = $j( '#bulk-edit' );
	const post_ids = [];

	// get the IDs of the selected posts
	$bulk_row.find( '#bulk-titles' ).children().each( function () {
		post_ids.push( $j( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
	} );

	// get the desired state of linklist
	const linklist_display = $j( '#linklist-bulk-selectbox' ).val();

	// save the data
	$j.ajax( {
		url: ajaxurl,
		type: 'POST',
		async: false,
		cache: false,
		data: {
			action: 'linklist_save_bulk_edit',
			nonce: linklistBulkEdit.nonce,
			post_ids,
			linklist_state: linklist_display,
		},
	} );
} );

const $wp_inline_edit = inlineEditPost.edit;

/**
 * Wraps WordPress's quick-edit handler to sync the LinkList selectbox
 * with the edited post's current LinkList state.
 * @listens inlineEditPost#edit
 */
inlineEditPost.edit = function ( id ) {
	$wp_inline_edit.apply( this, arguments );

	const post_id = typeof id === 'object' ? Number.parseInt( this.getId( id ), 10 ) : 0;
	const linklist_display = `#linklist-${ post_id }`;
	const state = $j( linklist_display ).html();

	$j( '#linklist-selectbox' ).val( state ? 'yes' : 'no' );
};
