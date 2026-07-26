import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Edit from '../../src/edit.js';

function renderEdit( attributes = {} ) {
	const setAttributes = vi.fn();
	const defaultAttributes = {
		style: '',
		prolog: '',
		sep: '',
		sort: '',
		minlinks: -1,
		...attributes,
	};

	const utils = render(
		<Edit attributes={ defaultAttributes } setAttributes={ setAttributes } />
	);

	return { ...utils, setAttributes };
}

describe( 'Edit', () => {
	it( 'renders the list style, sort order, prolog and minlinks controls', () => {
		renderEdit();

		expect( screen.getByLabelText( 'List style' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Sort order' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Prolog text' ) ).toBeInTheDocument();
		expect( screen.getByLabelText( 'Minimum number of links to show' ) ).toBeInTheDocument();
	} );

	it( 'does not render the separator field by default', () => {
		renderEdit( { style: '' } );

		expect( screen.queryByLabelText( 'Separator' ) ).not.toBeInTheDocument();
	} );

	it( 'renders the separator field only when style is "rbli"', () => {
		renderEdit( { style: 'rbli' } );

		expect( screen.getByLabelText( 'Separator' ) ).toBeInTheDocument();
	} );

	it( 'calls setAttributes with the chosen style', async () => {
		const user = userEvent.setup();
		const { setAttributes } = renderEdit();

		await user.selectOptions( screen.getByLabelText( 'List style' ), 'rbul' );

		expect( setAttributes ).toHaveBeenCalledWith( { style: 'rbul' } );
	} );

	it( 'calls setAttributes with the chosen sort order', async () => {
		const user = userEvent.setup();
		const { setAttributes } = renderEdit();

		await user.selectOptions( screen.getByLabelText( 'Sort order' ), 'off' );

		expect( setAttributes ).toHaveBeenCalledWith( { sort: 'off' } );
	} );

	it( 'calls setAttributes with typed prolog text', async () => {
		const user = userEvent.setup();
		const { setAttributes } = renderEdit();

		await user.type( screen.getByLabelText( 'Prolog text' ), 'Hi' );

		expect( setAttributes ).toHaveBeenCalledWith( { prolog: 'H' } );
		expect( setAttributes ).toHaveBeenCalledWith( { prolog: 'i' } );
	} );

	it( 'shows an empty minlinks field when the value is the -1 sentinel', () => {
		renderEdit( { minlinks: -1 } );

		expect( screen.getByLabelText( 'Minimum number of links to show' ).value ).toBe( '' );
	} );

	it( 'shows the stored numeric minlinks value', () => {
		renderEdit( { minlinks: 3 } );

		expect( screen.getByLabelText( 'Minimum number of links to show' ).value ).toBe( '3' );
	} );

	it( 'maps an emptied minlinks field back to the -1 sentinel', async () => {
		const user = userEvent.setup();
		const { setAttributes } = renderEdit( { minlinks: 3 } );

		await user.clear( screen.getByLabelText( 'Minimum number of links to show' ) );

		expect( setAttributes ).toHaveBeenCalledWith( { minlinks: -1 } );
	} );

	it( 'maps a typed minlinks value to a number', async () => {
		const user = userEvent.setup();
		const { setAttributes } = renderEdit( { minlinks: -1 } );

		await user.type( screen.getByLabelText( 'Minimum number of links to show' ), '5' );

		expect( setAttributes ).toHaveBeenCalledWith( { minlinks: 5 } );
	} );

	it( 'passes the block name and attributes through to the server-side preview', () => {
		renderEdit( { style: 'rbol', prolog: 'Custom:' } );

		const preview = screen.getByTestId( 'server-side-render' );
		expect( preview ).toHaveAttribute( 'data-block', 'linklist/linklist' );
		expect( JSON.parse( preview.getAttribute( 'data-attributes' ) ) ).toMatchObject( {
			style: 'rbol',
			prolog: 'Custom:',
		} );
	} );
} );
