
    var el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
	
	const { __, _x, _n, _nx } = wp.i18n;
	const htmlToElem = ( html ) => wp.element.RawHTML( { children: html } );
	var wodtext ="";
	var wodevent="";
	var SelectControl = wp.components.SelectControl; 
	var TextControl = wp.components.TextControl; 
	var ToggleControl = wp.components.ToggleControl; 
	var InspectorControls = wp.blockEditor.InspectorControls; 
	var AlignmentToolbar = wp.editor.AlignmentToolbar; 
	var BlockControls = wp.editor.BlockControls;
	var Fragment = wp.element.Fragment;
	var programselect,locationselect;
	
registerBlockType( 'wp-wdfy-integration-of-wodify/wod', {
    title: __("Wodify WOD",'wp-integration-of-wodify'),

    icon: 'universal-access-alt',
	description: __('Display the workout for the selected day, program and location. For advanced setting please use shortcode [wdfywod]','wp-integration-of-wodify'),
    category: 'wpwdfy',
	
	 attributes: {
		  date: { type: 'string',   },
		  program: { type: 'string',   },
		  location: { type: 'string',   },
            
        },
	
    edit: function(props) {
		 var wdfydate = props.attributes.date;
		 var wdfyprogram = props.attributes.program;
		  var wdfylocation = props.attributes.location;
			 
		 function onChangeWodDate(newdate){
			 props.setAttributes ( {date: newdate});
			 wdfydate = newdate;
		 };
		 
		 function onChangeProgram(newprogram){
			 props.setAttributes ( {program: newprogram});
			 wdfyprogram = newprogram;
		 };
		 
		 function onChangeLocation(newlocation){
			 props.setAttributes ( {location: newlocation});
			 wdfylocation = newlocation;
		 };
	 
		function wpwdfyAPICall() {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						
						var str=""+this.responseText;
						str = str.replace(/\"/g,'');
						var lookups = str.split(";");
						var str1 = lookups[0];
						var str2 = lookups[1];				
						
						var arr = str1.split(",");
						var len = arr.length;
						var arr1;
						programselect = [];
						arr1 = {'value': '', 'label': 'Default'};
							programselect.push(arr1);
						for (var i = 0; i < len; i++) {
							
							arr1 = {'value': arr[i], 'label': arr[i]};
							programselect.push(arr1);
						}	
						
						var arr = str2.split(",");
						var len = arr.length;
						locationselect = [];
						arr1 = {'value': '', 'label': 'Default'};
							locationselect.push(arr1);
						for (var i = 0; i < len; i++) {
							
							arr1 = {'value': arr[i], 'label': arr[i]};
							locationselect.push(arr1);
						}	
				
			}
			};
			xhttp.open("POST", wpApiSettings.root + 'wp-integration-of-wodify/v1/lookups/', false);
			xhttp.setRequestHeader("Content-type", "application/json");
			xhttp.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			xhttp.send();
		}

		function wpwdfyGetWod() {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						
						str=JSON.parse(this.responseText);
						
						$wodtext=str;	
				
			}
			};
			xhttp.open("GET", wpApiSettings.root + 'wp-integration-of-wodify/v1/blockwod/?date='+ props.attributes.date+'&program='+props.attributes.program+'&location='+props.attributes.location, false);
			xhttp.setRequestHeader("Content-type", "application/json");
			xhttp.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			xhttp.send();
		}

		if (!programselect)
			wpwdfyAPICall();
	
		if (!wodtext)
			wpwdfyGetWod()
		
	   if (programselect[1].value=="")
	   {
		  //Error
		  return (
				el(
					Fragment,
					null,
				el(
                    InspectorControls,
					null,
					el('p',{ }, '' ),
					el('p',{ }, 'Error accessing Wodify' ),
										
					
                    )
               
				));		  
	   }
	   else
       return (
				el(
					Fragment,
					null,
					el(
						InspectorControls,
						null,
						el('p',{ }, '' ),
						el(SelectControl,{label: "WOD date",value: wdfydate, 
						options:  [
							{ value: '+0', label: __('Today','wp-integration-of-wodify') },
							{ value: '+1', label: __('Tomorrow','wp-integration-of-wodify') },
							{ value: '-1', label: __('Yesterday','wp-integration-of-wodify') }],
						onChange: onChangeWodDate,
						}),
						
						el(SelectControl,{label: "Location",value: wdfylocation, 
						options:  locationselect,
						onChange: onChangeLocation,
						}),
						
						el(SelectControl,{label: "Program",value: wdfyprogram, 
						options:  programselect,
						onChange: onChangeProgram,
						}),
					),
   				 htmlToElem($wodtext)
				)
			);									
    },

    save: function(props) {
	  return null;
    },
} );

//Block events

