(function (window) {
    'use strict';
    
    var $ = window.$,
    	$archiveForms = $('.archive-form'),
    	$table = $('#my-clients-list'),
    	$confirmElement = $('#archive-client-dialog-confirm'),
    	userChoices = {};
    
    function handleArchiveFormSubmit(e) {
    	var $form = $(this),
			id = $form.find('[name="user_id"]').val(),
			options = {
				resizable: false,
    			height:140,
    			modal: true,
		      	buttons: {
		      		"Yes": function() {
		      			userChoices[id] = true;
		      			$(this).dialog("close");
	      				
		      			// performs a submit, but the browser will not emit a "submit" event
		      			$form.get(0).submit();
			        },
			        Cancel: function() {
			          userChoices[id] = false;
			          $(this).dialog("close");
		        	}
		      	}
			};
    		
		if ( !userChoices[id] ) {
    		e.preventDefault();
			$confirmElement.dialog(options);
		}
    }
    
    function handleArchiveLinkClick(e) {
    	$(this).next('form').trigger('submit');
    }
    
    function registerEventHandlers () {
    	$archiveForms.on('submit', handleArchiveFormSubmit);
    	$table.on('click', 'a.delete', handleArchiveLinkClick);
    }
    
    $(window).on('load', function () {
    	registerEventHandlers();
    });
    
}(this));