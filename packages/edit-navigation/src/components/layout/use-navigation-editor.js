/**
 * WordPress dependencies
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { useState, useEffect } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { store as editNavigationStore } from '../../store';
import { store as noticesStore } from '@wordpress/notices';
import { __ } from '@wordpress/i18n';

export default function useNavigationEditor() {
	const [ hasFinishedInitialLoad, setHasFinishedInitialLoad ] = useState(
		false
	);
	const [ selectedMenuId, setSelectedMenuId ] = useState( null );
	const [ isMenuDeleted, setIsMenuDeleted ] = useState( false );
	const isMenuBeingDeleted = useSelect(
		( select ) =>
			select( 'core' ).isDeletingEntityRecord(
				'root',
				'menu',
				selectedMenuId
			),
		[ selectedMenuId ]
	);

	const { menus, hasLoadedMenus } = useSelect( ( select ) => {
		const selectors = select( 'core' );
		const params = { per_page: -1 };
		return {
			menus: selectors.getMenus( params ),
			hasLoadedMenus: selectors.hasFinishedResolution( 'getMenus', [
				params,
			] ),
		};
	}, [] );

	const { createErrorNotice, createInfoNotice } = useDispatch( noticesStore );

	useEffect( () => {
		if ( hasLoadedMenus ) {
			setHasFinishedInitialLoad( true );
		}
	}, [ hasLoadedMenus ] );

	useEffect( () => {
		if ( ! selectedMenuId && menus?.length ) {
			setSelectedMenuId( menus[ 0 ].id );
		}
	}, [ selectedMenuId, menus ] );

	const navigationPost = useSelect(
		( select ) => {
			if ( ! selectedMenuId ) {
				return;
			}
			return select( editNavigationStore ).getNavigationPostForMenu(
				selectedMenuId
			);
		},
		[ selectedMenuId ]
	);

	const selectMenu = ( menuId ) => {
		setIsMenuDeleted( false );
		setSelectedMenuId( menuId );
	};

	const { deleteMenu: _deleteMenu } = useDispatch( 'core' );

	const deleteMenu = async () => {
		const didDeleteMenu = await _deleteMenu( selectedMenuId, {
			force: true,
		} );
		if ( didDeleteMenu ) {
			setSelectedMenuId( null );
			createInfoNotice( __( 'Menu deleted' ), {
				type: 'snackbar',
				isDismissible: true,
			} );
			setIsMenuDeleted( true );
		} else {
			createErrorNotice( __( 'Menu deletion unsuccessful' ) );
		}
	};

	return {
		menus,
		hasLoadedMenus,
		hasFinishedInitialLoad,
		selectedMenuId,
		navigationPost,
		isMenuBeingDeleted,
		selectMenu,
		deleteMenu,
		isMenuDeleted,
		setIsMenuDeleted,
	};
}
