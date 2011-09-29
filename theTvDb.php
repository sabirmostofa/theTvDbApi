<?php
/*
 * Template Name: theTvDb
*/



get_header();
global $wpdb,$wptheTvDbApi;

/*
 $wptheTvDbApi -> remove_all();
	var_dump( $wpdb -> query('truncate table wp_thetvdb_series') );
	$series_ids = $wpdb->get_col("SELECT ID FROM $wpdb->posts WHERE post_type = 'series'");
	$all_series = $wpdb->get_col("SELECT id FROM wp_thetvdb_series");
	var_dump($series_ids);
	var_dump($all_series);
exit;

*/
$paged = (get_query_var ('paged'))? get_query_var ('paged') :1; 



$my_query = $wptheTvDbApi -> get_query();
//var_dump($my_query);
//var_dump($my_query->s);

//If the total page number > 1 we will show the pagination

//PHP Image code Below this line--- to show above the pagination links ***PHP CODE****

?>
<!-- HTML Image Code Below this line -->
<img class="aligncenter size-full wp-image-2336" title="networks" src="http://freecast.com/wp-content/uploads/2011/09/networks.jpg" alt="" width="936" height="75" />

<div style="<?php if($paged == 1) echo 'float:left;margin-top:25px;' ?>">
<?php
if(isset($_REQUEST['search-tvdbseries'])):
	echo "<p style=\"display:inline\">Search Result For \"{$my_query -> s}\". Total {$my_query -> found_posts} Records found. </p>" ;
	echo "<a href=\"".get_permalink($post -> ID)."\">Clear Search</a><br/>"; 
endif;
 ?>
</div>
<form method='get'action='<?php echo get_permalink($post -> ID); ?>' style="float:right;margin:25px 20px 0 0;">
<input type='text' name='search-tvdbseries' value="<?php echo $a = (isset($my_query -> s))? $my_query -> s:'';  ?>"/>
<input type='submit' name='tvdb-search' value='Search'/>
<input type='hidden' name='page_id' value="<?php echo $post -> ID  ?>"/>
</form>


<?php
if($my_query->max_num_pages>1)
if(function_exists('wp_pagenavi'))
wp_pagenavi(array(
   'query' =>$my_query   
   ));
   
echo "<div style=\"clear:both;\"></div>";

$counter = 0;

  
  foreach ($my_query-> posts as $single):
	 $post_id = $single ->ID;
	 $meta = get_post_meta($post_id,'series_meta');
	 
	 
	 extract($meta[0],EXTR_OVERWRITE );
	$p_ext= $wptheTvDbApi -> return_file_ext($poster);
	$base=wp_upload_dir();
	$site_url = site_url();
	$p_img = $site_url."/?get_image=1&poster_id={$id}";
	
	echo "";
	
	?>
	
	<?php if(++$counter%2==1):?>
	<?php include("/uploads/2011/09/networks.jpg"); ?>
	<div style="margin-top:20px;">
	
	
	<?php endif; ?>
	
		<div style="overflow:hidden;padding:5px;float:left;margin-left:20px;width:440px;height:200px;border:4px solid #128AE2">
			<a style="float:left;margin-right:10px;" href="<?php echo get_permalink($post_id) ?>"><img src='<?php echo  $p_img?>' style="float:left;width:136px;height:200px"/></a>
			<a href="<?php echo get_permalink($post_id) ?>"><h2 style="font-weight:bold;font-size:14px;text-align:center;display:inline;"><?php echo $SeriesName ?></h2></a>
			
			
			<p style=""><?php echo substr( $single -> post_content, 0 ,200 ); ?><a href="<?php echo get_permalink($post_id) ?>"> [....]</a></p>
		</div>
	<?php if($counter%2==0):?>
	</div>
	<div style="clear:both;"></div>
	
	<?php endif; ?>
<?php
	 
endforeach;
  
 
 /* 
 * ******/


echo "<div style=\"clear:both;\"></div>";

if($my_query->max_num_pages>1)   
if(function_exists('wp_pagenavi'))
wp_pagenavi(array(
   'query' =>$my_query   
   ));
echo "<div style=\"clear:both;\"></div>";
//var_dump($my_query);


get_footer();




?>

