<?php
/**
 * The template for displaying all single posts.
 *
 * @package ultrabootstrap
 */

get_header(); ?>
<div class="">
<!-- Header Image -->
<?php if (has_header_image()): ?>
<div class="text-center">
<img src="<?php header_image(); ?>" height="<?php echo get_custom_header()->height; ?>" width="<?php echo get_custom_header()->width; ?>" alt="" />
</div>
<?php endif;?>
<!-- Header Image -->
<div class="container">
  <div class="row">
        <div class="col-sm-9">
<section class="page-section">

      <div class="detail-content">
           	
      	<?php while ( have_posts() ) : the_post(); ?>                    
  	      <?php get_template_part( 'template-parts/content', 'single' ); ?>
          

        <?php endwhile; // End of the loop. ?>

    
        <?php comments_template(); ?>


                  </div><!-- /.end of deatil-content -->
  			 
</section> <!-- /.end of section -->  
</div>
    <div class="col-sm-3"><?php get_sidebar(); ?>
    </div>
    </div>
</div>
</div>
<?php get_footer(); ?>