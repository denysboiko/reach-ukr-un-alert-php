window.Monthpicker = function(el, beginMonth, endMonth, options) {

	var self = this;

	// check arguments
	beginMonth = d3.time.month.floor(beginMonth);
	endMonth = d3.time.month.ceil(endMonth);


	options = options || {};
	options.format = options.format || '%b %y';
	options.change = options.change || function() {};

	// inital things
	var format = d3.time.format(options.format);
	
	var $window = d3.select(window);


	// create elements
	var $el = d3.select(el);
	$el.classed({'monthpicker': true});

	self.$el = $el;
	
	var $range = $el.append('div');
	$range.classed({ 'monthpicker-range': true })
		.style({ 'margin-right': '-9999em' });
	
	var $picker = $el.append('div');
	$picker.classed({'monthpicker-picker': true});

	var $begin = $picker.append('div');
	$begin.classed({'monthpicker-begin': true});

	var $end = $picker.append('div');
	$end.classed({'monthpicker-end': true});

	var $months = $range.selectAll('div.monthpicker-month')
		.data(d3.time.months(beginMonth, endMonth))
		.enter()
		.append('div')
			.classed({'monthpicker-month': true})
			.style({'float': 'left'})
			.text(function(date) { return format(date) });


	var $beginMonth = $months.filter(':first-child'), $endMonth = $months.filter(':last-child');
	
	// set initial position
	this.startPos = function(){
		var length = d3.selectAll('.monthpicker-range .monthpicker-month')[0].length;
		//console.log(d3.selectAll('.monthpicker-range .monthpicker-month')[0])
		var beginMonth = d3.select('.monthpicker-range .monthpicker-month:nth-child('+(length-5)+')');
        console.log(beginMonth);

		$beginMonth = beginMonth;
		setPos($beginMonth, $endMonth);
		options.change(d3.time.month.floor($beginMonth.datum()), d3.time.month.ceil($endMonth.datum().setSeconds(1)));

		//var width = d3.select('.lrscroll-container').node().getBoundingClientRect().width;

		//console.log(width)

		var picker = d3.select('.monthpicker-picker').node().getBoundingClientRect();

		//var ml = picker.left+picker.width-width-390;
		//d3.select('.lrscroll').style('margin-left',-ml+'px');


	};
	var setPos = function($beginMonth, $endMonth) {

		var beginNode = $beginMonth.node()
			, endNode = $endMonth.node();
		var beginLeft = beginNode.offsetLeft
			, beginWidth = beginNode.offsetWidth
			, endLeft = endNode.offsetLeft
			, endWidth = endNode.offsetWidth;

		$picker.classed({'monthpicker-onemonth': beginNode == endNode  });

		$picker.style({'left': parseInt(beginLeft + beginWidth/2) + 'px'});
		$picker.style({'width': parseInt(endLeft - beginLeft) + 'px'})
	};

	setPos($beginMonth, $endMonth);

	var dragBegin = d3.behavior.drag();
	$begin.call(dragBegin);
	dragBegin.on('drag', function() {
		var x = d3.mouse($range.node())[0];

		var month;
		$months.each(function() {
			var self = this;
			var left = self.offsetLeft
				, right = left + self.offsetWidth;
				if(x >= left && x <= right) { month = self }
		});
		$beginMonth = month ? d3.select(month) : $months.filter(':first-child');
		if($beginMonth.datum() >= $endMonth.datum()) { $endMonth = $beginMonth }
		setPos($beginMonth, $endMonth)
	});
	dragBegin.on('dragend', function() {
		options.change(d3.time.month.floor($beginMonth.datum()), d3.time.month.ceil($endMonth.datum().setSeconds(1)))
	});

	var dragEnd = d3.behavior.drag()
	$end.call(dragEnd)
	dragEnd.on('drag', function() {
		var x = d3.mouse($range.node())[0]

		var month
		$months.each(function() {
			var self = this
			var left = self.offsetLeft
				, right = left + self.offsetWidth
				if(x >= left && x <= right) { month = self }
		})

		$endMonth = month ? d3.select(month) : $months.filter(':last-child')

		if($endMonth.datum() <= $beginMonth.datum()) { $beginMonth = $endMonth }
		setPos($beginMonth, $endMonth)
	})
	dragEnd.on('dragend', function() {
			options.change(d3.time.month.floor($beginMonth.datum()), d3.time.month.ceil($endMonth.datum().setSeconds(1)))
	})


	self.reset = function() {
		$beginMonth = $months.filter(':first-child'), $endMonth = $months.filter(':last-child')
		setPos($beginMonth, $endMonth)
		options.change(d3.time.month.floor($beginMonth.datum()), d3.time.month.ceil($endMonth.datum().setSeconds(1)))
	}



	return self
}