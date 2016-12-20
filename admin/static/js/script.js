(function() {
	$('input[type=date]').each(function() {
		if(this.type == 'text') $(this).datepicker({dateFormat: 'yy-mm-dd'})
	})
})()