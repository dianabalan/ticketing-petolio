(function (window) {
    'use strict';
    
    var $ = window.$,
    	selector = '.pagefirst, .pageprev, .pagenext, .pagelast',
    	$container = $("#users-list-wrapper"),
    	clientsMap = {};
    
    function checkSelectedUsers () {
    	$container.find('.user-id').each(function (index, element) {
			var $element = $(element),
				id = $element.val();
			
			if ( clientsMap[id] ) {
				$element.attr('checked', true);
			}
		});
    }
    
    function handlePaginationLinkClick(e) {
    	var $this = $(this),
    		href = $this.attr('href');
    	
    	e.preventDefault();
    	$container.load(href, function () {
    		attachPaginationHandlers();
    		checkSelectedUsers();
    	});
    }
    
    function handleCheckboxClick(e) {
    	var $this = $(this),
    	    isChecked = $this.is(":checked"),
    	    id = $this.val();
    	
    	if ( isChecked ) {
    		clientsMap[id] = true;
    	} else {
    		delete clientsMap[id];
    	}
    }
    
    function handleSave(e) {
    	var $this = $(this);
    	
    	$.each(clientsMap, function (key, value) {
    		 $("<input>").attr({ 'type':'hidden', 'name':'client_id[]' }).val(key).appendTo($this);
    	});
    }
    
    function attachPaginationHandlers() {
    	$(selector).on('click', handlePaginationLinkClick);
    }
    
    function registerEventHandlers () {
    	attachPaginationHandlers();
    	$container.on('click', '.user-id', handleCheckboxClick);
    	$('#save-clients').on('submit', handleSave);
    }
    
    $(window).on('load', function () {
    	registerEventHandlers();
    });
    
}(this));