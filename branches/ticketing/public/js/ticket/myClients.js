(function (window) {
    'use strict';
    
    var $ = window.$,
    	$archiveForms = $('.archive-form'),
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
    
    function registerEventHandlers () { 
    	$archiveForms.on('submit', handleArchiveFormSubmit);
    }
    
    $(window).on('load', function () {
    	registerEventHandlers();
    });
    
}(this));