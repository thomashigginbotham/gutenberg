/**
 * WordPress dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
	const { tagName: Tag, layout = {} } = attributes;
	const { contentSize, wideSize } = layout;
	// TODO: this shouldn't be based on the values but on a theme.json config.
	const supportsLayout = !! contentSize || !! wideSize;
	if ( supportsLayout ) {
		return (
			<Tag { ...useBlockProps.save() }>
				<InnerBlocks.Content />
			</Tag>
		);
	}

	return (
		<Tag { ...useBlockProps.save() }>
			<div className="wp-block-group__inner-container">
				<InnerBlocks.Content />
			</div>
		</Tag>
	);
}
