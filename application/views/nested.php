<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<title>Nested Sets Example Page</title>
		<link type="text/css" href="<?php echo base_url(); ?>assets/css/smoothness/jquery-ui-1.8.17.custom.css" rel="stylesheet" />	
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-1.7.1.min.js"></script>
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery-ui-1.8.17.custom.min.js"></script>
		<script type="text/javascript" src="<?php echo base_url(); ?>assets/js/jquery.ui.nestedSortable.js"></script>

		<style type="text/css">
		.placeholder {
			background-color: #cfcfcf;
		}
		.ui-nestedSortable-error {
			background:#fbe3e4;
			color:#8a1f11;
		}
		ol {
			margin: 0;
			padding: 0;
			padding-left: 30px;
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

		.sortable li div  {
			border: 1px solid black;
			padding: 3px;
			margin: 0;
			/*cursor: move;*/
		}
		.sortable li div span  {
			cursor: pointer;
		}	
		</style>	
	</head>
	<body>
		<script>
		
		$(function makeNested() {
			$('ol.sortable').nestedSortable({
				disableNesting: 'no-nest',
				forcePlaceholderSize: true,
				handle: 'span.handle',
				helper:	'clone',
				items: 'li',
				maxLevels: 3,
				opacity: .6,
				placeholder: 'placeholder',
				revert: 250,
				tabSize: 25,
				tolerance: 'pointer',
				toleranceElement: '> div',
				
				update : function (event, ui)  {
		         var result = $('ol.sortable').nestedSortable('toArray');
		         var item = ui.item;//.attr('id');
		   
		         var moveto = "";
		         var type = "";
		         
			         if ($(item).index() != 0)
			         {
				        moveto = $(item).prev().attr("id");
				        type = 'addnext';
			         } 
			         else 
			         {
					    moveto =($($(item).parents().get(0)).attr("id") == "root") ? "1" : $($(item).parents().get(1)).attr("id") ;
				        type = 'appendfirst';
			         }
			        			        
					$.ajax({
					  url: "<?php echo base_url(); ?>index.php/nested/ajax_sort",
					  type: "POST",
					  data: ({ item : $(item).attr("id").match(/[0-9]+/g)[0], moveto : moveto.match(/[0-9]+/g)[0], type : type}),
					  success: function(data){ 
					  }
					 });
		         }
			});

			$('#makeitdisable').click(function(){
				$('ol.sortable').nestedSortable('destroy');
				$('span.handle').hide();
			})
			
			$('#makeitenable').click(function(){
				makeNested();
				$('span.handle').show();
			})

		});
		</script>
			<input type="submit" name="makeitdisable" id="makeitdisable" value="Disable Sorting" />
			<input type="submit" name="makeitdenable" id="makeitenable" value="Enable Sorting" />

		<div style="width:480px;">
		<ol class="sortable" id="root">
		<?php  
		$menu = $sections;
		 $i = 1;
		 $active_menu = '';
		 $menu_items = count($menu);
		 foreach($menu as $item)
		 {
			$item = (object)$item;
		    if(isset($j) && $item->depth == $j)    echo "</li>";
		    elseif(isset($j) && $item->depth > $j) echo "<ol>";
		    elseif(isset($j) && $item->depth < $j) echo str_repeat('</li></ol>', abs($item->depth-$j)) . "</li>";

		    echo '<li id="item_'.$item->id.'"  class="ui-state-default"><div><span class="handle"><a class="ui-icon ui-icon-grip-dotted-vertical" style="float:left;"></a></span>'. $item->title . '<small> (' . $item->num_pages . ' item)</small></div>';

		    if($i == $menu_items) echo str_repeat('</li></ol>', abs($item->depth-1)) . "</li>";

		    $i++;
		    $j = $item->depth;
		    $k = $item->lft;
		 }
		?>
		</ol>
		</div>
	</body>
</html>


