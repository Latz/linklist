// Minimal stand-ins for the @wordpress/* packages that the real WordPress
// block editor provides as externals (window.wp.*) rather than npm
// packages. These are not installed as dependencies, so tests mock them
// with just enough DOM behaviour to exercise src/edit.js.

export function PanelBody( { title, children } ) {
	return (
		<div data-testid="panel-body">
			<h2>{ title }</h2>
			{ children }
		</div>
	);
}

export function SelectControl( { label, value, options, onChange } ) {
	return (
		<label>
			{ label }
			<select
				aria-label={ label }
				value={ value }
				onChange={ ( event ) => onChange( event.target.value ) }
			>
				{ options.map( ( option ) => (
					<option key={ option.value } value={ option.value }>
						{ option.label }
					</option>
				) ) }
			</select>
		</label>
	);
}

export function TextControl( { label, value, placeholder, type, min, onChange } ) {
	return (
		<label>
			{ label }
			<input
				aria-label={ label }
				type={ type || 'text' }
				min={ min }
				value={ value }
				placeholder={ placeholder }
				onChange={ ( event ) => onChange( event.target.value ) }
			/>
		</label>
	);
}
