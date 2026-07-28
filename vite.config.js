import { fileURLToPath, URL } from 'node:url';
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const mocksDir = ( name ) => fileURLToPath( new URL( `./tests/js/mocks/${ name }`, import.meta.url ) );

// Test-only config (Vitest). Production builds still go through
// `wp-scripts build`, which externalizes @wordpress/* packages against the
// WordPress `wp` global instead of npm packages — they aren't installed
// here, so tests alias them to lightweight local stand-ins.
export default defineConfig( {
	plugins: [ react() ],
	resolve: {
		alias: {
			'@wordpress/blocks': mocksDir( 'wordpress-blocks.js' ),
			'@wordpress/block-editor': mocksDir( 'wordpress-block-editor.jsx' ),
			'@wordpress/components': mocksDir( 'wordpress-components.jsx' ),
			'@wordpress/data': mocksDir( 'wordpress-data.js' ),
			'@wordpress/server-side-render': mocksDir( 'wordpress-server-side-render.jsx' ),
		},
	},
	// src/edit.js contains JSX but keeps the .js extension (WordPress block
	// convention); esbuild only auto-detects JSX for .jsx/.tsx, so force the
	// jsx loader for every .js/.jsx file (the jsx loader parses plain JS fine
	// too, so this is safe project-wide — there's no TypeScript here).
	esbuild: {
		loader: 'jsx',
		// Vite's built-in esbuild plugin only matches .ts/.jsx/.tsx by default
		// (assuming plain .js never needs transforming) and explicitly
		// excludes .js; src/edit.js needs both overridden to get JSX support.
		include: /\.[jt]sx?$/,
		exclude: [],
	},
	optimizeDeps: {
		esbuildOptions: {
			loader: { '.js': 'jsx' },
		},
	},
	test: {
		environment: 'jsdom',
		setupFiles: [ './tests/js/setup.js' ],
		include: [ 'tests/js/**/*.test.{js,jsx}' ],
		globals: false,
		coverage: {
			provider: 'v8',
			reporter: [ 'lcov', 'text' ],
			reportsDirectory: 'bin/reports/coverage-js',
		},
	},
} );
