/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * WordPress dependencies
 */
import { Platform } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	InnerBlocks,
	useBlockProps,
	InspectorAdvancedControls,
	InspectorControls,
	__experimentalUseInnerBlocksProps as useInnerBlocksProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import {
	SelectControl,
	PanelBody,
	__experimentalBoxControl as BoxControl,
	__experimentalUnitControl as UnitControl,
	Button,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useInstanceId } from '@wordpress/compose';
const { __Visualizer: BoxControlVisualizer } = BoxControl;

const isWeb = Platform.OS === 'web';

export const CSS_UNITS = [
	{
		value: '%',
		label: isWeb ? '%' : __( 'Percentage (%)' ),
		default: '',
	},
	{
		value: 'px',
		label: isWeb ? 'px' : __( 'Pixels (px)' ),
		default: '',
	},
	{
		value: 'em',
		label: isWeb ? 'em' : __( 'Relative to parent font size (em)' ),
		default: '',
	},
	{
		value: 'rem',
		label: isWeb ? 'rem' : __( 'Relative to root font size (rem)' ),
		default: '',
	},
	{
		value: 'vw',
		label: isWeb ? 'vw' : __( 'Viewport width (vw)' ),
		default: '',
	},
];

function GroupEdit( { attributes, setAttributes, clientId } ) {
	const id = useInstanceId( GroupEdit );
	const { defaultLayout, hasInnerBlocks } = useSelect(
		( select ) => {
			const { getBlock, getSettings } = select( blockEditorStore );
			const block = getBlock( clientId );
			return {
				defaultLayout: getSettings().__experimentalFeatures?.defaults
					?.layout,
				hasInnerBlocks: !! ( block && block.innerBlocks.length ),
			};
		},
		[ clientId ]
	);
	const blockProps = useBlockProps();
	const { tagName: TagName = 'div', templateLock, layout = {} } = attributes;
	const { contentSize, wideSize } = layout;
	// TODO: this shouldn't be based on the values but on a theme.json config.
	const supportsLayout = !! contentSize || !! wideSize;
	const innerBlocksProps = useInnerBlocksProps(
		{
			className: classnames( {
				'wp-block-group__inner-container': ! supportsLayout,
				[ `wp-container-${ id }` ]: contentSize || wideSize,
			} ),
		},
		{
			templateLock,
			renderAppender: hasInnerBlocks
				? undefined
				: InnerBlocks.ButtonBlockAppender,
			__experimentalLayout: {
				type: 'default',
				alignments:
					contentSize || wideSize
						? [ 'wide', 'full' ]
						: [ 'left', 'center', 'right' ],
			},
		}
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Layout settings' ) }>
					{ !! defaultLayout && (
						<Button
							isSecondary
							onClick={ () => {
								setAttributes( {
									layout: {
										...defaultLayout,
									},
								} );
							} }
						>
							{ __( 'Use default layout' ) }
						</Button>
					) }
					<UnitControl
						label={ __( 'Content size' ) }
						labelPosition="edge"
						__unstableInputWidth="80px"
						value={ contentSize || wideSize || '' }
						onChange={ ( nextWidth ) => {
							nextWidth =
								0 > parseFloat( nextWidth ) ? '0' : nextWidth;
							setAttributes( {
								layout: {
									...layout,
									contentSize: nextWidth,
								},
							} );
						} }
						units={ CSS_UNITS }
					/>
					<UnitControl
						label={ __( 'Wide size' ) }
						labelPosition="edge"
						__unstableInputWidth="80px"
						value={ wideSize || contentSize || '' }
						onChange={ ( nextWidth ) => {
							nextWidth =
								0 > parseFloat( nextWidth ) ? '0' : nextWidth;
							setAttributes( {
								layout: {
									...layout,
									wideSize: nextWidth,
								},
							} );
						} }
						units={ CSS_UNITS }
					/>
				</PanelBody>
			</InspectorControls>
			<InspectorAdvancedControls>
				<SelectControl
					label={ __( 'HTML element' ) }
					options={ [
						{ label: __( 'Default (<div>)' ), value: 'div' },
						{ label: '<header>', value: 'header' },
						{ label: '<main>', value: 'main' },
						{ label: '<section>', value: 'section' },
						{ label: '<article>', value: 'article' },
						{ label: '<aside>', value: 'aside' },
						{ label: '<footer>', value: 'footer' },
					] }
					value={ TagName }
					onChange={ ( value ) =>
						setAttributes( { tagName: value } )
					}
				/>
			</InspectorAdvancedControls>
			<TagName { ...blockProps }>
				{ ( wideSize || contentSize ) && (
					<style>
						{ `
							.wp-container-${ id } > * {
								max-width: ${ contentSize ?? wideSize };
								margin-left: auto;
								margin-right: auto;
							}
						
							.wp-container-${ id } > [data-align="wide"] {
								max-width: ${ wideSize ?? contentSize };
							}
						
							.wp-container-${ id } > [data-align="full"] {
								max-width: none;
							}
						` }
					</style>
				) }
				<BoxControlVisualizer
					values={ attributes.style?.spacing?.padding }
					showValues={ attributes.style?.visualizers?.padding }
				/>
				{ /*
				 * When the layout option is supported, the extra div is not necessary,
				 * it's just there because of the extra "BoxControlVisualizer" and "style"
				 * that should be outside the inner blocks container.
				 */ }
				<div { ...innerBlocksProps } />
			</TagName>
		</>
	);
}

export default GroupEdit;
