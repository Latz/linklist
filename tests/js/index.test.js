import { describe, it, beforeEach, expect } from 'vitest';
import { registerBlockType } from './mocks/wordpress-blocks.js';
import metadata from '../../src/block.json';

beforeEach( () => {
	registerBlockType.mockClear();
} );

describe( 'block registration', () => {
	it( 'registers the linklist/linklist block with an edit implementation and a null save', async () => {
		await import( '../../src/index.js' );

		expect( registerBlockType ).toHaveBeenCalledTimes( 1 );

		const [ name, settings ] = registerBlockType.mock.calls[ 0 ];

		expect( name ).toBe( metadata.name );
		expect( typeof settings.edit ).toBe( 'function' );
		expect( settings.save() ).toBeNull();
	} );
} );
