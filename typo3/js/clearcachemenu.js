/***************************************************************
*  Copyright notice
*
*  (c) 2007-2011 Ingo Renner <ingo@typo3.org>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * class to handle the clear cache menu
 */
var TYPO3BackendClearCacheMenu = {

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		jQuery(window).resize(function() {
			TYPO3BackendToolbarManager.positionMenu('clear-cache-actions-menu');
			}
		);
		TYPO3BackendToolbarManager.positionMenu('clear-cache-actions-menu');

		jQuery('#clear-cache-actions-menu').click(function(){
			TYPO3BackendClearCacheMenu.toggleMenu();
		});

			// observe all clicks on clear cache actions in the menu
		jQuery('#clear-cache-actions-menu li a').each(function(element) {
			jQuery(this).data('href', jQuery(this).attr('href'));
			jQuery(this).attr('href','javascript:void(0);');
			jQuery(this).click(function() {
				TYPO3BackendClearCacheMenu.clearCache(jQuery(this));
			});
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = jQuery('#clear-cache-actions-menu > a').first();
		var menu        = jQuery('#clear-cache-actions-menu .toolbar-item-menu').first();
		toolbarItem.blur();

		if (!toolbarItem.hasClass('toolbar-item-active')) {
			toolbarItem.addClass('toolbar-item-active');
			menu.fadeIn(200);
			TYPO3BackendToolbarManager.hideOthers(toolbarItem);
		} else {
			toolbarItem.removeClass('toolbar-item-active');
			menu.fadeOut(200);
		}
	},

	/**
	 * calls the actual clear cache URL using an asynchronious HTTP request
	 *
	 * @param	Event	prototype event object
	 */
	clearCache: function(clickedElement) {
		var toolbarItemIcon = jQuery('#clear-cache-actions-menu .toolbar-item span.t3-icon').first();

			// activate the spinner
		var toolbarItemParent = toolbarItemIcon.parent();
		toolbarItemParent.data('originalHtml', toolbarItemIcon.parent().html());
		var spinner = '<span class="spinner"></span>';
		toolbarItemParent.html(spinner);

		if (jQuery(clickedElement).data('href')) {
			jQuery.get(jQuery(clickedElement).data('href'), function() {
				toolbarItemParent.html(toolbarItemParent.data('originalHtml'));
			});
		}
	}
};

jQuery(document).ready(function() {
	TYPO3BackendClearCacheMenu.initialize();
});

