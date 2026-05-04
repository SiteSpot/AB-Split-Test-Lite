(function($) {

	var bt_bb_ab_posts = JSON.parse(bt_bb_ab_predirect_vars.posts);

	if( !jQuery.isEmptyObject(bt_bb_ab_posts) )
	{												
		jQuery.each(bt_bb_ab_posts, function(index, items) {
			
			bt_bb_ab_predirect_vars.select += '<optgroup label="'+ index +'">';
				jQuery.each(items, function(i, post) {				
					if( post.post_title != '' ) {
						bt_bb_ab_predirect_vars.select += '<option value="'+ post.id +'">'+ post.post_title +'</option>';
					}					
				});
			bt_bb_ab_predirect_vars.select += '</optgroup>';
		});						
	}

})(jQuery);