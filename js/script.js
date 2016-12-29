(function() {

	/*=========================================
	=            Library functions            =
	=========================================*/

	var myLoad = function(dataToLoad, callback) {

		var links = [];

		for(var name in dataToLoad) {
			var item = dataToLoad[name];

			item.urls = item.urls || [item.url];

			links = links.concat(item.urls.map(function(url) {
				return { item: item, loader: item.loader, url: url }
			}))

		}


		links.forEach(function(link) {
			link.state = 'pending';
			link.loader(link.url, function(error, data) {
				link.state = 'loaded';

				link.item.data = link.item.data ? link.item.data.concat(data) : (Array.isArray(data) ? data : [data])

				for(var i = 0; i < links.length; ++i) {
					if(links[i].state == 'pending') { return }
				}

				callback(dataToLoad)
			})
		})

	};
	
	var extendObject = function(target, source) {
		for (var k in source) {
			if (source.hasOwnProperty(k)) {
				target[k] = source[k]
			}
		}
		return target
	};

	/*=====  End of Library functions  ======*/










	/*============================
	=            Init            =
	============================*/

	var $window = d3.select(window)
		, $body = d3.select(document.body)
		, cf = undefined;
	
	/*=====  End of Init  ======*/










	/*================================
	=            Init map            =
	================================*/

	var map = {};

	map.leaflet = L.map('map', {
		center: conf.map.center
		, zoom: conf.map.zoom
		, minZoom: conf.map.minZoom
		, maxZoom: conf.map.maxZoom
		, maxBounds: conf.map.maxBounds
		, zoomControl: false
	});


	map.leaflet.on('viewreset', function() {
		map.leaflet.invalidateSize()
	});

	new L.Control.Zoom({ position: 'topright' }).addTo(map.leaflet)

	map.osmLayer = L.tileLayer(conf.map.tiles, {
		attribution: '<a href="http://osm.org/copyright">OpenStreetMap</a>'
	});

	map.leaflet.addLayer(map.osmLayer)

	map.ukrainianLayer = L.geoJson(conf.admUkrainian, {
		style: function(feature) {
			return {
				weight: 2
				, opacity: 1
				, color: '#aaa'

				// hide fill for Krym, Sevastopol, Donetsk and Luhansk
				, fill: ['0100000000', '8500000000', '1400000000', '4400000000'].indexOf(feature.properties['KOATUU']) == -1
				, fillColor: '#03f'
				, fillOpacity: .03
				, clickable: false
			}
		}
	})
	map.leaflet.addLayer(map.ukrainianLayer)

	map.raionsLayer = L.geoJson(conf.admRaions, {
		style: function(feature) {
			return {
				weight: 1
				, opacity: .3
				, color: '#aaa'

				, fill: true
				, fillColor: conf.raionColors[feature.properties['KOATUU']]
				, fillOpacity: .55

				, clickable: false
			}
		}
		, className: 'raions-overlay'
	})
	map.leaflet.addLayer(map.raionsLayer)

	map.greyZoneLayer = L.geoJson(conf.greyZone, {
		style: function(feature) {
			return {
				weight: 1
				, opacity: 1
				, color: '#666'
				, fill: false
				, clickable: false
			}
		}
	})

	map.leaflet.addLayer(map.greyZoneLayer)

	map.greyZoneLayer.eachLayer(function(layer) {
		d3.select(layer._container).select('path')
			.attr({ 'fill': 'url("#diagonalHatch")'})
			.style({ 'opacity': .5 })
	})

	// add diagonalHatch pattern
	d3.select(map.leaflet._pathRoot).insert('defs', ':first-child')
		.append('pattern')
			.attr('id', 'diagonalHatch')
			.attr('patternUnits', 'userSpaceOnUse')
			.attr('width', 4)
			.attr('height', 4)
			.append('path')
				.attr('d', 'M -1,1 l 2,-2    M 0, 4 l 4,-4    M 3,5 l 2,-2')
				.attr('stroke', '#666')
				.attr('stroke-width', 1)

	/*=====  End of Init map  ======*/










	myLoad(conf.data, function(data) {
		/*==================================
		=            Clean data            =
		==================================*/

		var referals = data.referals;

		var referalsCleaned = referals.data.map(function(record) {
			//console.dir(record)
			var res = {
				settlementRaw: record[referals.fields.settlement]
				, settlement: record[referals.fields.settlement]
				, raion: record[referals.fields.raion]
				, raionCode: record[referals.fields.raionCode]
				, oblast: record[referals.fields.oblast]
				, latitude: parseFloat(record[referals.fields.latitude]) || 0
				, longitude: parseFloat(record[referals.fields.longitude]) || 0
				, affected: parseInt(record[referals.fields.affected]) || 0
				, date: conf.dateParse.parse(record[referals.fields.date])
				, status: record[referals.fields.status]
				, cluster: record[referals.fields.cluster].split(',').map(function(name) { return name.trim() })
				, partner: record[referals.fields.partner]
				, type: record[referals.fields.type]
				, need: record[referals.fields.need].split(',').map(function(name) { return name.trim() })
				, covered: record[referals.fields.covered]
				, context: record[referals.fields.context]
				, description: record[referals.fields.description]
				, infoLink: record[referals.fields.infoLink]
				, notCoveredNeeds: record[referals.fields.notCoveredNeeds]
				, conflictRelated: record[referals.fields.conflictRelated]
			};

			res.coords = [res.latitude, res.longitude]
			res.title = res.settlement + ' / ' + res.raion + ' / ' + res.oblast
			res.titleRevers = res.oblast + ' / ' + res.raion + ' / ' + res.settlement

			return res
		});

		
		/*=====  End of Clean data  ======*/










		/*========================================
		=            Init crossfilter            =
		========================================*/
		
		var cf = crossfilter(referalsCleaned)

		cf.allDim = cf.dimension(function(d) { return d.titleRevers })
		cf.allGrp = cf.allDim.groupAll()
		cf.settlementDim = cf.dimension(function(d) { return d.settlementRaw })
		cf.affectedGrp = cf.settlementDim.group().reduce(
				function(p, v) {
					if(!p.key) p.key = v.settlementRaw
					if(!p.coords) p.coords = v.coords
					if(!p.record) p.record = v

					++p.count
					
					p.affected += v.affected
					p.status[v.status].affected += v.affected
					
					return p
				}
				, function(p, v) {
					--p.count

					p.affected -= v.affected
					p.status[v.status].affected -= v.affected

					return p
				}
				, function() {
					return {
						key: undefined
						, record: undefined
						, coords: undefined
						, count: 0
						, affected: 0
						, status: conf.filterStatus.reduce(function(init, cur) { init[cur.key] = { affected: 0, color: cur.color }; return init }, {})
					}
				}
			)
			.order(function(d) { return d.affectedCount })

		cf.dateDim = cf.dimension(function(d) { return d.date })
		cf.clusterDim = cf.dimension(function(d) { return d.cluster }, true)
		cf.partnerDim = cf.dimension(function(d) { return d.partner })
		cf.statusDim = cf.dimension(function(d) { return d.status })
		cf.needDim = cf.dimension(function(d) { return d.need }, true)
		cf.typeDim = cf.dimension(function(d) { return d.type })
		cf.oblastDim = cf.dimension(function(d) { return d.oblast })
		cf.raionCodeDim = cf.dimension(function(d) { return d.raionCode })
		cf.oblastRaionGrp = cf.raionCodeDim.group().reduce(
				function(p, v) {
					++p.count
					p.oblast = v.oblast
					p.raion = v.raion
					p.raionCode = v.raionCode
					return p
				}
				, function(p, v) {
					--p.count
					return p
				}
				, function() {
					return {
						count: 0
						, oblast: undefined
						, raion: undefined
						, raionCode: undefined
					}
				}
			)
			.order(function(d) { return d.raion })
		

		var filterDispatcher = d3.dispatch('filtered')

		/*=====  End of Init crossfilter  ======*/










		/*==========================================================
		=            Update raions on map when filtered            =
		==========================================================*/

		// it uses raionsLayer from map section

		var allRaionCodes = d3.merge(d3.values(conf.filterOblastRaions)).map(function(raion) { return raion.key })

		var updateRaions = function(selectedRaionCodes) {
			map.raionsLayer.eachLayer(function(layer) {
				var containerNodes = layer._container ? [layer._container] : d3.values(layer._layers).map(function(layer) { return layer._container })

				var $paths = d3.selectAll(containerNodes).selectAll('path')

				if(!(selectedRaionCodes instanceof Array) || selectedRaionCodes.length == 0) {
					// none raions selected
					$paths.classed({ 'js-active': false, 'js-inactive': true })
				} else if (allRaionCodes.length == selectedRaionCodes.length) {
					// all raions selected
					$paths.classed({ 'js-active': false, 'js-inactive': false })
				} else {
					var active = selectedRaionCodes.indexOf(layer.feature.properties['KOATUU']) != -1
					$paths.classed({ 'js-active': active, 'js-inactive': !active })
				}
			})
		}

		var raionFilterDispatcher = d3.dispatch('filtered')

		raionFilterDispatcher.on('filtered.updateRaions', function(filters) {
			updateRaions(filters)
			//console.log(filters)
		})

		updateRaions(allRaionCodes)

		/*=====  End of Update raions on map when filtered  ======*/










		/*=================================================
		=            Markers and Marker's Rose            =
		=================================================*/



		var round = function(number, count) {

			var order = 0
			var i = number
			while(i > 1) {
				i /= 10
				++order
			}

			var roundTo1Num = Math.round(number / Math.pow(10, order - 1)) * Math.pow(10, order - 1)
				, roundTo15Num = Math.round(number / Math.pow(10, order - 2) / 5) * 5 * Math.pow(10, order - 2)
				, ceilTo1Num = Math.ceil(number / Math.pow(10, order - 1)) * Math.pow(10, order - 1)

			var roundTo1Perc = Math.abs(Math.abs(roundTo1Num - number) / number * 100)
				, roundTo15Perc = Math.abs(Math.abs(roundTo15Num - number) / number * 100)
				, ceilTo1Perc = Math.abs(Math.abs(ceilTo1Num - number) / number * 100)

			if(ceilTo1Perc < 20) {
				return ceilTo1Num
			}

			if(roundTo15Perc < roundTo1Perc) {
				return roundTo15Num
			}

			return roundTo1Num
		}


		var affectedMax = 0

		cf.affectedGrp.all().forEach(function(item) {
			item = item.value

			if(affectedMax < item.affected) {
				affectedMax = item.affected
			}
		})

		var affectedMaxRound = round(affectedMax)


		var calculateDiameter = function(affectedCount) {
			// var diam = d3.scale.sqrt().domain([0, affectedMax])(affectedCount) * conf.markerMaxDiam
			var diam = d3.scale.sqrt().domain([0, affectedMaxRound])(affectedCount) * conf.markerMaxDiam

			if(diam == 0) {
				return 0
			} else if( diam <= conf.markerMinDiam) {
				return conf.markerMinDiam
			} else {
				return diam
			}
		}

		// init markers
		var markerLayer = L.layerGroup()
		cf.affectedGrp.all().forEach(function(item) {
			item = item.value

			var marker = L.marker(item.coords)

			marker.data = {
				key: item.key
				, record: item.record
				, coords: item.coords
				, count: item.count
				, status: item.status
				, affected: item.affected
				, initDiameter: calculateDiameter(item.affected)
				, size: calculateDiameter(item.affected) + 4 // plus space to stroke
			}

			markerLayer.addLayer(marker)

			marker.setIcon(L.divIcon({ className: 'marker', iconSize: [marker.data.size, marker.data.size] }))
		})
		map.leaflet.addLayer(markerLayer)

		// init helpers for markers pies
		var arc = d3.svg.arc()
			.innerRadius(0)
			.startAngle(0)
		var pie = d3.layout.pie()
			.sort(null)
			.value(function(d, i) { return d.affected })

		// add markers pies
		markerLayer.eachLayer(function(marker) {
			var $icon = d3.select(marker._icon)
			$icon.datum({ marker: marker })

			// init svg
			var $svg = $icon.append(function() { return document.createElementNS('http://www.w3.org/2000/svg', 'svg') })
			$svg.classed({ 'marker-icon': true })
			$svg.attr({ 'width': marker.data.size, 'height': marker.data.size })

			// init container
			var $container = $svg.append('g')
				.attr('transform', 'translate(' + marker.data.size / 2 + ',' + marker.data.size / 2 + ')')

			// this circle used to drow stroke over pies
			$container.append('circle')
				.attr({ 'r': marker.data.initDiameter / 2 })

			// draw pies
			var data = pie( d3.map(marker.data.status).values() ).map(function(pie) {
				pie.diameter = marker.data.initDiameter
				return pie
			})
			$container.selectAll('path')
				.data(data)
				.enter()
				.append('path')
					.attr('d', function(d) {
						arc
							.outerRadius(d.diameter / 2)
							.startAngle(d.startAngle)
							.endAngle(d.endAngle)
						return arc()
					})
					.attr('fill', function(layout) { return layout.data.color })
		})


		var resetMarkers = function() {
			// get updated list of records, and makes it to be object instead of array
			var records = cf.affectedGrp.all().reduce(function(init, curr) { init[curr.key] = curr.value; return init }, {})

			// go through each marker
			markerLayer.eachLayer(function(marker) {
				var $icon = d3.select(marker._icon)
					, $svg = $icon.select('svg')
				
				// get record, and update markers data accordingly
				var record = records[marker.data.key]
				marker.data.count = record.count

				// check if marker should be visible and handled, or just hide it if it has no data
				if(marker.data.count == 0) {
					$icon.style({ 'display': 'none' })
					return
				} else {
					$icon.style({ 'display': null })
				}

				// get new marker attributes
				var diameter = calculateDiameter(record.affected)
					, oldData, newData

				// update markers dom
				var $container = $svg.select('g')

				$container.select('circle')
					.transition()
					.attr({ 'r': diameter / 2 })
				
				$container.selectAll('path')
					.call(function() {
						oldData = this.data()
						, newData = pie( d3.map(record.status).values() ).map(function(pie) {
							pie.diameter = diameter
							return pie
						})
					})
					.data(newData)
					.transition()
					.call(function(transition) {
						transition.attrTween('d', function(d, i) {
							var interpolateStartAngle = d3.interpolate(oldData[i].startAngle, d.startAngle)
								, interpolateEndAngle = d3.interpolate(oldData[i].endAngle, d.endAngle)
								, interpolateDiameter = d3.interpolate(oldData[i].diameter, diameter)
							
							return function(t) {
								arc
									.startAngle(interpolateStartAngle(t))
									.endAngle(interpolateEndAngle(t))
									.outerRadius(interpolateDiameter(t) / 2)
								return arc()
							}
						})
					})
			})
		}

		filterDispatcher.on('filtered.resetMarkers', function() {
			resetMarkers()
		});

		var rose = {
			$el: undefined
			, state: 'none'
			, open: function(marker) {
				var self = this
				self.state = 'open'
				self.marker = marker

				/* get data */
				var records = cf.settlementDim.top(Infinity).filter(function(record) { return record.settlementRaw == marker.data.key })

				/* count rose geometry properties */
				var diameters = records.map(function(record) { return calculateDiameter(record.affected)} )
					, markerDiameter = marker.data.initDiameter
					, markerSize = marker.data.size
					, maxDiameter = d3.max(diameters)

				// minimalRadius is calculated in such a way that the main marker would not overlap the biggest marker on circle
				var minimalRadius = markerDiameter / 2 + conf.markerSpacer + maxDiameter / 2


				var length = Math.max(d3.sum(diameters) + conf.markerSpacer * diameters.length, minimalRadius  * 2 * Math.PI)
					, radius = length / 2 / Math.PI

				var boxSize = radius * 2 + maxDiameter + 4

				/* create svg container */
				self.$el = d3.select(marker._icon).append('svg')
					.style({
						'position': 'absolute'
						, 'width':  boxSize + 'px'
						, 'height': boxSize + 'px'
						, 'left': - (boxSize - markerSize) / 2 + 'px'
						, 'top': - (boxSize - markerSize) / 2 + 'px'
					})
					.classed({ 'marker-rose': true })
				self.$container = self.$el.append('g')
				self.$container.attr({ 'transform': 'translate(' + (boxSize / 2) + ',' + (boxSize / 2) + ')' })


				/* insert markers */

				var pos = - diameters[0] / 2

				records.forEach(function(record, i) {
					var status = conf.filterStatus.filter(function(status) { return status.key == record.status })[0]

					pos += diameters[i] / 2

					var x = radius * Math.cos(pos / length * 2 * Math.PI - (Math.PI / 2))
						, y = radius * Math.sin(pos / length * 2 * Math.PI - (Math.PI / 2))
					
					self.$container.append('line')
						.attr({ 'x1': 0, 'y1': 0, 'x2': 0, 'y2': 0 })
						.style({ 'stroke': '#777', 'stroke-width': 2, 'stroke-dasharray': '2,5' })
						.transition()
							.attr({'x2': x, 'y2': y})

					self.$container.append('circle')
						.datum({ record: record })
						.classed({ 'js-submarker': true })
						.attr({ 'r': diameters[i] / 2, 'cx': 0, 'cy': 0, 'fill': status.color })
						.transition()
							.attr({'cx': x, 'cy': y})


					pos += diameters[i] / 2 + conf.markerSpacer
				})

			}
			, close: function() {
				var self = this
				self.state = 'none'

				self.$container.selectAll('circle')
					.transition()
						.attr({ 'cx': 0, 'cy': 0})

				self.$container.selectAll('line')
					.transition()
						.attr({ 'x2': 0, 'y2': 0 })

				self.$el.transition().remove()

				self.marker = undefined
			}
		}


		var popup = function(recordData, latlng) {
			var popup = L.popup({ minWidth: 320 })
				.setLatLng(latlng)
				.setContent(conf.tplPopup({ data: recordData }))
				.openOn(map.leaflet)
		}

		/* marker interaction logic */

		var moving // It is used to check whether the event was a map moving or click
			, clickStartPos
			, latlng
		map.leaflet.on('mousedown', function(mapEvent) {
			clickStartPos = [mapEvent.originalEvent.screenX, mapEvent.originalEvent.screenY]
		})
		map.leaflet.on('mouseup', function(mapEvent) {
			moving =
				Math.abs(mapEvent.originalEvent.screenX - clickStartPos[0]) > 5
				|| Math.abs(mapEvent.originalEvent.screenY - clickStartPos[1]) > 5
			latlng = mapEvent.latlng
		})

		var markersResetInactive = function(currentMarker) {
			markerLayer.getLayers().forEach(function(marker) {
				d3.select(marker._icon).classed({ 'js-inactive': currentMarker ? marker._icon != currentMarker._icon : false })
			})
		}
		var markersResetActive = function(currentMarker) {
			markerLayer.getLayers().forEach(function(marker) {
				d3.select(marker._icon).classed({ 'js-active': marker == currentMarker })
			})
		}
		var submarkersResetActive = function($marker) {
			rose.$el.selectAll('circle').classed({ 'js-active': false })
			$marker.classed({ 'js-active': true })
		}

		$window.on('click', function() {
			if(moving) { return }
			map.leaflet.closePopup() // close popup if any
			markersResetActive()

			if(rose.state == 'open') {
				markersResetInactive()
				rose.close()
			}
		})

		// hide opened all popups
		// if(click on marker with single record) {
		// 	show popup
		// 	close rose if opened
		// } else {
		// 	if(rose not opened) {
		// 		open rose
		// 	} else {
		// 		if(event.target is marker in rose) {
		// 			open popup
		// 		} else {
		// 			rose is opened and click was not on marker, so
		// 			close rose
		// 		}
		// 	}
		// }

		markerLayer.getLayers().forEach(function(marker) {
			// by some reasones, leaflet reset all markers on click, without it
			marker.on('click', function(event) { })
			
			d3.select(marker._icon).on('click', function() {
				var self = this

				if(moving) { return }

				var currentMarker = d3.select(self).datum().marker

				map.leaflet.closePopup() // close popup if any
	
				var $target = d3.select(d3.event.target)

				if(currentMarker.data.count == 1) {

					if(rose.state == 'open') {
						markersResetInactive()
						markersResetActive()
						rose.close()
					}

					markersResetActive(currentMarker)
					popup(currentMarker.data.record, latlng)
				} else {
					if(rose.state != 'open') {
						markersResetInactive(currentMarker)
						markersResetActive(currentMarker)

						rose.open(currentMarker)
					} else {
						if($target.classed('js-submarker')) {
							submarkersResetActive($target)
							popup($target.datum().record, latlng)
						} else {
							markersResetInactive()
							markersResetActive()

							rose.close()
						}
					}
				}

			})
		})

		/*=====  End of Markers and Marker's Rose  ======*/










		/*====================================
		=            Month filter            =
		====================================*/
        var slider;

		var initMonthpicker = function() {

			// Retrieving Min and Max date values from date dimension
			var   dateBegin = cf.dateDim.bottom(1)[0].date
				, dateEnd = cf.dateDim.top(1)[0].date;


			//var $monthpicker = d3.select('#monthpicker')

			// monthpicker = new Monthpicker($monthpicker.node(), dateBegin, dateEnd, {
			// 	change: function(dateBegin, dateEnd) {
			// 		cf.dateDim.filterRange([dateBegin, dateEnd])
            //
			// 		filterDispatcher.filtered()
			// 	}
			// });

			//lrscroll($monthpicker.node(), {anchors: '.monthpicker-month'});

            slider = $("#slider");
            var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sept", "Oct", "Nov", "Dec"];
            slider.dateRangeSlider({
                bounds: {
                    min: dateBegin,
                    max: dateEnd
                },
				values: {
                    min: dateBegin,
                    max: dateEnd
				},
                valueLabels: "change",
                delayOut: 600,
                scales: [{
                    first: function(value){ return value; },
                    end: function(value) {return value; },
                    next: function(value){
                        var next = new Date(value);
                        return new Date(next.setMonth(value.getMonth() + 1));
                    },
                    label: function(value){
                        return months[value.getMonth()];
                    },
                    format: function(tickContainer, tickStart, tickEnd){
                        tickContainer.addClass("myCustomClass");
                    }
                }]
            });


            slider.bind("valuesChanged", function(e, data){

                var values = slider.dateRangeSlider("values");
                var bounds = slider.dateRangeSlider("bounds");

                var dateBeginNew;
                var dateEndNew;

                if (values.min.getTime() === values.max.getTime()) {
                    dateBeginNew = bounds.min;
                    dateEndNew = bounds.max;
				} else {
                    dateBeginNew = values.min;
                    dateEndNew = values.max;
				}
				//console.log(dateBeginNew);
                //console.log(dateEndNew);
                cf.dateDim.filterRange([dateBeginNew, dateEndNew]);
				filterDispatcher.filtered();
            });


		};

		if(['interactive', 'complete'].indexOf(document.readyState) != -1) {
			initMonthpicker();
			//monthpicker.startPos();
            //console.log(monthpicker.startPos());
		} else {
			$window.on('load', initMonthpicker);
		}

        var resetMonthpicker = function (slider) {

            var bounds = slider.dateRangeSlider("bounds");
            //console.log(bounds);
            slider.dateRangeSlider("values", bounds.min, bounds.max);
            //console.log(slider.dateRangeSlider("values"));
            //cf.dateDim.filterRange([bounds.min, bounds.max]);
            //filterDispatcher.filtered();

        };
		/*=====  End of Month filter  ======*/










		/*=============================================
		=            Partner filter                   =
		=============================================*/
		
		var   $partner = d3.select('#filterPartner') // select with list of partners as option values
			, $partnerSelected = d3.select('#filterPartnerSelected') // Div with list of selected partners and remove button
			, $allPartners = d3.select('#filterPartnersAll'); // Checkbox that's intended to select "All partners"


		// Fulfills select with values

		$partner.selectAll('option')
			.data(cf.partnerDim.group().all().map(function(d) { return d.key }))
			.enter()
			.append('option')
				.attr({ 'value': function(datum) { return datum } })
				.text(function(datum) { return datum });

        // d3.select('#filterPartner2').selectAll('option')
        //     .data(cf.partnerDim.group().all().map(function(d) { return d.key }))
        //     .enter()
        //     .append('option')
        //     .attr({ 'value': function(datum) { return datum } })
        //     .text(function(datum) { return datum });

		// $partner.insert('option', ':first-child')
		// 	.text('- Select partner to filter -')
		// 	.attr({ 'value': 'none' });


		var handleDeselect = function() {
			var   option = this
				, $option = d3.select(option)
				, $deselect = d3.select( $option.datum().deselect );

			$option
				.style({ 'display': null })
				.datum({ selected: false });
			
			$deselect.remove();


			//console.log(option);

			partnerChange()

		};

		var handleSelect = function() {

			var el = this;
			if(el.value == 'none') { return }


			var $option = $partner.select('[value="' + el.value + '"]');
			//$option.style({ 'display': 'none' });

			// var $deselect = $partnerSelected.append('div'); // appends a div
			// $deselect
			// 	.classed({ 'filter-deselect': true })
			// 	.text(el.value)
			// 	.datum({ 'option': $option.node() })
			// 	.append('span')
			// 		.text('x')
			// 		.on('click', function() { handleDeselect.call($option.node()) });

			$option.datum({ selected: true/*, deselect: $deselect.node()*/ });



            //console.log($partner.node().value);

			//$partner.node().value = 'none';

			partnerChange();


		};

		var partnerChange = function() {

				var   filters = []
					, ln = 0;




			$partner.selectAll('option')
				.each(function() {
					var self = this, $option = d3.select(self);
					//if(self.value == 'none') { return }
					//++ln;
					if(self.selected) { filters.push(self.value); console.log(self.value) }
                    console.log(self);
					//filters.push(self.value);
					//console.log(filters)
				});
			
			if(filters.length == 0) {
				cf.partnerDim.filter([]);
				$allPartners.node().checked = false

			} else if (filters.length == ln) {

				cf.partnerDim.filterAll();
				$allPartners.node().checked = true

			} else {

				cf.partnerDim.filterFunction(function(d) { return filters.indexOf(d) != -1 });
				$allPartners.node().checked = false

			}

			filterDispatcher.filtered()

		};




        var selectObj = $("#filterPartner");
        //$('#filterPartner');

        //console.log(selectObj.css('display'));
        var Selectize = selectObj.selectize({
            plugins: ['remove_button'],
            delimiter: ',',
            persist: false,
            create: function(input) {
                return {
                    value: input,
                    text: input
                }
            },
            sortField: 'text'

        });

        Selectize.on('change', function () {
            var   filters = [];
            $("#filterPartner option").each(function() {
            	var value = $(this).val();
                filters.push(value);
            });

           if (filters.length == 0) {
                cf.partnerDim.filterAll();
                $allPartners.node().checked = true
            } else {
               cf.partnerDim.filterFunction(function(d) { return filters.indexOf(d) != -1 });
               $allPartners.node().checked = false
		   }

            filterDispatcher.filtered();
        });

        var resetPartners = function () {


            Selectize[0].selectize.clear();
            cf.partnerDim.filterAll();
            $allPartners.node().checked = true;


        };
        //$partner.on('change', handleSelect);

        var partnerDoCheckAll = function(checked) {

			if(checked !== undefined) { $allPartners.node().checked = checked }

			if($allPartners.node().checked) {
			//	$partner.selectAll('option').each(function() {

					// var self = this;
                    //
                    //
					// if(self.value == 'none') { return }
                    //
					// var $option = d3.select(self);
                    //
					// if(!$option.datum().selected) {
					// 	handleSelect.call(self)
					// }



				//})
                cf.partnerDim.filter([]);
                $allPartners.node().checked = false;
                filterDispatcher.filtered()
			} else {
				$partner.selectAll('option').each(function() {

					var self = this;
					self.clear();

					if(self.value == 'none') { return }

					var $option = d3.select(self);

					if($option.datum().selected) {
						handleDeselect.call(self)
					}

				})
			}
		};

		$allPartners.on('change', function() {
			//partnerDoCheckAll()
		});

		/*=====  End of Partner filer          ======*/










		/*==================================
		=            Map legend            =
		==================================*/

		var $mapLegend = d3.select('#mapLegend').append('svg')
			.attr({
				'width': 290
				, 'height': 265
			})

		conf.filterStatus.forEach(function(status, index) {
			$mapLegend.append('circle')
				.attr({
					'r': 10
					, 'cx': 40
					, 'cy': 30 * index + 40
					, 'fill': status.color
				})
			$mapLegend.append('text')
				.text(status.text)
				.attr({
					'x': 60
					, 'y': 30 * index + 40
					, 'dominant-baseline': 'middle'
				})
		})

		var diameters = [
			{value: affectedMaxRound, diam: calculateDiameter(affectedMaxRound)}
			, {value: Math.round(affectedMaxRound * 0.5), diam: calculateDiameter(Math.round(affectedMaxRound * 0.5))}
			, {value: '<' + Math.round(affectedMaxRound * 0.25), diam: calculateDiameter(Math.round(affectedMaxRound * 0.25))} ]
		diameters.forEach(function(diameter) {
			$mapLegend.append('circle')
				.attr({
					'r': diameter.diam / 2
					, 'cx': 40
					, 'cy': 190 - diameter.diam / 2
					, 'stroke-width': 1
					, 'stroke': '#888'
					, 'fill': 'none'
				})

			$mapLegend.append('text')
				.text(diameter.value)
				.attr({
					'x': 40
					, 'y': 190 - diameter.diam + 2
					, 'dominant-baseline': 'text-before-edge'
					, 'text-anchor': 'middle'
				})
				.style({ 'font-size': '11px' })
		})

		$mapLegend.append('text')
			.attr({'x': 0, 'y': 140 })
			.selectAll('tspan')
			.data(['Size depends on number', 'of people affected (cumulative)'])
			.enter()
			.append('tspan')
				.attr({ 'x': 80, 'dy': '1.33em' })
				.text(function(d) { return d })


		$mapLegend.append('rect')
			.attr({
				'x': 19.5, 'y': 219.5
				, 'width': 40, 'height': 20
				, 'fill': 'url("#diagonalHatch")'
				, 'opacity': .5
				, 'stroke': '#666'
				, 'stroke-width': 1
			})
		$mapLegend.append('text')
			.attr({
				'x': 70, 'y': 220
				, 'dominant-baseline': 'text-before-edge'
			})
			.style({ 'line-height': '20px' })
			.text('Area along the "contact line"')


		/*=====  End of Map legend  ======*/










		/*==================================================================================
		=            Checkbox filters (cluster / status / type / need / oblast)            =
		==================================================================================*/
		
		var filterCheckboxWidget = function(selector, dimension, options) {

			var self = this;

			// handle options
			self.options = extendObject({
				checkAll: undefined
				, onChange: undefined
				, dataAccessor: function(dimension) { return dimension.group().all() }
				, valueAccessor: function(d) { return d.key }
				, textAccessor: function (d) { return d.key }
				, filtering: function(dimension, filters) {
					if(filters.length == 0) {
						dimension.filter([])
					} else if (filters.length == self.totalFilters) {
						dimension.filterAll()
					} else {
						dimension.filter(function(d) { return filters.indexOf(d) != -1 })
					}
				}
			}, options || {});

			// init
			var $container = d3.select(selector)
				, $labels
				, $checks
				, $allLabel
				, $allCheck;
			
			// make containers for each input[checkbox]

			$labels = $container.selectAll('div')
				.data( self.options.dataAccessor.call(self, dimension) )
				.enter()
				.append('div')
				.classed({ 'checkbox' : true })/*label*/

				/*i = i + 1; var result = id+i+''; */

			// init public properties
			self.container = $container.node();
			self.filters = $labels.data().map(function(d) { return d.key });
			self.totalFiltersNum = self.filters.length;

			// fill each checkbox
			$checks = $labels.append('input')
				.attr({
					'type': 'checkbox',
					'checked': 'checked',
					'value': function(d) { return self.options.valueAccessor.call(self, d) },
					'id': function(d) { return self.options.valueAccessor.call(self, d).replace(/\/|\s+/g, '_').toLowerCase() }
					/*string.toLowerCase()*/
				})
				.on('change', function() {
					self.filters = $checks.filter(':checked').data().map(function(d) { return self.options.valueAccessor.call(self, d) })

					if(options.checkAll) {
						$allCheck.node().checked = self.filters.length == self.totalFiltersNum
					}

					// self.options.filtering.call(self, dimension, self.filters)
					self.options.filtering.call(self, dimension, self.filters)

					if(typeof self.options.onChange == 'function') {
						self.options.onChange.call(self)
					}
				});

			// add text to each checkbox
			$labels.append('label')
				.text( function(d) { return self.options.textAccessor.call(self, d) } )
				.attr({'for': function(d) { return self.options.valueAccessor.call(self, d).replace(/\/|\s+/g, '_').toLowerCase() }})

			self.doCheckAll = function(checked) {
				var checkbox = self.options.checkAll ? $allCheck.node() : new String('dummy');
				if(checked !== undefined) checkbox.checked = checked

				self.filters = checkbox.checked ? $checks.data().map(function(d) { return self.options.valueAccessor.call(self, d) }) : []

				$checks.each(function() { this.checked = checkbox.checked })

				self.options.filtering.call(self, dimension, self.filters)

				if(typeof self.options.onChange == 'function') {
					self.options.onChange.call(self)
				}
			}

			// create "Check all"
			if(self.options.checkAll) {
				$allLabel = $container.insert('div', ':first-child')
				$allLabel
					.classed({ 'filtercheckbox-allcheck': true, 'checkbox' : true })

				$allCheck = $allLabel.append('input')
					.attr({'type': 'checkbox', 'checked': 'checked'})
					.on('change', function() {
						self.doCheckAll()
					});

				$allLabel.append('label')
					.text(self.options.checkAll)
			}

			return self
		};


		// init Cluster filter
		var filterCluster = new filterCheckboxWidget('#filterCluster', cf.clusterDim, {
			checkAll: 'All clusters'
			, onChange: function() { filterDispatcher.filtered() }
			, dataAccessor: function(dimension) { return conf.filterCluster }
		});

		// init Status filter
		var filterStatus = new filterCheckboxWidget('#filterStatus', cf.statusDim, {
			dataAccessor: function(dimension) { return conf.filterStatus }
			, textAccessor: function(d) { return d.text }
			, onChange: function() { filterDispatcher.filtered() }
		});
		// d3.select(filterStatus.container).selectAll('label').each(function(d) {
		// 	var $label = d3.select(this)

		// 	$label.insert('span', function() { return d3.select(this).select('input').node().nextSibling }) // custom check element
		// 		.html('&check;')
		// 		.style({'border-color': d.color, 'color': d.color })
		// })


		// init Alert type filter
		var filterType = new filterCheckboxWidget('#filterType', cf.typeDim, { checkAll: 'All alert types', onChange: function() { filterDispatcher.filtered() } })


		// init Needs filter
		var filterNeed = new filterCheckboxWidget('#filterNeed', cf.needDim, { checkAll: 'All need types', onChange: function() { filterDispatcher.filtered() } })


		// init Location filter
		var filterRaionDonetsk, filterRaionLuhansk, filtersRes;

		filterRaionDonetsk = new filterCheckboxWidget('#filterRaionDonetsk', cf.raionCodeDim, {
			checkAll: 'Donetska oblast'
			, dataAccessor: function(dimension) {
				return conf.filterOblastRaions.donetsk
			}
			, textAccessor: function(d) { return d.value }
			, filtering: function(dimension, filters) {
				filtersRes = d3.merge([filterRaionDonetsk.filters, filterRaionLuhansk.filters]);
				if(filtersRes.length == 0) {
					dimension.filter([])
				} else if (filtersRes.length == conf.filterOblastRaions.donetsk.length + conf.filterOblastRaions.luhansk.length) {
					dimension.filterAll()
				} else {
					dimension.filterFunction(function(d) { return filtersRes.indexOf(d) != -1 })
				}
			}
			, onChange: function() {
				filterDispatcher.filtered();
				raionFilterDispatcher.filtered(filtersRes)
			}
		});



		filterRaionLuhansk = new filterCheckboxWidget('#filterRaionLuhansk', cf.raionCodeDim, {
			checkAll: 'Luhanska oblast'
			, dataAccessor: function(dimension) {
				return conf.filterOblastRaions.luhansk
			}
			, textAccessor: function(d) { return d.value }
			, filtering: function(dimension, filters) {
				filtersRes = d3.merge([filterRaionDonetsk.filters, filterRaionLuhansk.filters]);
				if(filtersRes.length == 0) {
					dimension.filter([])
				} else if (filtersRes.length == conf.filterOblastRaions.donetsk.length + conf.filterOblastRaions.luhansk.length) {
					dimension.filterAll()
				} else {
					dimension.filterFunction(function(d) { return filtersRes.indexOf(d) != -1 })
				}
			}
			, onChange: function() {
				filterDispatcher.filtered();
				raionFilterDispatcher.filtered(filtersRes)
			}
		});

        /*function flatten(o) {
            var prefix = arguments[1] || "", out = arguments[2] || {}, name;
            for (name in o) {
                if (o.hasOwnProperty(name)) {
                    typeof o[name] === "object" ? flatten(o[name], prefix + name + '.', out) :
                        out[prefix + name] = o[name];
                }
            }
            return out;
        }*/
		var OblastRaions = function() {
			result = [];
			var OblastRaions = conf.filterOblastRaions;
			for (oblast in OblastRaions) {
				for (i = 0; i < OblastRaions[oblast].length; i++) {

                    var oblast_raions = {};

                    oblast_raions.id = OblastRaions[oblast][i].key;
                    oblast_raions.oblast = oblast;
                    oblast_raions.raion = OblastRaions[oblast][i].value;

                    result.push(oblast_raions);
				}
			}
            return result;
		};

        var loc = $('#locations-filter').selectize({
            options: OblastRaions(),
            optgroups: [
                {id: 'luhansk', name: 'Luhanska oblast'},
                {id: 'donetsk', name: 'Donetska oblast'}
            ],
            labelField: 'raion',
            valueField: 'id',
            optgroupField: 'oblast',
            optgroupLabelField: 'name',
            optgroupValueField: 'id',
            optgroupOrder: ['donetsk', 'luhansk'],
            searchField: ['raion'],
            plugins: ['optgroup_columns', 'remove_button'],
            delimiter: ',',
            persist: false,
            create: function(input) {
                return {
                    value: input,
                    text: input
                }
            }
        });

        loc.on('change', function () {
            var   filters = [];

            $("#locations-filter option").each(function() {
                var value = $(this).val();

                //console.log(value)
                filters.push(value);
            });

            if (filters.length == 0) {
                cf.raionCodeDim.filterAll();
                //$allPartners.node().checked = true
            } else {
                cf.raionCodeDim.filterFunction(function(d) { return filters.indexOf(d) != -1 });
                //$allPartners.node().checked = false
            }

            /*, filtering: function(dimension, filters) {
                filtersRes = d3.merge([filterRaionDonetsk.filters, filterRaionLuhansk.filters]);
                if(filtersRes.length == 0) {
                    dimension.filter([])
                } else if (filtersRes.length == conf.filterOblastRaions.donetsk.length + conf.filterOblastRaions.luhansk.length) {
                    dimension.filterAll()
                } else {
                    dimension.filterFunction(function(d) { return filtersRes.indexOf(d) != -1 })
                }
            }
            , onChange: function() {
                filterDispatcher.filtered();
                raionFilterDispatcher.filtered(filtersRes)
            }*/

            filterDispatcher.filtered(filters);
            raionFilterDispatcher.filtered(filters)
        });


        //console.log(conf.filterOblastRaions)
		//console.log(d3.merge([conf.filterOblastRaions.donetsk, conf.filterOblastRaions.luhansk]))
        //console.log(conf.filterOblastRaions.donetsk);
        //console.log(conf.filterOblastRaions.luhansk);
		/*=====  End of Checkbox filters (cluster / status / type / need / oblast)  ======*/










		/*==================================
		=            Data table            =
		==================================*/
		
		var $openDataTable = d3.select('#openDataTable')
			, $dataTableContainer = d3.select('.data-table')
			, $dataTable = d3.select("#dataTable")
			, $dataTableHead = $dataTable.append('thead')
			, $dataTableBody = $dataTable.append('tbody')
			, $dataTableClose = $dataTableContainer.select('#dataTableClose')
			, $dataTablePagination = $dataTableContainer.select('#dataTablePagination')
			, dataTablePage = 0

		$dataTableHead.append('tr')
			.html(conf.tplDataTableHead())

		var updateTable = function() {
			// Clear table, not to write d3 update function.
			// It will be tricky to update cell with all spoilers stuff.
			$dataTableBody.selectAll('tr').remove()
			
			var data = cf.allDim.bottom(Infinity)
			
			var $rows = $dataTableBody.selectAll('tr')
				.data( data.slice( dataTablePage * conf.paginationStep, (dataTablePage + 1) * conf.paginationStep) )

			$rows.enter().append('tr')
				.html(function(data) { return conf.tplDataTableRow({data: data}) })
				.selectAll('td')
					.each(function(data) {
						var td = this
							, $td = d3.select(td)
							, div = document.createElement('div')
							, $div = d3.select(div)
						
						// wrap td content into div
						while(td.hasChildNodes()) {
							div.appendChild(td.childNodes[0])
						}
						td.appendChild(div)

						$div.classed({ 'data-table-spoiled': true })
						
						// it would be fine to reset this property on window resize, but i dont care
						$div.classed({ 'data-table-spoiled-ready': div.offsetHeight < div.scrollHeight })
					
						$div
							.on('mouseenter', function() {
								if(div.offsetHeight >= div.scrollHeight) { return }

								$div.append('div')
									.classed({ 'data-table-spoiler': true })
									.style({ 'height': div.scrollHeight + 'px' })
									.text($div.text())
							})
							.on('mouseleave', function() {
								$div.select('.data-table-spoiler').remove()
							})
					})

			$dataTablePagination.selectAll('li').remove()

			var $pages = $dataTablePagination.selectAll('li')
				.data( d3.range(Math.ceil(data.length / conf.paginationStep)) )

			$pages.enter().append('li')
				.text(function(d) { return d + 1 })
				.classed('active', function(d) { return d == dataTablePage })
				.on('click', function(d) {
					dataTablePage = d
					updateTable()
				})
		}

		$openDataTable.on('click', function() {
			if( $dataTableContainer.style('display') == 'none' ) {
				$dataTableContainer.style({ 'display':  null })
				$openDataTable.classed({ 'active': true })
				$body.classed({ 'data-table-opened': true })
				dataTablePage = 0
				updateTable()
			} else {
				$dataTableContainer.style({ 'display': 'none' })
				$openDataTable.classed({ 'active': false })
				$body.classed({ 'data-table-opened': false })
			}
		})

		$dataTableClose.on('click', function() {
			$dataTableContainer.style({ 'display': 'none' })
			$openDataTable.classed({ 'active': false })
			$body.classed({ 'data-table-opened': false })
		})


		filterDispatcher.on('filtered.updateTable', function() {
			if($dataTableContainer.style('display') != 'none') {
				dataTablePage = 0
				updateTable()
			}
		})

		/*=====  End of Data table  ======*/










		/*===================================================================
		=            Show counter of alerts with filters applied            =
		===================================================================*/

		var $filterCounter = d3.select('#filterCounter')

		var updateFilterCounter = function() {
			$filterCounter.html(conf.tplFilterCounter({ data: { value: cf.allGrp.value(), total: cf.size() } }))
		}

		filterDispatcher.on('filtered.updateCounter', function() {
			updateFilterCounter()
		})

		updateFilterCounter()

		/*=====  End of Show counter of alerts with filters applied  ======*/









		/*====================================
		=            Reset button            =
		====================================*/
		
		var $resetFilters = d3.select('#resetFilters');


		var ResetAll = function() {
            filterCluster.doCheckAll(true);
            //partnerDoCheckAll(true);

			resetPartners();

			filterStatus.doCheckAll(true);
            filterType.doCheckAll(true);
            filterNeed.doCheckAll(true);
            filterRaionDonetsk.doCheckAll(true);
            filterRaionLuhansk.doCheckAll(true);

			resetMonthpicker(slider);
		};


		$resetFilters.on('click', function() {
            ResetAll()
		});


		// $(document).ready(function(){
		// 	$('#resetFilters').on('click', function(){
		// 		filterCluster.doCheckAll(true)
		// 		filterStatus.doCheckAll(true)
		// 		filterType.doCheckAll(true)
		// 		filterNeed.doCheckAll(true)
		// 		filterRaionDonetsk.doCheckAll(true)
		// 		filterRaionLuhansk.doCheckAll(true)
		// 	});
		// });
		
		/*=====  End of Reset button  ======*/


	})

})()
