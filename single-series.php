<?php
/**
 * The Template for displaying all single posts.
 *
 * @package WordPress
 * @subpackage Twenty_Eleven
 * @since Twenty Eleven 1.0
 */

get_header(); 
global $post;
?>

		<div id="primary">
			<div id="main-content" role="main">

				<?php while ( have_posts() ) : the_post(); ?>
				<?php $meta = get_post_meta($post -> ID,'series_meta');
				extract($meta[0]);
				$p_ext= $wptheTvDbApi -> return_file_ext($poster);
				$base = wp_upload_dir();
				$site_url = site_url();
				$p_img = $site_url."/?get_image=1&poster_id={$id}";
				//$p_img = $base['baseurl']."/{$id}/poster{$p_ext}";
				$b_img = $base['baseurl']."/{$id}/banner{$p_ext}";
				$f_img = $base['baseurl']."/{$id}/fanart{$p_ext}";				
				?>
				<!--
				
				Image Here---HTML CODE BLOCK
				-->
				
		<div style="text-align:center;">
			<img src="<?php echo $b_img ?>"/>
			<h2 style="font-size:20px;font-weight:bold;"><?php echo $SeriesName ?> </h2>
			<img style = "width:136px;height:200px;" src="<?php echo $p_img ?>"/>

		</div>
		<?php the_content() ?>
		<div style="text-align:center;">
						<table style="border:none;" id='tvdb-table'>
				<thead>
				
				</thead>
				<tbody>
				<tr class="t-odd">
					<td>Genre:</td>
					<td><?php echo $p_gen =(strlen($Genre)>4)?str_replace('|',', ',trim($Genre,'|')):'Not Avalilable'; ?></td>
				</tr>
				<tr>
					<td>Actors:</td>
					<td><?php echo $p_acts =(strlen($Actors)>4)?str_replace('|',', ',trim($Actors,'|')):'Not Avalilable'; ?></td>
				</tr>
				<tr class="t-odd">
					<td>First Aired:</td>
					<td><?php echo $p_aired =(strlen($FirstAired)>4)?$FirstAired:'Not Available'; ?></td>
				</tr>		
				<tr>
					<td>User Rating:</td>
					<td><?php echo $p_raitng =(strlen($Rating)>0)?$Rating:'Not Available'; ?></td>
				</tr>		
				<tr class="t-odd">
					<td>Network:</td>
					<td><?php echo $p_net =(strlen($Network)>1)?$Network:'Not Available'; ?></td>
				</tr>		
				<tr>
					<td>IMDB Link</td>
					<td><?php $link =(stripos($IMDB_ID,'tt')!==false)?$IMDB_ID:"tt{$IMDB_ID}";
					$link = (strlen($Network)>3)? $link:'Not Available';
					if($link != 'Not Available'){
					$lin = "http://www.imdb.com/title/".$link; 
					echo "<a href='$lin'>$lin</a>";   
				    }
				    else echo $link; 
				    ?>
				    </td>
				</tr>
				</tbody>		
			</table>
		</div>
		
		<?php comments_template( '', true ); ?>

				<?php endwhile; // end of the loop. ?>

			</div><!-- #content -->
		</div><!-- #primary -->
	<?php get_sidebar(); ?>

<?php get_footer(); ?>
