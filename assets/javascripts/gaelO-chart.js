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



/**
 * Returns an array with the 'x' values
 * of the given set of data 'arr'
 * @param {string} x property name to extract
 * @param {array} arr set of data
 */
function listValuesX(x, arr) {
	let res = [];
	for (let elmt of arr) {
		if (elmt.hasOwnProperty(x)) {
			res.push(elmt[x]);
		} else {
			console.warn('listValuesX: This element does not have the property: ' + x);
		}
	}
	return res;
}


/**
 * Returns an array of objects with a property
 * named 'x' which contains the corresponding value
 * in the given array
 */
function listValuesXReverse(x, arr) {
	let res = [];
	for (let elmt of arr) {
		let obj = {};
		obj[x] = elmt;
		res.push(obj);
	}
	return res;
}

function objGetValuesX(x, obj) {
	let res = [];
	for (let e in obj) {
		res.push(obj[e][x]);
	}
	return res;
}

function objGetProperties(obj) {
	let res = [];
	for (let p in obj) {
		res.push(p);
	}
	return res;
}




class GaelOChart {

	/**
	 * Format a set of data for a 'bar' chartjs. It averages the
	 * 'y' (values) and groups them by 'x' (labels)
	 * @param {number} y values property
	 * @param {*} x group property
	 * @param {array|object} rawData set of data
	 * @param {number} roundedTo decimal places 
	 */
	static formatAvgGroupBy(y, x, rawData, roundedTo) {
		let res = {
			labels: [],
			values: []
		};

		for (let d in rawData) {
			d = rawData[d];
			if (!res.labels.includes(d[x])) {
				res.labels.push(d[x]);
			}
		}
		for (let l in res.labels) {
			l = res.labels[l];
			let avg = 0;
			let count = 0;
			for (let d in rawData) {
				d = rawData[d];
				if (d[x] == l) {
					avg += d[y];
					count++;
				}
			}
			avg /= count;
			res.values.push(avg.toFixed(roundedTo));
		}
		return res;
	}

	/**
	 * Format a set of data for a 'pie' chartjs. It sums the
	 * 'y' (values) and groups them by 'x' (labels)
	 * @param {number} y values property
	 * @param {*} x group property
	 * @param {array|object} rawData set of data
	 * @param {number} roundedTo decimal places 
	 */
	static formatSumGroupBy(y, x, rawData) {
		let res = {
			labels: [],
			values: []
		};

		for (let d in rawData) {
			d = rawData[d];
			if (!res.labels.includes(d[x])) {
				res.labels.push(d[x]);
			}
		}
		for (let l in res.labels) {
			l = res.labels[l];
			let sum = 0;
			for (let d in rawData) {
				d = rawData[d];
				if (d[x] == l) {
					sum += d[y];
				}
			}
			res.values.push(sum);
		}
		return res;
	}

	/**
	 * Returns the hexcode of a color
	 * @param {number} colorIndex
	 */
	static generateColor(colorIndex) {
		let hex = ['#F3C300', '#875692', '#F38400', '#A1CAF1', '#BE0032', '#C2B280', '#848482', '#008856', '#E68FAC', '#0067A5', '#F99379', '#604E97', '#F6A600', '#B3446C', '#DCD300', '#882D17', '#8DB600', '#654522', '#E25822', '#2B3D26'];
		if (colorIndex === undefined) {
			colorIndex = (Math.random() * hex.length-1).toFixed(0);
		}
		return {
			hex: hex[colorIndex%hex.length],
			r: GaelOColor.hexToRgb(hex[colorIndex%hex.length]).r,
			g: GaelOColor.hexToRgb(hex[colorIndex%hex.length]).g,
			b: GaelOColor.hexToRgb(hex[colorIndex%hex.length]).b
		};
	}

