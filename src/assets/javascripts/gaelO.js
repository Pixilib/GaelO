/**
 Copyright (C) 2018-2020 KANOUN Salim
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

/**
 * check all require filds in a form before submit
 */
function checkForm(form) {
	var inputs = form.elements;
	for (var i = 0; i < inputs.length; i++) {
		if (inputs[i].hasAttribute("required")) {
			if (inputs[i].value == "") {
				// found an empty field
				alertifyError("Please fill in all fields");
				return false;
			}
		}
	}
	return true;
}

/**
 * Change color of key text value in the tables
 * @returns
 */
function changeColor() {

	//Polly fill for IE11 missing function
	if (!String.prototype.includes) {
		Object.defineProperty(String.prototype, 'includes', {
			value: function (search, start) {
				if (typeof start !== 'number') {
					start = 0
				}

				if (start + search.length > this.length) {
					return false
				} else {
					return this.indexOf(search, start) !== -1
				}
			}
		})
	}


	//on parcours tout les tableaux (balises table)
	$("table:visible").each(function (i, el) {
		//si la table a un id
		if (el.id != "") {
			//on parcours chaque clonne de la table (identifiée grace à son id)
			$('#' + el.id + ' tr').each(function (i, el) {

				$(this).find('td').each(function () {
					if ($(this).html().includes("Not Done") || $(this).html().includes("Refused")) {
						$(this).css('color', '#D53333');
					}
					else if ($(this).html().includes("Done") || $(this).html().includes("Accepted")) {
						$(this).css('color', '#47B236)');
					}
					else if ($(this).html().includes("Should be done") || $(this).html().includes("Wait Definitive Conclusion") || $(this).html().includes("Draft") || $(this).html().includes("Corrective Action Asked")) {
						$(this).css('color', '#E29300');
					}
				});
			});
		}

	});
}

/**
 * Download documentation by id
 * @param id
 * @returns
 */
function downloadDocumentation(id) {
	window.location = "scripts/download_documentation.php?idDocumentation=" + id;
}

function alertifyError(message) {
	alertify.error(message);
}

function alertifyWarning(message) {
	alertify.warning(message);
}

function alertifySuccess(message) {
	alertify.success(message);
}

function alertifyMessage(message) {
	alertify.message(message);
}

function µ(str) {
	// Escape & < > " chars
	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function isBrowserSupportDicomUpload() {

	const browser = bowser.getParser(window.navigator.userAgent);
	let isValidBrowser = browser.satisfies({
		// in general
		chrome: ">=90",
		firefox: ">=90",
		edge: ">=90",
		opera: ">=80",
		electron: ">=21",
		safari: ">=15"
	});

	return isValidBrowser
}

const preventDefault = function (event) {
	event.preventDefault();
	event.returnValue = ''; // Needed for Chrome
}

/**
 * Set listeners on page changing and page refreshing
 * The user will be asked to confirm before updating the page
 */
function preventAjaxDivLoading() {
	// Prevent page changing
	window.addEventListener('beforeunload', preventDefault);

	// Prevent ajax div loading
	$(document).ajaxSend((evt, request, settings) => {
		request.abort();
		alertify.confirm('Close?', 'Your upload will be lost. Do you want to <strong>cancel the upload</strong>? </br> In that case, Click OK and redo your action.', () => {
			//SK CANCEL DANS L UPLOADER?
			$(document).off('ajaxSend');
			refreshInvestigatorDiv();
		}, () => { });
	});
}

function allowAjaxDivLoading() {
	window.removeEventListener('beforeunload', preventDefault);
	$(document).off('ajaxSend');
}

function addUnsupportedBrowserMessage(idSelector) {
	$(idSelector).append('\
	<div class="alert alert-danger" role="alert">\
		Sorry, your browser does not support the DICOM Uploader. Please use Firefox, Chrome, Edge 90+, Opera 80+, Safari 15+.\
	</div>\
	');
}


