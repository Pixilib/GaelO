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

class IgnoredFilesPanel {
	constructor() {
		this.dom = $('#du-ignored-files-panel');

		this.dom.append(`
			<div class="modal fade" id="du-ignored-files-modal" tabindex="-1" role="dialog" aria-labelledby="ignored-files-panel" aria-hidden="true">
			<div class="modal-dialog modal-dialog-centered modal-lg">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title">Ignored files <span id="du-ignored-files-badge" class="badge badge-danger"></span></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body">
						<table class="table table-responsive">
							<thead>
								<tr>
									<th>Files</th>
									<th>Reason</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		`);
	}

	update(ignoredFiles) {
		$('#du-ignored-files-badge').text(ignoredFiles.length);

		this.dom.find('tbody').empty();

		for (let file of ignoredFiles) {

			this.dom.find('tbody').append(`
				<tr>
					<td>${file.name}</td>
					<td>${file.ignoredBecause}</td>
				</tr>
			`);
		}
	}
}