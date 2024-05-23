# TMDb CinemaFetch Plugin

## Overview
TMDb CinemaFetch is a WordPress plugin designed to fetch and display movie and actor data from TMDb (The Movie Database). This plugin provides a set of shortcodes to easily integrate movie information, actor details, and other related data into your WordPress site.

## Features
- Fetch and display movie genres, production companies, languages, reviews, alternative titles, similar titles, and credits.
- Fetch and display popular actors and upcoming movies.
- Retrieve and display where to watch a specific movie.
- Automatically update WordPress posts with movie and actor data.

## Installation
1. Download the TMDb CinemaFetch plugin.
2. Upload the plugin files to the `/wp-content/plugins/tmdb-cinemafetch` directory or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.

## Shortcodes
The plugin provides the following shortcodes to display various types of data:

- `[tmdb_movie_where_to_watch id=""]`: Display where to watch a specific movie.
- `[tmdb_movie_genres id=""]`: Display the genres of a specific movie.
- `[tmdb_movie_production_companies id=""]`: Display the production companies of a specific movie.
- `[tmdb_movie_language id=""]`: Display the language of a specific movie.
- `[tmdb_movie_reviews id=""]`: Display reviews for a specific movie.
- `[tmdb_movie_alternative_titles id=""]`: Display alternative titles for a specific movie.
- `[tmdb_movie_similar_titles id=""]`: Display similar titles for a specific movie.
- `[tmdb_movie_credits id=""]`: Display the credits (cast) of a specific movie.
- `[tmdb_person_movie_credits id=""]`: Display the movie credits of a specific actor.
- `[tmdb_popular_actors]`: Fetch and display popular actors.
- `[tmdb_upcoming_movies]`: Fetch and display upcoming movies.

## Functions
The plugin includes the following main functions:

- `fetch_movies_data($page, $sort_by, $query)`: Fetches a list of movies based on the provided parameters.
- `fetch_popular_actors_data($page)`: Fetches data for popular actors.
- `fetch_upcoming_movies_data()`: Fetches data for upcoming movies.
- `fetch_movie_trailer_data($movie_id)`: Fetches trailer data for a specific movie.
- `fetch_watch_providers_data($movie_id)`: Fetches data on where to watch a specific movie.
- `fetch_movie_reviews_data($movie_id)`: Fetches reviews for a specific movie.
- `fetch_movie_general_data($movie_id, $attr)`: Fetches general data for a specific movie.
- `fetch_person_general_data($person_id, $attr)`: Fetches general data for a specific actor.
- `prepare_movies($movies)`: Prepares and adds movie data to WordPress.
- `add_movies(array $movies)`: Adds movie posts to WordPress.
- `prepare_popular_people($actors)`: Prepares and adds popular actor data to WordPress.
- `add_actors(array $actors)`: Adds actor posts to WordPress.
- `upload_image_from_url($image_url, $post_id)`: Uploads an image from a URL to WordPress.

## Usage
1. Use the provided shortcodes in your WordPress posts or pages to display movie and actor data.
2. Customize the plugin according to your needs by modifying the PHP code.

## Example Usage
To display the genres of a movie with ID 123, use the following shortcode in your post or page:
- `[tmdb_movie_genres id="123"]`

## License
This plugin is licensed under the GPLv2 or later.

## Credits
TMDb CinemaFetch is developed and maintained by Samir Castro.

For more information, support, or to contribute to the development of this plugin, please visit the [GitHub repository](https://github.com/samir360/tmdb-cinema-fetch).
