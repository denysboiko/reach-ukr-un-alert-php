<?php
	$dataFile = './data/alerts.csv?m=' . (filemtime('data/alerts.csv') % 12345678);


	$dataFiles = [];
	$dataFilesDir = scandir('data/alerts/');

	for($i = 2; $i < count($dataFilesDir); ++$i) {
		$dataFiles[] = 'data/alerts/' . $dataFilesDir[$i] . '?m=' . (filemtime('data/alerts/' . $dataFilesDir[$i]) % 12345678);
	}

?>
<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>Alert system Ukraine</title>
		
		<link href='https://fonts.googleapis.com/css?family=Roboto:400,500' rel='stylesheet' type='text/css'>
		
		<link rel="stylesheet" href="libs/leaflet/leaflet.css" />
		<script src="libs/leaflet/leaflet.js"></script>

		<script src="https://d3js.org/d3.v3.min.js" charset="utf-8"></script>
		
		<script src="libs/crossfilter.min.js"></script>

		<script src="libs/underscore-min.js"></script>


		<link rel="stylesheet" href="css/style.css" />

		<script type="text/javascript" src="js/monthpicker.js"></script>
		<script type="text/javascript" src="js/lrscroll.js"></script>


		
		<script type="text/javascript" src="https://code.jquery.com/jquery-3.1.1.min.js"></script>
		<script src="js/changeLang.js"></script>
		<script type="text/javascript">

				
				var setLang = function(newLang) {
					window.localStorage['lang'] = newLang;
				}

				if(!window.localStorage['lang']) {
					setLang('en');
				}
				changeLang(window.localStorage['lang']);
				$(document).ready(function(){
					$('.button-lang-'+window.localStorage['lang']).css({'background': '#ccc','border-color': '#888','color': '#000'});
					
				});
		</script>
		<style type="text/css">
			*{
				font-family: "Arial Narrow",Roboto,verdana,sans-serif !important;
			}
			body{
				font-size: 11pt; 
			}
		</style>
	</head>
	<body>
		<div class="header-wrapper">
			<header>
				<button class="header-button fleft translate resetFilters">RESET FILTERS</button>
				<a href="/UKRAINE/alerts/admin"><button class="header-button fleft translate">ADD AN ALERT</button></a>
				<div class="header-button fleft">
					<button class="button-lang-en translate"  onclick="setLang('en');location.reload();">EN</button>
					<button class="button-lang-ua translate"  onclick="setLang('ua');location.reload();">UA</button>
					<button class="button-lang-ru translate"  onclick="setLang('ru');location.reload();">RU</button>
				</div>
				<span class="header-text" id="filterCounter"></span>
				<div class="header-separator"></div>

				<script type="text/template" id="tplFilterCounter"><%= data.value %> <span class="translate">alerts selected of</span> <%= data.total %></script>
				<button class="header-button translate" id="openDataTable">DATA TABLE</button>
				<a href="<?= $dataFile ?>" class="button header-button translate">DOWNLOAD DATA</a>
				
			</header>
		</div>

		<div class="main-wrapper">
			<main>
				<div id="map"></div>

				<div class="bottom-wrapper">
					<div class="bottom">
						<div id="monthpicker"></div>
					</div>
				</div>

				<div id="mapLegend" class="translate-filter"></div>

				<div class="data-table" style="display: none">
					<div class="data-table-close" id="dataTableClose">&times;</div>
					<div class="data-table-overflow">
						<table id="dataTable"></table>
					</div>
					<ul id="dataTablePagination"></ul>
				</div>
			</main>
		</div>


		<div class="aside-wrapper">
			<aside>
				<div class="filter filter-cluster translate-filter">
					<h3>Cluster</h3>
					<div id="filterCluster"></div>
				</div>

				<div class="filter filter-partner translate-filter">
					<h3>Response partner</h3>
					<label>
						<input type="checkbox" id="filterPartnersAll" /><span>All response partners</span>
					</label>
					<div id="filterPartnerSelected"></div>
					<select id="filterPartner"></select>
				</div>


				<div class="filter filter-status translate-filter">
					<h3>Status</h3>
					<div id="filterStatus"></div>
				</div>

				<div class="filter filter-type translate-filter">
					<h3>Alert type</h3>
					<div id="filterType"></div>
				</div>


				<div class="filter filter-need translate-filter">
					<h3>Needs type</h3>
					<div id="filterNeed"></div>
				</div>

				<div class="filter filter-oblast">
				</div>

				<div class="filter filter-location translate-filter">
					<h3 class="dropdown-button" onclick="this.classList.toggle('open');">Location</h3>
					<div class="dropdown-list">
						<div id="filterRaionDonetsk" class="dropdown-self">
							<a href="#" class="dropdown-button" onclick="this.classList.toggle('open');"></a>
						</div>
						<div id="filterRaionLuhansk" class="dropdown-self">
							<a href="#" class="dropdown-button" onclick="this.classList.toggle('open');"></a>
						</div>
					</div>
				</div>

			</aside>
		</div>

	
		<script type="text/template" id="tplPopup">
			<p class="popup-title"><%= data.title %></p>
			<p>
				<b>Custer:</b> <%= data.cluster.join(', ') %>
			</p>
			<p>
				<b>Number of covered/affected people:</b> <%= data.covered %>/<%= data.affected %>
			</p>
			<p>
				<b>Type alert:</b> <%= data.type %>
			</p>
			<p>
				<b>Type needs:</b> <%= data.need %>
			</p>
			<p>
				<b>Context:</b><br />
				<span style="white-space:pre-wrap"><%= data.context %></span>
			</p>
			<p>
				<b>Description:</b><br />
				<span style="white-space:pre-wrap"><%= data.description %></span>
			</p>
			<% if(data.infoLink.trim()) { %>
				<p>
					<b>Additional information:</b> <a href="<%= data.infoLink %>">Additional information</a>
				</p>
			<% } %>
			<% if(data.conflictRelated) { %>
				<p>
					<b>Relayed to conflict:</b> <%= data.conflictRelated %>
				</p>
			<% } %>

			<p>
				<b>Date referral:</b> <%= conf.dateFormat(data.date) %>
			</p>

			<% if(data.conflictRelated) { %>
				<p>
					<b>Gap-not covered need:</b> <%= data.notCoveredNeeds %>
				</p>
			<% } %>
		</script>

		<script type="text/template" id="tplDataTableHead">
			<td style="width:120px;">Oblast / <wbr />Raion / <wbr />Settlement</td>
			<td style="width:80px;">Covered / Affected</td>
			<td style="width:100px;">Type alerts</td>
			<td style="width:140px;">Type needs</td>
			<td style="width:20%;">Description</td>
			<td style="width:20%;">Context</td>
			<td style="width:100px;">Additional information</td>
			<td style="width:80px;">Conflict related</td>
			<td style="width:100px;">Date referral</td>
			<td style="width:80px;">Gaps-not covered need</td>
		</script>

		<script type="text/template" id="tplDataTableRow">
			<td><%= data.oblast %> / <%= data.raion %> / <%= data.settlement %></td>
			<td style="text-align:center;"><%= data.affected %> / <%= data.covered %></td>
			<td style="text-align:center;"><%= data.type %></td>
			<td><%= data.need.join(', ') %></td>
			<td><%= data.description %></td>
			<td><%= data.context %></td>
			<td style="text-align:center;"><% if(data.infoLink.trim()) { %><a href="<%= data.infoLink %>">Link</a><% } %></td>
			<td style="text-align:center;"><%= data.conflictRelated %></td>
			<td style="text-align:center;"><%= conf.dateFormat(data.date) %></td>
			<td style="text-align:center;"><%= data.notCoveredNeeds %></td>
		</script>

		<script type="text/javascript" src="data/ukr_adm1b.js"></script>
		<script type="text/javascript" src="data/raions_donetsk_luhansk.js"></script>
		<script type="text/javascript" src="data/grey_zone.js"></script>
		<script type="text/javascript">
			window.conf = {
				map: {
					// tiles: 'http://{s}.tile.osm.org/{z}/{x}/{y}.png'
					// tiles: 'http://c.tile.stamen.com/toner-lite/{z}/{x}/{y}.png'
					tiles: 'https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}.png'
					, center: [48.1, 38.2]
					, zoom: 8
					, minZoom: 6
					, maxZoom: 12
					, maxBounds: [
						[44 - 2, 22] // south coords of ukraine + space to bottom datepicker, Ukraine west
						, [53 + 4, 41 + 16] // Ukraine north + some space to popups, east coords of ukraine + space to make center near Donetsk/Luhansk on minZoom
					]
				}
				, admUkrainian: window.adm1
				, admRaions: window.raionsDonetskLuhansk
				, greyZone: window.greyZone
				, dateFormat: d3.time.format('%e %b %Y')
				, dateFormatString: '%e %b %Y'
				, dateFormatShort: d3.time.format('%b %y')
				, dateFormatShortString: '%b %y'
				, dateParse: d3.time.format('%Y-%m-%d')
				, markerMinDiam: 20
				, markerMaxDiam: 50
				, data: {
					referals: {
						urls: ['<?= implode("', '", $dataFiles) ?>']
						, loader: d3.csv
						, fields: {
							settlement: 'SETTLEMENTS'
							, oblast: 'OBLAST'
							, raion: 'RAION'
							, raionCode: 'ADMIN2'
							, latitude: 'latitude'
							, longitude: 'longitude'
							, affected: 'NO_AFFECTED'
							, date: 'DATE_REFERAL'
							, status: 'STATUS'
							, cluster: 'CLUSTER'
							, partner: 'RESPONSE_PARTNER'
							, type: 'ALERT_TYPE'
							, need: 'NEED_TYPE'
							, covered: 'GAP_BENEFICIARIES'
							, context: 'CONTEXT'
							, description: 'DESCRIPTION'
							, infoLink: 'ADDITIONAL_INFO_LINK'
							, notCoveredNeeds: 'UNCOVERED_NEEDS'
							, conflictRelated: 'CONFLICT_RELATED'
						}
					}
				}
				, filterStatus: [
					{key: 'resolved', text: 'Resolved', color: '#70A800'}
					, {key: 'Addressed but unresolved', text: 'Addressed, not resolved', color: '#F69E61'}
					, {key: 'Not addressed', text: 'Not addressed', color: '#EE5859'}
				]
				, filterCluster: [
					{ key: 'Education' }
					, { key: 'Food Security' }
					, { key: 'Health/Nutrition' }
					, { key: 'Livelihoods/Early Recovery' }
					, { key: 'Logistics' }
					, { key: 'Protection' }
					, { key: 'Emergency Shelter/NFI' }
					, { key: 'Water Sanitation Hygiene' }
				]
				, markerSpacer: 20
				, tplPopup: _.template(d3.select('#tplPopup').html())
				, tplDataTableRow: _.template(d3.select('#tplDataTableRow').html())
				, tplDataTableHead: _.template(d3.select('#tplDataTableHead').html())
				, paginationStep: 100
				, filterOblast: {
					// keyInSourceCode: 'Key data in document'
					donetsk: 'Donetska'
					, luhansk: 'Luhanska'
				}
				, filterOblastRaions: {
					donetsk: [
						{ key: '1420600000', value: 'Amvrosiivskyi' }, { key: '1410300000', value: 'Artemivska' }
						, { key: '1410200000', value: 'Avdiivska' }, { key: '1420900000', value: 'Bakhmutskyi' }
						, { key: '1410900000', value: 'Debaltsevcka' }, { key: '1411500000', value: 'Dobropilska' }
						, { key: '1422000000', value: 'Dobropilskyi' }, { key: '1411600000', value: 'Dokuchaievska' }
						, { key: '1410100000', value: 'Donetska' }, { key: '1411700000', value: 'Druzhkivska' }
						, { key: '1411300000', value: 'Dymytrivska' }, { key: '1411200000', value: 'Dzerzhynska' }
						, { key: '1410600000', value: 'Horlivska' }, { key: '1415000000', value: 'Khartsyzka' }
						, { key: '1412500000', value: 'Kirovska' }, { key: '1412600000', value: 'Kostiantynivska' }
						, { key: '1422400000', value: 'Kostiantynivskyi' }, { key: '1412900000', value: 'Kramatorska' }
						, { key: '1413200000', value: 'Krasnoarmiiska' }, { key: '1422700000', value: 'Krasnoarmiiskyi' }
						, { key: '1413300000', value: 'Krasnolymanska' }, { key: '1423000000', value: 'Krasnolymanskyi' }
						, { key: '1413500000', value: 'Makiivska' }, { key: '1423300000', value: 'Marinskyi' }
						, { key: '1412300000', value: 'Mariupolska' }, { key: '1423600000', value: 'Novoazovskyi' }
						, { key: '1413600000', value: 'Novohrodivska' }, { key: '1420300000', value: 'Oleksandrivskyi' }
						, { key: '1423900000', value: 'Pershotravnevyi' }, { key: '1413800000', value: 'Selydivska' }
						, { key: '1415300000', value: 'Shakhtarska' }, { key: '1425200000', value: 'Shakhtarskyi' }
						, { key: '1414100000', value: 'Slovianska' }, { key: '1424200000', value: 'Slovianskyi' }
						, { key: '1414400000', value: 'Snizhnianska' }, { key: '1424500000', value: 'Starobeshivskyi' }
						, { key: '1424800000', value: 'Telmanivskyi' }, { key: '1414700000', value: 'Torezka' }
						, { key: '1421200000', value: 'Velykonovosilkivskyi' }, { key: '1421500000', value: 'Volnovaskyi' }
						, { key: '1421700000', value: 'Volodarskyi' }, { key: '1414800000', value: 'Vuhledarska' }
						, { key: '1415500000', value: 'Yasynuvatska' }, { key: '1425500000', value: 'Yasynuvatskyi' }
						, { key: '1412000000', value: 'Yenakiivska' }, { key: '1412100000', value: 'Zhdanivska' }
					]
					, luhansk: [
						{ key: '4411200000', value: 'Alchevska' }, { key: '4420300000', value: 'Antratsytivskyi' }
						, { key: '4410300000', value: 'Antratsytska' }, { key: '4420900000', value: 'Bilokurakynskyi' }
						, { key: '4420600000', value: 'Bilovodskyi' }, { key: '4410500000', value: 'Briankivska' }
						, { key: '4411000000', value: 'Kirovska' }, { key: '4411400000', value: 'Krasnodonska' }
						, { key: '4421400000', value: 'Krasnodonskyi' }, { key: '4411600000', value: 'Krasnolutska' }
						, { key: '4421600000', value: 'Kreminskyi' }, { key: '4410100000', value: 'Luhanska' }
						, { key: '4422200000', value: 'Lutuhynskyi' }, { key: '4411800000', value: 'Lysychanska' }
						, { key: '4422500000', value: 'Markivskyi' }, { key: '4422800000', value: 'Milovskyi' }
						, { key: '4423100000', value: 'Novoaidarskyi' }, { key: '4423300000', value: 'Novopskovskyi' }
						, { key: '4423600000', value: 'Perevalskyi' }, { key: '4412100000', value: 'Pervomaiska' }
						, { key: '4423800000', value: 'Popasnianskyi' }, { key: '4412300000', value: 'Rovenkivska' }
						, { key: '4412500000', value: 'Rubizhanska' }, { key: '4412900000', value: 'Sievierodonetska' }
						, { key: '4424500000', value: 'Slovianoserbskyi' }, { key: '4413100000', value: 'Stakhanovska' }
						, { key: '4424800000', value: 'Stanychno-Luhanskyi' }, { key: '4425100000', value: 'Starobilskyi' }
						, { key: '4424000000', value: 'Svativskyi' }, { key: '4412700000', value: 'Sverdlovska' }
						, { key: '4424200000', value: 'Sverdlovskyi' }, { key: '4425400000', value: 'Troitskyi' }
					]
				}
				, tplFilterCounter: _.template(d3.select('#tplFilterCounter').html())
			}

			// colorbrewer['Set1']['3'] except yellow, green and red, because it used in markers
			conf.raionColors = {
				'1410100000': '#1E90FF', '1410200000': '#00BFFF', '1410300000': '#0fa9FF', '1410600000': '#0f60FF', '1410900000': '#00cFeF', '1411200000': '#87CEfB'
				, '1413200000': '#1E90FF', '1413300000': '#00BFFF', '1413500000': '#0fa9FF', '1413600000': '#0f60FF', '1413800000': '#00cFeF', '1414100000': '#87CEfB'
				, '1414400000': '#1E90FF', '1414700000': '#00BFFF', '1414800000': '#0fa9FF', '1415000000': '#0f60FF', '1422700000': '#87CEfB', '1423000000': '#00cFeF'
				, '1423300000': '#00BFFF', '1423600000': '#1E90FF', '1423900000': '#0fa9FF', '1424200000': '#0f60FF', '1424500000': '#00cFeF', '1424800000': '#87CEfB'
				, '1425200000': '#87CEfB', '1425500000': '#00cFeF', '1415300000': '#0fa9FF', '1415500000': '#0f60FF', '1420300000': '#1E90FF', '1420600000': '#00BFFF'
				, '1420900000': '#87CEfB', '1421200000': '#0f60FF', '1421500000': '#0fa9FF', '1421700000': '#00BFFF', '1422000000': '#00cFeF', '1422400000': '#1E90FF'
				, '1411300000': '#0fa9FF', '1411500000': '#00BFFF', '1411600000': '#0f60FF', '1411700000': '#87CEfB', '1412000000': '#1E90FF', '1412100000': '#00cFeF'
				, '1412300000': '#0f60FF', '1412500000': '#1E90FF', '1412600000': '#00cFeF', '1412900000': '#00BFFF', '4410100000': '#0fa9FF', '4410300000': '#87CEfB'
				, '4410500000': '#1E90FF', '4411000000': '#00BFFF', '4411200000': '#0fa9FF', '4411400000': '#0f60FF', '4411600000': '#00cFeF', '4411800000': '#87CEfB'
				, '4412100000': '#1E90FF', '4412300000': '#0fa9FF', '4412500000': '#00BFFF', '4412700000': '#0f60FF', '4412900000': '#00cFeF', '4413100000': '#87CEfB'
				, '4420300000': '#00BFFF', '4420600000': '#0fa9FF', '4420900000': '#00cFeF', '4421400000': '#1E90FF', '4421600000': '#0f60FF', '4422200000': '#87CEfB'
				, '4422500000': '#00cFeF', '4422800000': '#1E90FF', '4423100000': '#87CEfB', '4423300000': '#00BFFF', '4423600000': '#0f60FF', '4423800000': '#0fa9FF'
				, '4424000000': '#0fa9FF', '4424200000': '#00BFFF', '4424500000': '#00cFeF', '4424800000': '#0f60FF', '4425100000': '#1E90FF', '4425400000': '#87CEfB'
			}
			// make all raionColors a bit lighter
			for(var k in conf.raionColors) {
				var c = d3.hsl(conf.raionColors[k])
				c.l += .1
				conf.raionColors[k] = c.toString()
			}
			
		</script>
		<script type="text/javascript" src="js/script.js"></script>
	</body>
</html>
