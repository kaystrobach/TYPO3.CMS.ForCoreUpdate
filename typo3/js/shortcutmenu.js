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
 * class to handle the shortcut menu
 */
var TYPO3BackendShortcutMenu = {

	/**
	 * registers for resize event listener and executes on DOM ready
	 */
	initialize: function() {
		jQuery(window).resize(function() {
				TYPO3BackendToolbarManager.positionMenu('shortcut-menu');
			}
		);
		TYPO3BackendToolbarManager.positionMenu('shortcut-menu');
		jQuery('#shortcut-menu .toolbar-item').first().click(function(){
			TYPO3BackendShortcutMenu.toggleMenu();
		})
		TYPO3BackendShortcutMenu.initControls();
	},

	/**
	 * initializes the controls to follow, edit, and delete shortcuts
	 *
	 */
	initControls: function() {
		jQuery('.shortcut-label a').each(function() {
			jQuery(this).click(function(){
				TYPO3BackendShortcutMenu.toggleMenu();
			})
		});

			// activate delete icon
		jQuery('.shortcut-delete img').each(function() {
			jQuery(this).click(function() {
				if (confirm('Do you really want to remove this bookmark?')) {
					var shortcutId = jQuery(this).parents('.shortcut').attr('id').slice(9);
					var del = jQuery.get('ajax.php?ajaxID=ShortcutMenu::delete&shortcutId=' + shortcutId, function(){
						TYPO3BackendShortcutMenu.reRenderMenu();

					});
				}
			});
		});
	},

	/**
	 * toggles the visibility of the menu and places it under the toolbar icon
	 */
	toggleMenu: function(event) {
		var toolbarItem = jQuery('#shortcut-menu > a').first();
		var menu        = jQuery('#shortcut-menu .toolbar-item-menu').first();
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
	 * gets called when the update was succesfull, fetches the complete menu to
	 * honor changes in group assignments
	 */
	reRenderMenu: function(transport, element, backPath) {
		var container = jQuery('#shortcut-menu .toolbar-item-menu').first();
		if (!backPath) {
			var backPath = '';
		}

		container.css({
			height: container.height() + 'px'
		});
		container.html('LOADING');

		var render = jQuery.ajax({
			url: backPath + 'ajax.php?ajaxID=ShortcutMenu::render',
			success: function(result) {
				container.html(result);
				TYPO3BackendShortcutMenu.initControls();
			},
			async: false
		});

		container.css({
			height: 'auto'
		});
	},

	/**
	 * makes a call to the backend class to create a new shortcut,
	 * when finished it reloads the menu
	 */
	createShortcut: function(backPath, moduleName, url) {
		var toolbarItemIcon = jQuery('#shortcut-menu .toolbar-item span.t3-icon').first();

		// activate the spinner
		var toolbarItemParent = toolbarItemIcon.parent();
		toolbarItemParent.data('originalHtml', toolbarItemIcon.parent().html());
		var spinner = '<span class="spinner"></span>';
		toolbarItemParent.html(spinner);
			// synchrous call to wait for it to complete and call the render
			// method with backpath _afterwards_
		var call = jQuery.ajax({
			url: backPath + 'ajax.php?ajaxID=ShortcutMenu::create',
			type: 'POST',
			data: {
				'module': moduleName,
				'url': decodeURIComponent(url)
			},
			success: function(result) {
				TYPO3BackendShortcutMenu.reRenderMenu();
				toolbarItemParent.html(toolbarItemParent.data('originalHtml'));
				TYPO3BackendShortcutMenu.initControls();
			},
			async: true
		});

	}

};

jQuery(document).ready(function() {
	TYPO3BackendShortcutMenu.initialize();
});