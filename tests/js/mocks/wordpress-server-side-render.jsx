export default function ServerSideRender( { block, attributes } ) {
	return (
		<div
			data-testid="server-side-render"
			data-block={ block }
			data-attributes={ JSON.stringify( attributes ) }
		/>
	);
}
