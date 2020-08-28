//
// replate function _stickyMenu
// to Solve the bug of $('.panel.wrapper') has 'margin-bottom' on mobile 
// ----------------------
// 
// 
_create: function(){
	this._removeColon(); //add new line
}，


_stickyMenu: function(){
	var $stickyMenu = $('.sticky-menu').first();
	if( $stickyMenu.length > 0 ){
		var threshold = $stickyMenu.height() + $stickyMenu.offset().top;
		var headerHeight = $stickyMenu.height();
		$(window).scroll(function(){
			var screenWidth=$(window).width();
			if(screenWidth<768){
				$('.panel.wrapper').first().css({'margin-bottom':'0px'});
			}else{
				var $win = $(this);
				var newHeight = 0;
				if($('.sticky-menu.active').length > 0)
					newHeight = $('.sticky-menu.active').height();																			
				var curWinTop = $win.scrollTop() + newHeight;
				if(curWinTop > threshold){
					$stickyMenu.addClass('active');
					$('.panel.wrapper').first().css({'margin-bottom':headerHeight+'px'});
				}else{
					$('.panel.wrapper').first().css({'margin-bottom':'0px'});
					$stickyMenu.removeClass('active');
				}
			}
			
		});
		$(window).resize(function(){
			var screenWidth=$(window).width();
			if(screenWidth<768){
				$('.panel.wrapper').first().css({'margin-bottom':'0px'});
			}
		});
	}
},

//If there are two colons, remove one of them on customerconnect page
_removeColon: function(){
	$('.box-account.block-dashboard-info .info-list p .label').each(function(){
		var lableVal=$(this).html();
		console.log(lableVal);
		if(lableVal.substring((lableVal.length - 3),lableVal.length)==':: '){
			lableVal=lableVal.substring(0,(lableVal.length - 2));
			$(this).html(lableVal);
		}
	});
}