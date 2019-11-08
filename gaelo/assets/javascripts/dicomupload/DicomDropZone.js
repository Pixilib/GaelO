/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

class DicomDropZone {
	constructor(selectorDom) {
		this.dom = $(selectorDom);

		this.obj = new Dropzone(selectorDom, {
			previewsContainer: false,
			dictDefaultMessage: "",

			// Upload is done with resumable.js
			url: "#",
			autoProcessQueue: false,

			// Define custom properties
			init: function () {
				this.active = true;
			}
		});

		this.reloadWebKitDir();
	}

	/**
	 * Delegate 'on' function to the Dropzonejs object
	 */
	on(event, handlerFunction) {
		this.obj.on(event, handlerFunction);
	}

	/**
	 * Activate or deactivate the dropzone
	 * @param {boolean} status 
	 */
	setActive(status) {
		// Check active is boolean & affect property
		if (typeof status === 'boolean') this.active = status;
		// Set dropzone DOM inner text
		if (this.active === true) {
			this.dom.text('Drag & drop files here');
			this.dom.removeClass('du-deactivated');

		} else {
			this.dom.text('');
			this.dom.addClass('du-deactivated');
		}
	}

	/**
	 * Enable the dropzone to browse and import entire folders
	 * This needs to be called whenever the dropzone ends to load files
	 */
	reloadWebKitDir() {
		// Override dropzonejs html element, allow folder select when browsing files
		// It needs to be called each time files are dropped
		return $('body').find('input.dz-hidden-input').attr('webkitdirectory', '');
	}
}