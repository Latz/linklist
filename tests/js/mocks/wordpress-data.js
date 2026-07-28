import { vi } from 'vitest';

let mockPostType = 'post';

/**
 * Test-only hook to control the post type useSelect() resolves to.
 *
 * @param {string} postType Post type slug ('post', 'page', ...).
 */
export function __setMockPostType( postType ) {
	mockPostType = postType;
}

export const useSelect = vi.fn( ( mapSelect ) =>
	mapSelect( ( storeName ) =>
		storeName === 'core/editor'
			? { getCurrentPostType: () => mockPostType }
			: {}
	)
);
