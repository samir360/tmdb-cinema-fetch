<?php

/**
 * Theme functions and definitions.
 *
 * For additional information on potential customization options,
 * read the developers' documentation:
 *
 * https://developers.elementor.com/docs/hello-elementor-theme/
 *
 * @package HelloElementorChild
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

define('HELLO_ELEMENTOR_CHILD_VERSION', '2.0.0');

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles()
{

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		[
			'hello-elementor-theme-style',
		],
		HELLO_ELEMENTOR_CHILD_VERSION
	);
}
add_action('wp_enqueue_scripts', 'hello_elementor_child_scripts_styles', 20);

function my_theme_scripts()
{
	wp_enqueue_style('main-style', get_stylesheet_uri());
}
add_action('wp_enqueue_scripts', 'my_theme_scripts');


// Fetch movie data
function fetch_movie_data($movie_id)
{
	$api_url = "https://api.themoviedb.org/3/movie/{$movie_id}?api_key=" . TMDB_API_KEY;
	$response = wp_remote_get($api_url);
	if (is_wp_error($response)) {
		error_log('TMDb Movie Fetcher: ' . $response->get_error_message());
		return false;
	}
	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	if (isset($data['status_code']) && isset($data['status_message'])) {
		error_log('TMDb Movie Fetcher: ' . $data['status_message']);
		return false;
	}
	return $data;
}



// Register shortcode [tmdb_movie_detail]
function tmdb_movie_detail_shortcode($atts)
{
	$movie_id = get_query_var('id');

	//if (!$atts['id']) {
	if (!$movie_id) {
		return 'Movie ID no found.';
	}

	ob_start();
?>
	<div class="movie-details">
		<h2><?php echo do_shortcode('[tmdb_movie_title id="' . $movie_id . '"]'); ?></h2>
		<div class="movie-poster">
			<?php echo do_shortcode('[tmdb_movie_poster id="' . $movie_id . '"]'); ?>
		</div>
		<div class="movie-info">
			<p><strong>Release Date:</strong> <?php echo do_shortcode('[tmdb_movie_release_date id="' . $movie_id . '"]'); ?></p>
			<p><strong>Genres:</strong> <?php echo do_shortcode('[tmdb_movie_genres id="' . $movie_id . '"]'); ?></p>
			<p><strong>Overview:</strong> <?php echo do_shortcode('[tmdb_movie_overview id="' . $movie_id . '"]'); ?></p>
			<p><strong>Alternative Titles:</strong> <?php echo do_shortcode('[tmdb_movie_alternative_titles id="' . $movie_id . '"]'); ?></p>
			<p><strong>Popularity:</strong> <?php echo do_shortcode('[tmdb_movie_popularity id="' . $movie_id . '"]'); ?></p>
			<p><strong>Production Companies:</strong> <?php echo do_shortcode('[tmdb_movie_production_companies id="' . $movie_id . '"]'); ?></p>
			<p><strong>Language:</strong> <?php echo do_shortcode('[tmdb_movie_language id="' . $movie_id . '"]'); ?></p>
			<p><strong>Last Review:</strong> <?php echo do_shortcode('[tmdb_movie_reviews id="' . $movie_id . '"]'); ?></p>
			<p> <?php echo do_shortcode('[tmdb_movie_trailer id="' . $movie_id . '"]'); ?></p>
			<p><strong>Similars movies:</strong> <?php echo do_shortcode('[tmdb_movie_similar_titles id="' . $movie_id . '"]'); ?></p>
			<p><strong>Cast:</strong> <?php echo do_shortcode('[tmdb_movie_credits id="' . $movie_id . '"]'); ?></p>
			<p><strong>Where to Watch:</strong> <?php echo do_shortcode('[tmdb_movie_where_to_watch id="' . $movie_id . '"]'); ?></p>
		</div>
	</div>
<?php
	return ob_get_clean();
}
add_shortcode('tmdb_movie_detail', 'tmdb_movie_detail_shortcode');

function fetch_actor_data($actor_id)
{

	$url = 'https://api.themoviedb.org/3/person/' . intval($actor_id) . '?api_key=' . TMDB_API_KEY;

	$response = wp_remote_get($url);

	if (is_wp_error($response)) {
		return false;
	}

	$body = wp_remote_retrieve_body($response);
	$data = json_decode($body, true);
	if (isset($data['id'])) {
		return $data;
	} else {
		return false;
	}
}


function tmdb_actor_detail_shortcode($atts)
{
	$actor_id = get_query_var('id');

	if (!$actor_id) {
		return 'Person ID no found.';
	}

	$actor_data = fetch_actor_data($actor_id);

	if ($actor_data) {
		$worked_in = tmdb_person_movie_credits_shortcode(["id" => $actor_id]);
		$images = tmdb_person_images_shortcode(["id" => $actor_id]);

		$deathday = $actor_data['deathday'] ? '<p>Deathday: ' . esc_html($actor_data['deathday']) . '</p>' : "";
		$output = '<h1>' . esc_html($actor_data['name']) . '</h1>';
		$output .= '<img src="https://image.tmdb.org/t/p/w500' . esc_html($actor_data['profile_path']) . '" alt="' . esc_html($actor_data['name']) . '">';
		$output .= '<p>' . esc_html($actor_data['biography']) . '</p>';
		$output .= '<p><strong>Birthday:</strong>  ' . esc_html($actor_data['birthday']) . '</p>';
		$output .= $deathday;
		$output .= '<p><strong>Place of Birth:</strong>  ' . esc_html($actor_data['place_of_birth']) . '</p>';
		$output .= '<p><strong>Popularity:</strong>  ' . esc_html($actor_data['popularity']) . '</p>';
		$output .= "<p><strong>Movie Credits:</strong> $worked_in </p>";
		$output .= "<div class='person-single-image-colletion' > $images </div>";


		// Agregar más detalles según sea necesario

		return $output;
	} else {
		return 'Error: No se pudieron obtener los datos del actor.';
	}
}
add_shortcode('tmdb_actor_detail', 'tmdb_actor_detail_shortcode');
