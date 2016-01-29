(function() {
	'use strict';

	function act(e) {
		if( this.checked && this.value > 3 ) {
			this.form.submit();
		} else {
			var messageWrap = this.parentNode.parentNode.parentNode.querySelector('.wpkb-rating-message');
			messageWrap.style.display = 'block';
			messageWrap.querySelector('textarea').focus();
		}
	}

	var messageWraps = document.querySelectorAll('.wpkb-rating-message');
	[].forEach.call(messageWraps, function(e) { e.style.display = 'none'; });

	var options = document.querySelectorAll( '.wpkb-rating-option' );
	[].forEach.call(options, function(e) {
		e.style.display = 'none';
		e.addEventListener('change', act);
	});
})();