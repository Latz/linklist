/**
 * Submits the bulk-edit LinkList state for the selected posts via AJAX.
 * @listens click#bulk_edit
 */
document.getElementById( 'bulk_edit' )?.addEventListener( 'click', function () {
	const bulk_row = document.getElementById( 'bulk-edit' );
	const post_ids = [];

	// get the IDs of the selected posts
	bulk_row.querySelectorAll( '#bulk-titles > *' ).forEach( function ( child ) {
		post_ids.push( child.id.replace( /^(ttle)/i, '' ) );
	} );

	// get the desired state of linklist
	const linklist_display = document.getElementById( 'linklist-bulk-selectbox' ).value;

	// save the data
	const body = new URLSearchParams( {
		action: 'linklist_save_bulk_edit',
		nonce: linklistBulkEdit.nonce,
		linklist_state: linklist_display,
	} );
	post_ids.forEach( ( post_id ) => body.append( 'post_ids[]', post_id ) );

	fetch( ajaxurl, {
		method: 'POST',
		cache: 'no-store',
		body,
	} ).catch( ( error ) => console.error( 'linklist bulk edit failed:', error ) );
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
	const state = document.querySelector( linklist_display )?.innerHTML;

	document.getElementById( 'linklist-selectbox' ).value = state ? 'yes' : 'no';
};
