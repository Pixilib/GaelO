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

function checkBrowserSupportDicomUpload(selectorDom) {
	var isSupported = {
		webKitDirectory: (function () {
			var elem = document.createElement('input'),
				dir = 'directory',
				domPrefixes = ["", "moz", "o", "ms", "webkit"],
				prefix;

			elem.type = 'file';

			for (prefix in domPrefixes) {
				if (domPrefixes[prefix] + dir in elem) {
					return true;
				}
			}
			return false;
		})(),
		ecmascript6: (function () {
			try {
				new Function("(a = 0) => a");
				return true;
			}
			catch (err) {
				return false;
			}
		})()
	}

	if (!isSupported['ecmascript6']) {
		$(selectorDom).append('\
			<div class="alert alert-danger" role="alert">\
				Sorry, your browser does not support the DICOM Uploader. Please use Firefox 54+, Opera 62+, Edge 17+, Safari 12+, Chrome 58+ or newer versions.\
			</div>\
		');
		throw 'ECMAScript6 not supported.';
	}
	
	if (!(new Resumable()).support) {
		$(selectorDom).append('\
			<div class="alert alert-danger" role="alert">\
				Sorry, your browser does not support \'Resumable.js\'. You will not be able to upload files. Please use Firefox 54+, Opera 62+, Edge 17+, Safari 12+, Chrome 58+ or newer versions.\
			</div>\
		');
	}

	if (!isSupported['webKitDirectory']) {
		$(selectorDom).append('\
			<div class="alert alert-warning" role="alert">\
				Be carefull, your browser does not support \'WebKitDirectory\'. Use drag and drop when importing files instead of using the browsing window. (You can also change your browser for Firefox 54+, Opera 62+, Edge 17+, Safari 12+, Chrome 58+ or newer versions)\
			</div>\
		');
	}
}