	static createSelect(dom, data, x, customConfig) {
		let config = {
			xGroup: undefined,
			selectAllByDefaultIfLessThan: 20
		}
		// Overrides default config with custom config
		if (customConfig !== undefined) {
			for (let c in customConfig) {
				if (customConfig.hasOwnProperty(c)) {
					config[c] = customConfig[c];
				}
			}
		}

		dom.chosen({
			width: "100%",
			hide_results_on_select: false
		});

		if (config.xGroup !== undefined) {
			var optgroups = listValuesX(config.xGroup, alasql(`SELECT ${config.xGroup} FROM ? GROUP BY ${config.xGroup}`, [data]));
			optgroups.sort();

			var associations = {
				values: alasql(`SELECT ${config.xGroup}, ${x} FROM ? GROUP BY ${config.xGroup}, ${x}`, [data]),
				doesMatch: function (optgroup, option) {
					for (let v of this.values) {
						if (v[config.xGroup] == optgroup && v[x] == option) {
							return true;
						}
					}
					return false;
				}
			}

		}

		let options = listValuesX(x, alasql(`SELECT ${x} FROM ? GROUP BY ${x}`, [data]));
		options.sort();

		let res = {
			dom: dom,
			property: x,
			options: options,
			propertyGroup: config.xGroup,
			optgroups: optgroups,
			associations: associations,
			selectAllByDefaultIfLessThan: config.selectAllByDefaultIfLessThan,
			fill: function () {
				this.dom.empty();
				let html = '';
				if (this.optgroups !== undefined) {
					for (let og of this.optgroups) {
						html += `<optgroup label="${og}">\n`;
						for (let o of this.options) {
							if (this.associations.doesMatch(og, o)) {
								html += `<option value="${o}">${o}</option>\n`;
							}
						}
						html += `</optgroup>\n`;
					}
				} else {
					for (let o of this.options) {
						html += `<option value="${o}">${o}</option>\n`;
					}
				}
				this.dom.append(html);
				this.dom.trigger('chosen:updated');
			},

			init: function () {
				this.fill();
				if (this.options.length < this.selectAllByDefaultIfLessThan) {
					this.selectAll();
				}
			},

			update: function (opts) {
				if (opts !== undefined) {
					this.dom.val(opts);
					this.dom.chosen().change();
				}
				this.dom.trigger('chosen:updated');
			},

			selectAll: function () {
				let opts = [];
				for (let i = 0; i < this.dom.find('option').length; i++) {
					opts.push(this.dom.find('option')[i].value);
				};
				this.update(opts);
			},

			deselectAll: function () {
				this.dom.find('option').removeAttr('selected', false);
				this.update([]);
			},

			select: function(opt) {
				this.dom.find(`option[value=${opt}]`).attr('selected', false);
				let opts = this.dom.val();
				opts.push(opt);
				this.update(opts);
			},

			deselect: function(opt) {
				this.dom.find(`option[value=${opt}]`).removeAttr('selected', false);
				let opts = this.dom.val();
				let index = opts.indexOf(opt);
				if (index !== -1) {
					opts.splice(index, 1);
				}
				this.update(opts);
			},

			isSelected: function (opt) {
				return this.dom.val().includes(opt);
			},

			remove(opt) {
				this.deselect(opt);
				this.dom.find(`option[value=${opt}]`).remove();
				let index = this.options.indexOf(opt);
				if (index !== -1) {
					this.options.splice(index, 1);
				}
				this.update();
			},

			add(opts) {
				if (!Array.isArray(opts)) {
					opts = [opts];
				}
				for (let opt of opts) {
					if (this.dom.find(`option[value=${opt}]`).length === 0) {
						this.options.push(opt);
						this.dom.append(`<option value="${opt}">${opt}</option>`);
					}
				}
				this.update();
			},

			disable(opt) {
				this.dom.find(`option[value=${opt}]`).attr('disabled','');
				this.update();
			},

			enable(opt) {
				this.dom.find(`option[value=${opt}]`).removeAttr('disabled');
				this.update();
			}
		}
		res.init();
		return res;
	}
}





class GaelOColor {

	static rgbToHex(r, g, b) {

		function componentToHex(c) {
			var hex = c.toString(16);
			return hex.length == 1 ? "0" + hex : hex;
		}

		return "#" + componentToHex(r) + componentToHex(g) + componentToHex(b);
	}

	static hexToRgb(hex) {
		var result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
		return result ? {
			r: parseInt(result[1], 16),
			g: parseInt(result[2], 16),
			b: parseInt(result[3], 16)
		} : null;
	}

	static luminance(hex, lum) {
		// Validate hex string
		hex = String(hex).replace(/[^0-9a-f]/gi, "");
		if (hex.length < 6) {
			hex = hex.replace(/(.)/g, '$1$1');
		}
		lum = lum || 0;
		// Convert to decimal and change luminosity
		var rgb = "#",
			c;
		for (var i = 0; i < 3; ++i) {
			c = parseInt(hex.substr(i * 2, 2), 16);
			c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
			rgb += ("00" + c).substr(c.length);
		}
		return rgb;
	}
}