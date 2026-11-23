/**
 * Registers the block.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-registration/
 */
import { registerBlockType } from '@wordpress/blocks';

import './style.scss';

import Edit from './edit';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: Edit,
	/**
	 * Rendering handled server-side by render.php.
	 */
	save: () => null,
} );
