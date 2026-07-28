import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import ServerSideRender from '@wordpress/server-side-render';

// Attribute keys that change via continuous typing (a keystroke storm worth
// coalescing); style/sort come from SelectControls that fire one onChange
// per click, so debouncing them just adds a pointless delay before the
// preview reflects a discrete choice.
const DEBOUNCED_PREVIEW_KEYS = new Set( [ 'prolog', 'sep', 'minlinks' ] );

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

// "Last page only" only has an effect on pages split with <!--nextpage-->
// (PageLinkList); it's a no-op for posts, so the control is hidden there.
const LASTPAGE_OPTIONS = [
	{ label: __( 'Use default', 'linklist' ), value: '' },
	{ label: __( 'Show only on the last page', 'linklist' ), value: 'on' },
	{ label: __( 'Show on every page', 'linklist' ), value: 'off' },
];

/**
 * ServerSideRender re-fetches from the REST API on every attribute change,
 * so typing into prolog/separator/minlinks would otherwise trigger a server
 * round-trip per keystroke. Debounces the attributes it renders from,
 * independently of setAttributes, so the input fields themselves stay
 * immediately responsive -- but only for keys that actually change via
 * typing; a style/sort switch updates the preview right away, with nothing
 * to coalesce.
 *
 * @param {Object} attributes Current block attributes.
 * @return {Object} The (possibly debounced) attributes to render the preview from.
 */
function usePreviewAttributes( attributes ) {
	const [ previewAttributes, setPreviewAttributes ] = useState( attributes );
	const debouncedSetPreviewAttributes = useDebounce( setPreviewAttributes, 300 );
	const previousAttributesRef = useRef( attributes );

	useEffect( () => {
		const previous = previousAttributesRef.current;
		previousAttributesRef.current = attributes;

		const changedKeys = Object.keys( attributes ).filter(
			( key ) => attributes[ key ] !== previous[ key ]
		);
		const shouldDebounce =
			changedKeys.length > 0 &&
			changedKeys.every( ( key ) => DEBOUNCED_PREVIEW_KEYS.has( key ) );

		if ( shouldDebounce ) {
			debouncedSetPreviewAttributes( attributes );
		} else {
			debouncedSetPreviewAttributes.cancel();
			setPreviewAttributes( attributes );
		}
	}, [ attributes, debouncedSetPreviewAttributes ] );

	return previewAttributes;
}

/**
 * Separator field for the 'char separated' (rbli) list style; hidden for
 * every other style since it has no effect.
 *
 * @param {Object} props
 * @param {string} props.style
 * @param {string} props.sep
 * @param {Function} props.setAttributes
 */
function SeparatorControl( { style, sep, setAttributes } ) {
	if ( style !== 'rbli' ) {
		return null;
	}

	return (
		<TextControl
			label={ __( 'Separator', 'linklist' ) }
			value={ sep }
			placeholder={ __( 'Use default', 'linklist' ) }
			onChange={ ( value ) => setAttributes( { sep: value } ) }
		/>
	);
}

/**
 * "Last page only" control; only meaningful on pages split with
 * <!--nextpage-->, so it's hidden on posts.
 *
 * @param {Object} props
 * @param {string} props.postType
 * @param {string} props.lastpage
 * @param {Function} props.setAttributes
 */
function LastPageControl( { postType, lastpage, setAttributes } ) {
	if ( postType !== 'page' ) {
		return null;
	}

	return (
		<SelectControl
			label={ __( 'Last page only', 'linklist' ) }
			value={ lastpage }
			options={ LASTPAGE_OPTIONS }
			onChange={ ( value ) => setAttributes( { lastpage: value } ) }
		/>
	);
}

/**
 * Editor UI for the Link List block: per-block overrides in the sidebar,
 * plus a live server-rendered preview reusing the existing PHP renderer.
 *
 * @param {Object} props
 * @param {Object} props.attributes
 * @param {Function} props.setAttributes
 */
export default function Edit( { attributes, setAttributes } ) {
	const { style, prolog, sep, sort, minlinks, lastpage } = attributes;
	const blockProps = useBlockProps();
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);

	const previewAttributes = usePreviewAttributes( attributes );

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
					<SeparatorControl style={ style } sep={ sep } setAttributes={ setAttributes } />
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
					<LastPageControl postType={ postType } lastpage={ lastpage } setAttributes={ setAttributes } />
				</PanelBody>
			</InspectorControls>
			<ServerSideRender block="linklist/linklist" attributes={ previewAttributes } />
		</div>
	);
}
