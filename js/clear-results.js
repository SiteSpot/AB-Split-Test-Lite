jQuery(document).ready(function(){
  jQuery('#bt_clear_experiment_results').click(function(event){
    var eid = jQuery(this).attr('eid');
    event.preventDefault();
    if (jQuery('#restart-confirm').val().toLowerCase() == jQuery('#title').val().toLowerCase()) {
      var data = {
        'action': 'bt_clear_experiment_results',
        'eid': eid,
        'bt_action': 'clear',
        'nonce': bt_exturl.clear_results_nonce,
      };

      // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
      jQuery.post(bt_ajaxurl, data, function(response) {
        response = JSON.parse(response);
        alert(response.text);
        if(response.success)
          location.reload();
      });
    }
    
  });
});