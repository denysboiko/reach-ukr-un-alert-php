window.lrscroll = function(selector, options) {
	// init params
	var $el = d3.select(selector);

	var options = options || {};
	options.anchors = options.anchors || $el.node().childNodes;
	
	// init elements
	var $wrapper = d3.select( $el.node().parentNode ).insert('div', function(){ return $el.node() })
		.classed({'lrscroll-wrapper': true})
		.style({ 'position': 'relative' });

	var $left = $wrapper.append('div')
		.classed({'lrscroll-left': true})
		.style({ 'position': 'absolute', 'left': 0 });
	var $right = $wrapper.append('div')
		.classed({'lrscroll-right': true})
		.style({ 'position': 'absolute', 'right': 0 });

	var $container = $wrapper.append('div')
		.classed({ 'lrscroll-container': true })
		.style({ 'overflow': 'hidden', 'lrscroll-container': true });
	
	var $lrscroll = $container.append('div')
		.classed({ 'lrscroll': true })
		.style({ 'width': '9999em', 'position': 'relative' });

	$wrapper.style({ 'padding-left': $left.style('width'), 'padding-right': $right.style('width')})


	$lrscroll.append(function() { return $el.node() });

	// main part
	$left.on('click', function() {
		var shift = parseInt($lrscroll.style('margin-left')) || 0;

		d3.selectAll(options.anchors).each(function() {
			if(this.offsetLeft < -shift && this.offsetLeft + this.offsetWidth >= -shift) {
				$lrscroll.style({'margin-left': (-this.offsetLeft) + 'px'})
			}
		})
	});

	$right.on('click', function() {
		var shift = parseInt($lrscroll.style('margin-left')) || 0
			, width = parseInt($container.style('width'));

		d3.selectAll(options.anchors).each(function() {
			if(this.offsetLeft <= (-shift + width) && this.offsetLeft + this.offsetWidth > (-shift + width)) {
				$lrscroll.style({'margin-left': - (this.offsetLeft + this.offsetWidth - width) + 'px'})
			}
		})
	});

	return $lrscroll.node()
};