/**
 * External dependencies
 */
import renderer from 'react-test-renderer';

/**
 * WordPress dependencies
 */
// import { RegistryProvider, createRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import Controls from '../controls';

jest.mock( '@wordpress/compose', () => ( {
	...jest.requireActual( '@wordpress/compose' ),
	withPreferredColorScheme: jest.fn( ( Component ) => () => (
		<Component
			preferredColorScheme={ {} }
			getStylesFromColorScheme={ jest.fn( () => ( {} ) ) }
		/>
	) ),
} ) );
jest.mock( '@react-navigation/core' );

const setAttributes = jest.fn();
const didUploadFail = jest.fn();
const isUploadInProgress = jest.fn();
const onClearMedia = jest.fn();
const onSelectMedia = jest.fn();
const hasOnlyColorBackground = false;
const openMediaOptionsRef = { current: jest.fn( () => {} ) };

describe( 'Cover block edit controls', () => {
	it( 'renders without crashing', () => {
		const output = renderer.create(
			<Controls
				attributes={ {} }
				didUploadFail={ didUploadFail }
				hasOnlyColorBackground={ hasOnlyColorBackground }
				isUploadInProgress={ isUploadInProgress }
				onClearMedia={ onClearMedia }
				onSelectMedia={ onSelectMedia }
				openMediaOptionsRef={ openMediaOptionsRef }
				setAttributes={ setAttributes }
			/>
		);
		const json = output.toJSON();
		expect( json ).toBeTruthy();
	} );
} );
