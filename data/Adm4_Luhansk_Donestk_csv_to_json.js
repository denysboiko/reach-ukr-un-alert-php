#!/usr/bin/env node

var fs = require('fs')
	, csv = require('csv')


fs.readFile('./Adm4_Luhansk_Donestk.csv', function(error, fileData) {
	csv.parse(fileData, function(error, data) {
		var result = {}

		for(var i = 1, record; record = data[i], i < data.length; ++i) {
			var oblast = result[record[0]] = result[record[0]] || [
				record[3], {}
			]

			var raion = oblast[1][record[1]] = oblast[1][record[1]] || [
				record[4], {}
			]

			var settlement = raion[1][record[2]] = raion[1][record[2]] || [
				record[5]
				, parseFloat(record[6])
				, parseFloat(record[7])
			]

		}



		/**
		 *
		 * Reorder data by oblasts / raions / settlements name.
		 * 
		 * Typically, when KOTAUU used as array index
		 * when this JSON will be imported in PHP
		 * all data will be sorted like KOTAUU is typicall numeric index of array.
		 * So we set leading 'c' chatacter to KOTAUU code to prevent breaking of sorting.
		 *
		 */

		var resultSorted = {}
		Object.keys(result).sort().forEach(function(oblastName) {
			var oblast = result[oblastName]

			var newOblast = resultSorted['c' + oblast[0]] = [ oblastName, {} ]

			Object.keys(oblast[1]).sort().forEach(function(raionName) {
				var raion = oblast[1][raionName]
				var newRaion = newOblast[1]['c' + raion[0]] = [ raionName, {} ]

				Object.keys(raion[1]).sort().forEach(function(settlementName) {
					var settlement = raion[1][settlementName]

					var newSettlement = newRaion[1]['c' + settlement[0]] = [settlementName, settlement[1], settlement[2]]
				})
			})


		})


		console.log(JSON.stringify(resultSorted))

	})
})