import { readFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import jQuery from 'jquery';

// linklist.js is a legacy, non-module admin script written for a classic
// <script> tag: it assigns to undeclared globals ($j, linklist_display,
// state) and reads jQuery/ajaxurl/inlineEditPost off the global scope.
// Importing it as an ES module would run it in strict mode and throw on
// those bare assignments, so it's executed via `new Function` instead,
// which mirrors how a browser runs a plain inline script.
const __dirname = path.dirname( fileURLToPath( import.meta.url ) );
const scriptSource = readFileSync(
	path.resolve( __dirname, '../../linklist.js' ),
	'utf8'
);

function runAdminScript( inlineEditPost ) {
	window.jQuery = jQuery;
	window.$ = jQuery;
	window.ajaxurl = 'http://example.test/wp-admin/admin-ajax.php';
	window.inlineEditPost = inlineEditPost;

	const run = new Function( 'jQuery', 'ajaxurl', 'inlineEditPost', scriptSource );
	run( jQuery, window.ajaxurl, inlineEditPost );
}

beforeEach( () => {
	document.body.innerHTML = '';
	vi.restoreAllMocks();
} );

describe( 'bulk edit', () => {
	beforeEach( () => {
		document.body.innerHTML = `
			<div id="bulk-edit">
				<div id="bulk-titles">
					<div id="ttle101">Post 101</div>
					<div id="ttle202">Post 202</div>
				</div>
			</div>
			<select id="linklist-selectbox">
				<option value="yes">Display</option>
				<option value="no">Hide</option>
			</select>
			<button id="bulk_edit">Bulk edit</button>
		`;
	} );

	it( 'sends the selected post ids and linklist state to the ajax handler', () => {
		const ajaxSpy = vi.spyOn( jQuery, 'ajax' ).mockImplementation( () => {} );
		runAdminScript( { edit: vi.fn(), getId: vi.fn() } );

		jQuery( '#linklist-selectbox' ).val( 'yes' );
		document.getElementById( 'bulk_edit' ).click();

		expect( ajaxSpy ).toHaveBeenCalledTimes( 1 );
		expect( ajaxSpy ).toHaveBeenCalledWith(
			expect.objectContaining( {
				url: 'http://example.test/wp-admin/admin-ajax.php',
				type: 'POST',
				data: {
					action: 'linklist_save_bulk_edit',
					post_ids: [ '101', '202' ],
					linklist_state: 'yes',
				},
			} )
		);
	} );

	it( 'reports the hide state when selected', () => {
		const ajaxSpy = vi.spyOn( jQuery, 'ajax' ).mockImplementation( () => {} );
		runAdminScript( { edit: vi.fn(), getId: vi.fn() } );

		jQuery( '#linklist-selectbox' ).val( 'no' );
		document.getElementById( 'bulk_edit' ).click();

		expect( ajaxSpy ).toHaveBeenCalledWith(
			expect.objectContaining( {
				data: expect.objectContaining( { linklist_state: 'no' } ),
			} )
		);
	} );
} );

describe( 'quick edit', () => {
	it( 'wraps inlineEditPost.edit, calls the original handler, and shows "yes" when a linklist marker is present', () => {
		document.body.innerHTML = `
			<div id="linklist-55"><img id="linklist-55" src="check.png"></div>
			<select id="linklist-selectbox">
				<option value="yes">Display</option>
				<option value="no">Hide</option>
			</select>
		`;

		const originalEdit = vi.fn();
		const getId = vi.fn( () => 55 );
		const inlineEditPost = { edit: originalEdit, getId };

		runAdminScript( inlineEditPost );

		const rowObject = {};
		inlineEditPost.edit( rowObject );

		expect( originalEdit ).toHaveBeenCalledWith( rowObject );
		expect( getId ).toHaveBeenCalledWith( rowObject );
		expect( jQuery( '#linklist-selectbox' ).val() ).toBe( 'yes' );
	} );

	it( 'shows "no" when no linklist marker is present for the row', () => {
		document.body.innerHTML = `
			<div id="linklist-55"></div>
			<select id="linklist-selectbox">
				<option value="yes">Display</option>
				<option value="no">Hide</option>
			</select>
		`;

		const inlineEditPost = { edit: vi.fn(), getId: vi.fn( () => 55 ) };
		runAdminScript( inlineEditPost );

		inlineEditPost.edit( {} );

		expect( jQuery( '#linklist-selectbox' ).val() ).toBe( 'no' );
	} );

	it( 'does not compute a post id when called with a non-object id', () => {
		document.body.innerHTML = `
			<div id="linklist-0"><img src="check.png"></div>
			<select id="linklist-selectbox">
				<option value="yes">Display</option>
				<option value="no">Hide</option>
			</select>
		`;

		const getId = vi.fn( () => 999 );
		const inlineEditPost = { edit: vi.fn(), getId };
		runAdminScript( inlineEditPost );

		inlineEditPost.edit( '123' );

		expect( getId ).not.toHaveBeenCalled();
		expect( jQuery( '#linklist-selectbox' ).val() ).toBe( 'yes' );
	} );
} );
