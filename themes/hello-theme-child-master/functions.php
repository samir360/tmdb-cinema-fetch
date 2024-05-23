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

// Elementor supprot
add_action('after_setup_theme', function () {
	add_theme_support('elementor');
});

/**
 * Load child theme scripts & styles.
 *
 * @return void
 */
function hello_elementor_child_scripts_styles()
{
	wp_enqueue_style('hello-elementor', get_template_directory_uri() . '/style.css');

	wp_enqueue_style(
		'hello-elementor-child-style',
		get_stylesheet_directory_uri() . '/style.css',
		array('hello-elementor-theme-style'),
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
		error_log('TMDb Movi Fetcher: ' . $response->get_error_message());
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
