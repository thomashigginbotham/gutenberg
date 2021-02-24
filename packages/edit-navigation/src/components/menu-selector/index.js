/**
 * WordPress dependencies
 */
import {
	MenuGroup,
	MenuItemsChoice
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function MenuSelector( { onSelectMenu, menus } ) {
	return (
		<div className="edit-navigation-menu-selector">
			<h3 className="edit-navigation-menu-selector__header">
				{ __( 'Choose a menu to edit: ' ) }
			</h3>
			<div className="edit-navigation-menu-selector__body">
				<MenuGroup>
					<MenuItemsChoice
						onSelect={ onSelectMenu }
						choices={ menus.map( ( menu ) => ( {
							value: menu.id,
							label: menu.name,
						} ) ) }
					/>
				</MenuGroup>
			</div>
		</div>
	);
}
