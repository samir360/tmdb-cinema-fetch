<?php

/**
 * Template Name: Single Movie
 */

get_header();

if (have_posts()) : while (have_posts()) : the_post();
        // Obtener el ID de la película desde la URL
        $movie_id = get_query_var('id');
?>

        <div class="movie-details">
            <?php echo do_shortcode('[tmdb_movie_detail id="' . $movie_id . '"]'); ?>
        </div>

<?php
    endwhile;
endif;

get_footer();
?>