$j = jQuery.noConflict();


$j( '#bulk_edit' ).click( function() {
    var $bulk_row = $j( '#bulk-edit' );
    var $post_ids = new Array();

    // get the IDS of the selected posts
    $bulk_row.find( '#bulk-titles' ).children().each( function() {
        $post_ids.push( $j( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
    });

    // get the desired state of linklist
    linklist_display = $j('#linklist-selectbox').val();

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

var $wp_inline_edit = inlineEditPost.edit;
inlineEditPost.edit = function( id ) {

    $wp_inline_edit.apply( this, arguments );
    var post_id = 0;
    if ( typeof( id ) == 'object' )
        post_id = parseInt( this.getId( id ) );

    linklist_display = '#linklist-' + post_id;
    state = $j(linklist_display).html();
    if (state)
        $j("#linklist-selectbox").val('yes');
    else
        $j("#linklist-selectbox").val('no');

};