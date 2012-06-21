<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Nested Sets Example Page</title>
		<link type="text/css" href="<?php echo base_url(); ?>assets/css/smoothness/jquery-ui-1.8.17.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-ui-1.8.17.custom.min.js"></script>
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery.mjs.nestedSortable.js"></script>

		<style type="text/css">

		body { font-size: 62.5%; }
		#dialog-form label,#dialog-form  input { display:block; }
		 input.text { margin-bottom:12px; width:95%;	}
			fieldset { padding:0; border:0;	 }
		 h1 { font-size: 1.1px; margin: .6em 0; }
		.ui-dialog .ui-state-error { padding: .3em; }
		.validateTips { border: 1px solid transparent; padding: 0.3em; }
		
		.placeholder {
			border: 1px dashed #4183C4;
			-webkit-border-radius: 3px;
			-moz-border-radius: 3px;
			border-radius: 3px;
		}
		

		.mjs-nestedSortable-error {
			background: #fbe3e4;
			border-color: transparent;
		}
		
		ol {
			margin: 0;
			padding: 0;
			padding-left: 30px;
			font-size: 14px;
		}
		ol.sortable, ol.sortable ol {
			margin: 0 0 0 25px;
			padding: 0;
			list-style-type: none;
		}
		ol.sortable {
			margin: 1em 0;
		}
		.sortable li {
			margin: 7px 0 0 0;
			padding: 0;
		}

		.sortable li div.cont  {
			border: 1px solid #d4d4d4;
			position: relative;
			z-index: 1;
			-webkit-border-radius: 3px;
			-moz-border-radius: 3px;
			border-radius: 3px;
			border-color: #D4D4D4 #D4D4D4 #BCBCBC;
			padding: 3px;
			margin: 0;
			background: #f6f6f6;
			background: -moz-linear-gradient(top,  #ffffff 0%, #f6f6f6 47%, #ededed 100%);
			background: -webkit-gradient(linear, left top, left bottom, color-stop(0%,#ffffff), color-stop(47%,#f6f6f6), color-stop(100%,#ededed));
			background: -webkit-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
			background: -o-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
			background: -ms-linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
			background: linear-gradient(top,  #ffffff 0%,#f6f6f6 47%,#ededed 100%);
			filter: progid:DXImageTransform.Microsoft.gradient( startColorstr='#ffffff', endColorstr='#ededed',GradientType=0 );
			
		}
		
		.sortable li div span.handle {
			cursor: pointer;
		}	
		.deletebutton {
			float:right;position:relative;right:0px;top:0px;display:block;width:12px;height:12px;margin:0;padding:0;background-image:url('<?php echo base_url(); ?>assets/img/Delete.gif');cursor:pointer;opacity:0.4;filter:alpha(opacity=40);z-index: 2 !important;}
		.deletebutton:hover {opacity:1.0;filter:alpha(opacity=100);}
		.editbutton {
			float:right;position:relative;right:0px;top:0px;display:block;width:12px;height:12px;margin:0;padding:0;background-image:url('<?php echo base_url(); ?>assets/img/edit.gif');cursor:pointer;opacity:0.4;filter:alpha(opacity=40);}
		.editbutton:hover {opacity:1.0;filter:alpha(opacity=100);}
		</style>
		
		<script>
		$(document).ready(function() {
		
		var name = $( "#name" )
		var bNested = false;
		refresh_menu();
		
		// Add button
		$( "#addnew" )
			.button()
			.click(function() {$( "#dialog-form" ).dialog( "open" );
		});
		
		// Edit button
		$( "#edit" )
			.button()
			.click(function() {
				if (bNested == false){
					bNested = true;	
					$("span", this).text("edit: off");		
				} else {
					bNested = false;
					$("span", this).text("edit: on");
				}
				refresh_menu();
		});
		
		// dialog form
		$( "#dialog-form" ).dialog({
			autoOpen: false,
			height: 175,
			width: 350,
			modal: true,
			buttons: {
				"Add new section": function() {
			        $.ajax({
			        	url: "<?php echo base_url(); ?>index.php/nested/ajax_addnew",
			        	type: "POST",
			        	data: ({ title : name.val()}),
			        	success: function(data){refresh_menu();}
			        });	
					refresh_menu();
					$(this).dialog( "close" );
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			},
			close: function() {}
		});
				
		// Making nested 
		function makeNested() { 
			$('ol.sortable').nestedSortable('destroy');
			$('ol.sortable').nestedSortable({
				disableNesting: 'no-nest',
				forcePlaceholderSize: true,
				handle: 'span.handle',
				helper:	'clone',
				items: 'li',
				maxLevels: 4,
				opacity: .6,
				placeholder: 'placeholder',
				revert: 250,
				tabSize: 25,
				tolerance: 'pointer',
				toleranceElement: '> div',
				
				update : function (event, ui)  {
				   var result = $('ol.sortable').nestedSortable('toArray');
				   var item = ui.item;
				   var moveto = "";
				   var type = "";
				   
						if ($(item).index() != 0){
						moveto = $(item).prev().attr("id");
						type = 'addnext';
						} else {
						moveto =($($(item).parents().get(0)).attr("id") == "root") ? "1" : $($(item).parents().get(1)).attr("id") ;
						type = 'appendfirst';
						}
					   					  
					$.ajax({
					  url: "<?php echo base_url(); ?>index.php/nested/ajax_sort",
					  type: "POST",
					  data: ({ item : $(item).attr("id").match(/[0-9]+/g)[0], moveto : moveto.match(/[0-9]+/g)[0], type : type}),
					  success: function(data){refresh_menu();}
					 });
				   }
				   
			});
			}
			
	
			// getting Json and parsing menu..
			function refresh_menu() { 
			 $.ajax({
				 url: "<?php echo base_url(); ?>index.php/nested/get_json",
			  type: "POST",
			  data: ({ title : $("#t_addnew").val()}),
			  success: function(data){ 
			   var items = $.parseJSON(data);
			   var k = 1, c = items.length, j, nestedhtml = '';
			  	$.each(items, function(i,item){ // 
			  	 if(item.depth == j)	 {nestedhtml += '</li>';}
			  	 else if(parseInt(item.depth) > j){nestedhtml += '<ol>';}
			  	 else if(parseInt(item.depth) < j){nestedhtml += Array(Math.abs(parseInt(item.depth)-parseInt(j+1))).join('</li></ol>') + "</li>";}	// class="ui-state-default"
				 nestedhtml += '<li id="item_' + item.id + '"><div class="cont"><span class="handle" ><a class="ui-icon ui-icon-grip-dotted-vertical" style="float:left;"></a></span><span class="item_title">' + item.title + '</span><small> (' + item.num_pages + ' item)</small><span class="lft" id="'+item.lft+'"></span></div>';
				 if(k == c) { nestedhtml += Array(Math.abs(parseInt(item.depth)-1)).join('</li></ol>') + "</li>" };
				 k++;
				 j = parseInt(item.depth);
				 });
				 $('#root').html(nestedhtml);
				 $("<div class='deletebutton'></div>").appendTo("#root.sortable li div.cont");
				 $("<div class='editbutton'></div>").appendTo("#root.sortable li div.cont");
				 
				 $(".deletebutton").hide();
				 $(".editbutton").hide();
				 
				 if (bNested == false){
					$(".handle").attr("style","display:none");
				 }else{
					$('#root.sortable li div').bind({
					 mouseenter: function() {$(this).find(".deletebutton").show();$(this).find(".editbutton").show();},
					 mouseleave: function() {$(this).find(".deletebutton").hide();$(this).find(".editbutton").hide();},});	
					 makeNested();
				 }
				 
				 $('.deletebutton').bind('click', function() {
					if (confirm("Are you sure to delete this item ?")==true)
					{	
  				 	  $.ajax({
					  	 url: "<?php echo base_url(); ?>index.php/nested/ajax_delete",
					  	 type: "POST",
					  	 data: ({ lft : $(this).parent().find("span.lft").attr("id")}),
					  	 success: function(data){}
					  });	
					  refresh_menu();
					}
				  });
				  
				 $('.editbutton').bind('click', function() {
					if (data = prompt("hede", $(this).parent().find("span.item_title").html()))
					{	
					 
  					  $.ajax({
					  	 url: "<?php echo base_url(); ?>index.php/nested/ajax_edit",
					  	 type: "POST",
					  	 data: ({ title : data, 
					  	 		  lft : $(this).parent().find("span.lft").attr("id")}),
					  	 success: function(data){ console.log(data);}
					  });	
					  refresh_menu();
				
					}
				  });
				  
			  }
			 });	
			}
						
		});
		</script>
	</head>
	<body>
		
		<button id="edit">edit: on</button>
		<button id="addnew">add new item</button>

		<div style="width:480px;">
		  <ol class="sortable" id="root"></ol>
		</div>		

        <div id="dialog-form" title="Create new section">
           <p class="validateTips">All form fields are required.</p>
           <form>
            <fieldset>
              <label for="name">Title</label>
              <input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all" />
            </fieldset>
           </form>
        </div>

	</body>
</html>


