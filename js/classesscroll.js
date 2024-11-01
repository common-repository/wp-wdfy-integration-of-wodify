jQuery(document).ready(function($) {
   
    $(".wdfy_upcoming_classes").each(function() {
        var self = $(this);
        var autoscroll = 0;
		var numclasses = 3;
        var elementClasses = $(this).attr('class').split(' ');
        for (var i = 0; i < elementClasses.length; i++) {
            if (elementClasses[i].indexOf('autoscroll-') != -1) autoscroll = elementClasses[i].replace('autoscroll-', '');
        }
		for (var i = 0; i < elementClasses.length; i++) {
            if (elementClasses[i].indexOf('wdfynumclasses-') != -1) numclasses = +elementClasses[i].replace('wdfynumclasses-', '');
        }

        self.carouFredSel({
            direction: "up",
            items: {
                visible: (self.children().length >= numclasses ? numclasses : self.children().length),
                height: 'variable'
            },
            scroll: {
                items: 1,
                easing: "swing",
                pauseOnHover: true
            },
            prev: {
                button: self.next().children('#upcoming_classes_prev')
            },
            next: {
                button: self.next().children('#upcoming_classes_next')
            },
            auto: {
                play: (parseInt(autoscroll) ? true : false)
            }
        });
        
		self.find("li a.wdfy_upcoming_classes_container, li>span").hover(function() {
             $(".wdfy_upcoming_classes").trigger('updateSizes');
        });
		
		self.find("li a.wdfy_upcoming_classes_container, li>span").mousedown(function() {
			self.trigger('pause', 'immediate',true);
        });
		
		
    });
    
	$(window).resize(function() {
        $(".wdfy_upcoming_classes").trigger('reInit', true);
    });


    
});



