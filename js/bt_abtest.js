// js called in iframes

(function(a,b,c,d) {
	"use strict";

	var args = {};

	var parse_args = function() {

		a.abtest.args.forEach(function(item) {
			if( item.length > 1 ) {
				args[item[0]] = item[1];
			}
		});
	}

	var validate = function() {

		parse_args();

		var errors 	= 0;

		if( args.hasOwnProperty('eid') ) {
			if( isNaN(+args.eid) && !isFinite(args.eid) ) {
				console.warn("AB Split Test: eid must be an integer.");	
				errors += 1;
			}
		} else {
			console.warn("AB Split Test: requires abtest(eid, <experiment id>) parameter.");	
			errors += 1;			
		}
		if( args.hasOwnProperty('variation') ) {
			if( args.variation == '' ) {
				console.warn("AB Split Test: variation cannot be empty.");		
				errors += 1;
			}
		} else {
			console.warn("AB Split Test: requires abtest(variation, <experiment variation>) parameter.");		
			errors += 1;
		}

		if( errors > 0 ) {
			return false;
		}

		return true;
	}

	if( !validate() ) { return; }

	var add_conversion = function() {

		var src 		= document.getElementById('bt_abtest.js');
		var src 		= parseURL(src.src);
		var endpoint 	= src.protocol +'//'+ src.hostname +'/wp-json/bt_bb_ab_conversion/v1/add';			
		var bt_cookie 	= 'bt_ab_conversion_'+ args.eid +'_'+ args.variation;
		var data 		= abstGetCookie(bt_cookie);

		if( data != 'active' ) {
			var bt_xhttp = new XMLHttpRequest();

			bt_xhttp.onreadystatechange = function() {
				if (this.readyState === 4) {
					if (this.status >= 200 && this.status < 400) {
						var data = JSON.parse(this.responseText);		
						if( data.status !== undefined && data.status < 1 ) {
							console.warn(data.message);							
						} else {
							console.log(data);
						}
					} else {}
				}
			};	

			bt_xhttp.open('POST', endpoint);
			bt_xhttp.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
			bt_xhttp.send('eid='+ args.eid +'&variation='+ args.variation);		

			abstSetCookie(bt_cookie, 'active', 1000);	
		}		
	}

	function abstGetCookie(cname) {
	     var name = cname + "=";
	     var ca = document.cookie.split(';');
	     for(var i=0; i<ca.length; i++) {
	        var c = ca[i];
	        while (c.charAt(0)==' ') c = c.substring(1);
	        if(c.indexOf(name) == 0)
	           return c.substring(name.length,c.length);
	     }
	     return "";
	}

	function abstSetCookie(cname, cvalue, exdays) {
		if(!exdays) exdays = 365;
	    var d = new Date();
	    d.setTime(d.getTime() + (exdays*24*60*60*1000));
		var domain = window.location.hostname.split('.').slice(-2).join('.');
	    var expires = "expires="+ d.toUTCString();
		document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/;domain=." + domain + "; SameSite=None; Secure";
	}

	function parseURL(url) {
	    var parser = document.createElement('a'),
	        searchObject = {},
	        queries, split, i;
	    // Let the browser do the work
	    parser.href = url;
	    // Convert query string to object
	    queries = parser.search.replace(/^\?/, '').split('&');
	    for( i = 0; i < queries.length; i++ ) {
	        split = queries[i].split('=');
	        searchObject[split[0]] = split[1];
	    }
	    return parser;
	}

	add_conversion();

})(window, document, location, history);