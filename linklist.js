const $j = jQuery.noConflict();


$j( '#bulk_edit' ).click( function() {
    const $bulk_row = $j( '#bulk-edit' );
    const $post_ids = new Array();

    // get the IDS of the selected posts
    $bulk_row.find( '#bulk-titles' ).children().each( function() {
        $post_ids.push( $j( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
    });

    // get the desired state of linklist
    const linklist_display = $j('#linklist-bulk-selectbox').val();

    // save the data
    $j.ajax({
        url: ajaxurl,
        type: 'POST',
        async: false,
        cache: false,
        data: {
            action: 'linklist_save_bulk_edit',
            post_ids: $post_ids,
            linklist_state: linklist_display
        }
    });

});

const $wp_inline_edit = inlineEditPost.edit;
inlineEditPost.edit = function( id ) {

    $wp_inline_edit.apply( this, arguments );
    let post_id = 0;
    if ( typeof( id ) == 'object' )
        post_id = Number.parseInt( this.getId( id ) );

    const linklist_display = '#linklist-' + post_id;
    const state = $j(linklist_display).html();
    if (state)
        $j("#linklist-selectbox").val('yes');
    else
        $j("#linklist-selectbox").val('no');

};