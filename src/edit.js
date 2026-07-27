import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useEffect, useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

const STYLE_OPTIONS = [
	{ label: __( 'Use default', 'linklist' ), value: '' },
	{ label: __( 'Unordered list', 'linklist' ), value: 'rbul' },
	{ label: __( 'Ordered list', 'linklist' ), value: 'rbol' },
	{ label: __( 'Inline, separated', 'linklist' ), value: 'rbli' },
];

const SORT_OPTIONS = [
	{ label: __( 'Use default', 'linklist' ), value: '' },
	{ label: __( 'Sort alphabetically', 'linklist' ), value: 'on' },
	{ label: __( 'Keep original order', 'linklist' ), value: 'off' },
];

/**
 * Editor UI for the Link List block: per-block overrides in the sidebar,
 * plus a live server-rendered preview reusing the existing PHP renderer.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {Function} props.setAttributes
 */
export default function Edit( { attributes, setAttributes } ) {
	const { style, prolog, sep, sort, minlinks } = attributes;
	const blockProps = useBlockProps();

	// ServerSideRender re-fetches from the REST API on every attribute
	// change, so typing into prolog/separator/minlinks would otherwise
	// trigger a server round-trip per keystroke. Debounce the attributes
	// it renders from, independently of setAttributes, so the input
	// fields themselves stay immediately responsive.
	const [ previewAttributes, setPreviewAttributes ] = useState( attributes );
	const debouncedSetPreviewAttributes = useDebounce( setPreviewAttributes, 300 );

	useEffect( () => {
		debouncedSetPreviewAttributes( attributes );
	}, [ attributes, debouncedSetPreviewAttributes ] );

	return (
		<div { ...blockProps }>
			<InspectorControls>
				<PanelBody title={ __( 'Link List settings', 'linklist' ) }>
					<SelectControl
						label={ __( 'List style', 'linklist' ) }
						value={ style }
						options={ STYLE_OPTIONS }
						onChange={ ( value ) => setAttributes( { style: value } ) }
					/>
					{ style === 'rbli' && (
						<TextControl
							label={ __( 'Separator', 'linklist' ) }
							value={ sep }
							placeholder={ __( 'Use default', 'linklist' ) }
							onChange={ ( value ) => setAttributes( { sep: value } ) }
						/>
					) }
					<SelectControl
						label={ __( 'Sort order', 'linklist' ) }
						value={ sort }
						options={ SORT_OPTIONS }
						onChange={ ( value ) => setAttributes( { sort: value } ) }
					/>
					<TextControl
						label={ __( 'Prolog text', 'linklist' ) }
						value={ prolog }
						placeholder={ __( 'Use default', 'linklist' ) }
						onChange={ ( value ) => setAttributes( { prolog: value } ) }
					/>
					<TextControl
						label={ __( 'Minimum number of links to show', 'linklist' ) }
						type="number"
						min={ 0 }
						value={ minlinks < 0 ? '' : minlinks }
						placeholder={ __( 'Use default', 'linklist' ) }
						onChange={ ( value ) =>
							setAttributes( { minlinks: value === '' ? -1 : Number( value ) } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block="linklist/linklist" attributes={ previewAttributes } />
		</div>
	);
}
