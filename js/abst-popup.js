// Create-test pop-up (on_page_test_create).

jQuery(document).ready(function(){
  jQuery('body').on('click','.collapsed>h3, .expanded>h3',function(e){
    if (jQuery(this).closest('#configuration_settings').length) {
      return;
    }
    jQuery(this).parent().toggleClass('expanded').toggleClass('collapsed');
}); 
//select input post_title
  jQuery('#post_title').focus();
  jQuery('body').on('click','.abst-popup-close',function(){
    window.parent.postMessage('abclosemodal','*');
  });
});
// keys pressed in this iframe never reach the builder page, so Escape is handled here.
// capture phase runs before select2, so Escape on an open dropdown only closes the dropdown
window.addEventListener('keydown', function(e){
  if (e.key === 'Escape' && !document.querySelector('.select2-container--open'))
    window.parent.postMessage('abclosemodal','*');
}, true);