registerBlockType( 'wp-wdfy-integration-of-wodify/events', {
    title: __("Wodify Events",'wp-integration-of-wodify'),

    icon: 'calendar',
	description: __('Display a list of upcoming classes for a selected program including schema.org markup.','wp-integration-of-wodify'),
    category: 'wpwdfy',
	
	 attributes: {
		  
		  program: { type: 'string',   },
		  numdays: { type: 'string',   },
		  showheader: { type: 'string',   },
            
        },
	
    edit: function(props) {
		 var wdfyprogram = props.attributes.program;
		  var wdfynumdays = props.attributes.numdays;
		    var wdfyshowheader = props.attributes.showheader;
			 
		
		 
		 function onChangeProgram(newprogram){
			 props.setAttributes ( {program: newprogram});
			 wdfyprogram = newprogram;
		 };
		 
		 function onChangeNumdays(newnumdays){
			 props.setAttributes ( {numdays: newnumdays});
			 numdays = newnumdays;
		 };
		  function onChangeShowheader(newshowheader){
			 props.setAttributes ( {showheader: newshowheader});
			 showheader = newshowheader;
		 };
	 
		function wpwdfyAPICall() {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						
						var str=""+this.responseText;
						str = str.replace(/\"/g,'');
						var lookups = str.split(";");
						var str1 = lookups[0];
						var str2 = lookups[1];				
						
						var arr = str1.split(",");
						var len = arr.length;
						var arr1;
						programselect = [];
						arr1 = {'value': '', 'label': 'Default'};
							programselect.push(arr1);
						for (var i = 0; i < len; i++) {
							
							arr1 = {'value': arr[i], 'label': arr[i]};
							programselect.push(arr1);
						}	
						
						var arr = str2.split(",");
						var len = arr.length;
						locationselect = [];
						arr1 = {'value': '', 'label': 'Default'};
							locationselect.push(arr1);
						for (var i = 0; i < len; i++) {
							
							arr1 = {'value': arr[i], 'label': arr[i]};
							locationselect.push(arr1);
						}	
				
			}
			};
			xhttp.open("POST", wpApiSettings.root + 'wp-integration-of-wodify/v1/lookups/', false);
			xhttp.setRequestHeader("Content-type", "application/json");
			xhttp.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			xhttp.send();
		}
	 
		function wpwdfyGetEvents() {
			var xhttp = new XMLHttpRequest();
			xhttp.onreadystatechange = function() {
					if (this.readyState == 4 && this.status == 200) {
						
						str=JSON.parse(this.responseText);
						$wodevent=str;	
				
			}
			};
			xhttp.open("GET", wpApiSettings.root + 'wp-integration-of-wodify/v1/blockevents/?numdays='+ props.attributes.numdays+'&program='+props.attributes.program+'&showheader='+props.attributes.showheader, false);
			xhttp.setRequestHeader("Content-type", "application/json");
			xhttp.setRequestHeader( 'X-WP-Nonce', wpApiSettings.nonce );
			xhttp.send();
		}



		if (!programselect)
			wpwdfyAPICall();
		if (!wodevent)
			wpwdfyGetEvents();
		
	   if (programselect[1].value=="")
	   {
		  //Error
		  return (
				el(
					Fragment,
					null,
				el(
                    InspectorControls,
					null,
					el('p',{ }, '' ),
					el('p',{ }, 'Error accessing Wodify' ),
										
					
                    )
		  ));		  
	   }
	   else
       return (
				el(
					Fragment,
					null,
					el(
						InspectorControls,
						null,
						el('p',{ }, '' ),
						
						
						
						el(SelectControl,{label: "Program",value: wdfyprogram, 
						options:  programselect,
						onChange: onChangeProgram,
						}),
						
						el(SelectControl,{label: "Show events for upcoming ",value: wdfynumdays, 
						options:  [
							{ value: '3', label: __('3 days','wp-integration-of-wodify') },
							{ value: '7', label: __('1 week','wp-integration-of-wodify') },
							{ value: '10', label: __('10 days','wp-integration-of-wodify') },
							{ value: '14', label: __('2 weeks','wp-integration-of-wodify') },
							{ value: '30', label: __('1 month','wp-integration-of-wodify') },
							],
						onChange: onChangeNumdays,
						}),
						
						
						el(SelectControl,{label: "Show headers",value: wdfyshowheader, 
						options:  [
							{ value: 'false', label: __('No','wp-integration-of-wodify') },
							{ value: 'true', label: __('Yes','wp-integration-of-wodify') }],
						onChange: onChangeShowheader,
						}),
						
						el('p',{}, __('For schema.org event markup, please make sure to setup the site image and/or program image URLs in the plugin settings.','wp-wdfy-integration-of-wodify') ),
						
						el('p',{}, __('For advanced setting please use shortcode [wdfyevents].','wp-wdfy-integration-of-wodify') ),
												
					),
					htmlToElem($wodevent)
					
				)
			);									
    },

    save: function(props) {
	  return null;
    },
} );






