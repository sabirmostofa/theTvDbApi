jQuery(document).ready(function($){
		var search = window.location.search;
	    var usearch =search.match(/showurl=(.*)?&/);
        var tsearch =search.match(/pagetitle=(.*)/);
        if(usearch !== null)
			usearch = decodeURIComponent(usearch[1]);
        if(tsearch !== null)
			tsearch = decodeURIComponent(tsearch[1]);
		if(jQuery("#show-iframe").length != 0)
			if(jQuery("#show-iframe").attr('src').length < 7 )
				jQuery("#show-iframe").attr('src', usearch);
		if( jQuery("#iframe-title").length != 0 )	
			if( jQuery("#iframe-title").text().length < 2  )
				jQuery("#iframe-title").text(tsearch);
	
    jQuery('a').click(function(e){       
        

        var href = jQuery(this).attr('href');
        var id = jQuery(this).attr('id');
        var rel = jQuery(this).attr('rel');
        var target = jQuery(this).attr('target');
        if( target == '_blank' && rel == 'nofollow' && id.match('link-')){
            e.preventDefault();
            var hostname = window.location.hostname;
            var path = window.location.pathname;
            var search = window.location.search;
            var protocol = window.location.protocol;
            var loc = protocol+'://'+hostname;
           //window.location.href= ktSettings.site_url+'/iframe-page/?show_url='+encodeURIComponent(href);
          window.location.href= ktSettings.site_url+'/iframe-page/?showurl='+encodeURIComponent(href)+'&pagetitle='+encodeURIComponent(jQuery(this).text());

        }
    })
    jQuery('input[src="http://freecast.com/wp-content/uploads/2011/08/header_but01.png"]').click(function(e){
        e.preventDefault();
        jQuery.ajax({
            type :  "post",
            url : ktSettings.ajaxurl,
            timeout : 5000,
            data : {
                'action' : 'controll-soft-button'
            },
            success :  function(data){
                if(data=='true')
                    window.location.href='http://softlocker.net/ca.asp?l=964&p=sDzGCHsUA9ue';
                else{
                    alert('You have already received your 4 software registration codes, please contact support if you need further assistance');
                }
            }
        })


    });


})
