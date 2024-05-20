<?php

/*
Plugin Name: TMDb CinemaFetch
Description: Get movie and actors data from TMDb 
Version: 1.0
Author: Samir Castro
*/


define('TMDB_API_KEY', '77d1550cce1ae3b60d99430a8e36afea');

// Rewrite rule for details movie and actor pages
function my_rewrite_rules()
{
    add_rewrite_rule('^movie-detail/?', 'index.php?pagename=movie-detail', 'top');
    add_rewrite_rule('^actor-detail/?', 'index.php?pagename=actor-detail', 'top');
    add_rewrite_rule('^movie-list/page/([0-9]+)/?', 'index.php?pagename=movie-list&page=$matches[1]', 'top');
    add_rewrite_rule('^actors-list/page/([0-9]+)/?', 'index.php?pagename=actors-list&page=$matches[1]', 'top');
}
add_action('init', 'my_rewrite_rules');

function add_query_vars($vars)
{
    $vars[] = 'id';
    $vars[] = 'page';
    return $vars;
}
add_filter('query_vars', 'add_query_vars');


//Register shortcodes
add_shortcode('tmdb_movie_title', 'tmdb_movie_title_shortcode');
add_shortcode('tmdb_movie_release_date', 'tmdb_movie_release_date_shortcode');
add_shortcode('tmdb_movie_overview', 'tmdb_movie_overview_shortcode');
add_shortcode('tmdb_movie_cast_and_crew', 'tmdb_movie_cast_and_crew_shortcode');
add_shortcode('tmdb_movie_trailer', 'tmdb_movie_trailer_shortcode');
add_shortcode('tmdb_movie_where_to_watch', 'tmdb_movie_where_to_watch_shortcode');
add_shortcode('tmdb_movie_poster', 'tmdb_movie_poster_shortcode');
add_shortcode('tmdb_movie_genres', 'tmdb_movie_genres_shortcode');
add_shortcode('tmdb_movie_popularity', 'tmdb_movie_popularity_shortcode');
add_shortcode('tmdb_movie_production_companies', 'tmdb_movie_production_companies_shortcode');
add_shortcode('tmdb_movie_language', 'tmdb_movie_language_shortcode');
add_shortcode('tmdb_movie_reviews', 'tmdb_movie_reviews_shortcode');
add_shortcode('tmdb_movie_alternative_titles', 'tmdb_movie_alternative_titles_shortcode');
add_shortcode('tmdb_movie_similar_titles', 'tmdb_movie_similar_titles_shortcode');
add_shortcode('tmdb_movie_credits', 'tmdb_movie_credits_shortcode');
add_shortcode('tmdb_person_movie_credits', 'tmdb_person_movie_credits_shortcode');
add_shortcode('tmdb_person_images', 'tmdb_person_images_shortcode');

function my_plugin_scripts()
{
    wp_enqueue_style('mypluginstyle_movie', plugins_url('assets/css/movie-list-style.css', __FILE__));
    wp_enqueue_style('mypluginstyle_actor', plugins_url('assets/css/actor-list-style.css', __FILE__));
    wp_enqueue_style('mypluginstyle_upcoming', plugins_url('assets/css/upcoming-movies-style.css', __FILE__));
    wp_enqueue_style('mypluginstyle_popular_actors', plugins_url('assets/css/popular-actors-style.css', __FILE__));
}
add_action('wp_enqueue_scripts', 'my_plugin_scripts');

// Fetch movies list data
function fetch_movies_data($page, $sort_by, $query)
{
    $api_url = 'https://api.themoviedb.org/3/discover/movie?api_key=' . TMDB_API_KEY . '&page=' . $page . '&sort_by=' . $sort_by;

    if (!empty($query)) {
        $api_url = 'https://api.themoviedb.org/3/search/movie?api_key=' . TMDB_API_KEY . '&query=' . urlencode($query) . '&page=' . $page;
    }

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

function tmdb_movie_list_shortcode($atts)
{
    // Get current url
    global $wp;
    $current_url = home_url('/movie-list');
    $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'popularity.desc';
    $query = isset($_GET['query']) ? $_GET['query'] : '';

    $page = get_query_var('paged');
    if (empty($page)) {
        $page = 1;
    }

    $movies_data = fetch_movies_data($page, $sort_by, $query);

    if ($movies_data) {

        $output = '
        <form method="get" action="' . esc_url($current_url) . '">
            <select name="sort_by">
                <option value="popularity.desc" ' . selected($sort_by, 'popularity.desc', false) . '>Popularity - Desc</option>
                <option value="popularity.asc" ' . selected($sort_by, 'popularity.asc', false) . '>Popularity - Asc</option>
                <option value="release_date.desc" ' . selected($sort_by, 'release_date.desc', false) . '>Release Date - Desc</option>
                <option value="release_date.asc" ' . selected($sort_by, 'release_date.asc', false) . '>Release Date - Asc</option>
                <option value="original_title.desc" ' . selected($sort_by, 'original_title.desc', false) . '>Original Title - Desc</option>
                <option value="original_title.asc" ' . selected($sort_by, 'original_title.asc', false) . '>Original Title - Asc</option>
            </select>
            <input type="text" name="query" value="' . esc_attr($query) . '" placeholder="Search...">
            <input type="submit" value="Ordenar/Búsqueda">
        </form>';

        $output .= "<div class='tmdb-movies-container'>";
        $output .= "<p>Página $page </p>";
        foreach ($movies_data['results'] as $movie) {
            $poster_url = "https://image.tmdb.org/t/p/w500{$movie['poster_path']}";
            $title = $movie['title'];
            $detail_url = home_url('/movie-detail?id=' . $movie['id']);

            $output .= "<div class='tmdb-movie'>";
            $output .= "<a href='{$detail_url}'><img src='{$poster_url}' alt='{$title}'></a>";
            $output .= "<h2><a href='{$detail_url}'><div class='main-name' >{$title}<div></a></h2>";
            $output .= "</div>";
        }
        $output .= "</div>";

        // Paginación
        $output .= "<div class='tmdb-pager'>";
        if ($page > 1) {
            //$prev_page_url = add_query_arg(array('page' => $page - 1, 'sort_by' => $sort_by, 'query' => $query), $current_url);
            $prev_page_url = esc_url(home_url("/movie-list/page/" . ($page - 1)));
            $output .= "<a href='" . esc_url($prev_page_url) . "'>&laquo; Prev</a> ";
        }

        //$next_page_url = add_query_arg(array('page' => $page + 1, 'sort_by' => $sort_by, 'query' => $query), $current_url);
        $next_page_url = esc_url(home_url("/movie-list/page/" . ($page + 1)));
        $output .= "<a href='" . esc_url($next_page_url) . "'>Next &raquo;</a>";
        $output .= "</div>";

        return $output;
    } else {
        return 'Error: No se pudieron obtener los datos de las películas.';
    }
}
add_shortcode('tmdb_movie_list', 'tmdb_movie_list_shortcode');

// Fetch actors list data
function fetch_actors_data($page, $name = "", $movie = "")
{
    $api_url = 'https://api.themoviedb.org/3/discover/person?api_key=' . TMDB_API_KEY . '&page=' . $page . '&sort_by=name.asc';

    /* if (!empty($name)) {
        $api_url = 'https://api.themoviedb.org/3/search/person?api_key=' . TMDB_API_KEY . '&query=' . urlencode($name) . '&page=' . $page;
    }

    if (!empty($movie)) {
        $api_url .= '&with_movies=' . urlencode($movie);
    } */

    $response = wp_remote_get($api_url);

    if (is_wp_error($response)) {
        error_log('TMDb Actor Fetcher: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['status_code']) && isset($data['status_message'])) {
        error_log('TMDb Actor Fetcher: ' . $data['status_message']);
        return false;
    }

    return $data;
}

function tmdb_actor_list_shortcode($atts)
{
    // Get current url
    global $wp;
    $current_url = home_url('/actors-list');

    // Get filter and pagination parameters from the query string
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $movie = isset($_GET['movie']) ? $_GET['movie'] : '';

    $page = get_query_var('paged');
    if (empty($page)) {
        $page = 1;
    }


    // Fetch the actors data
    $actors_data = fetch_actors_data($page);

    if ($actors_data) {
        // Begin output
        /* $output = '
        <form method="get" action="' . esc_url($current_url) . '">
            <input type="text" name="name" value="' . esc_attr($name) . '" placeholder="Looking by name...">
            <input type="text" name="movie" value="' . esc_attr($movie) . '" placeholder="Looking by movie...">
            <input type="submit" value="Search">
        </form>';
        */

        $output = "<div class='tmdb-actors-container'>";
        foreach ($actors_data['results'] as $actor) {
            $profile_url = "https://image.tmdb.org/t/p/w500{$actor['profile_path']}";
            $person = $actor['name'];
            $detail_url = home_url('/actor-detail?id=' . $actor['id']);

            $output .= "<div class='tmdb-actor'>";
            $output .= "<a href='{$detail_url}'><img src='{$profile_url}' alt='{$person}'></a>";
            $output .= "<h2><a href='{$detail_url}'><div class='main-name' >{$person}<div></a></h2>";
            $output .= "</div>";
        }
        $output .= "</div>";

        // Pagination
        /*$output .= "<div class='tmdb-pager'>";
        if ($page > 1) {
            //$prev_page_url = add_query_arg(array('page' => $page - 1, 'name' => $name, 'movie' => $movie), $current_url);
            $prev_page_url = esc_url(home_url("/actor-list/page/" . ($page - 1) . "/?name=" . $name . "&movie=" . $movie));
            $output .= "<a href='" . esc_url($prev_page_url) . "'>&laquo; Prev</a> ";
        }

        $next_page_url = esc_url(home_url("/actor-list/page/" . ($page + 1) . "/?name=" . $name . "&movie=" . $movie));
        //$next_page_url = add_query_arg(array('page' => $page + 1, 'name' => $name, 'movie' => $movie), $current_url);
        $output .= "<a href='" . esc_url($next_page_url) . "'>Next &raquo;</a>";
        $output .= "</div>";
 */
        return $output;
    } else {
        return 'Error: No se pudieron obtener los datos de los actores.';
    }
}
add_shortcode('tmdb_actor_list', 'tmdb_actor_list_shortcode');


// Fetch popular actors data
function fetch_popular_actors_data()
{
    $api_url = "https://api.themoviedb.org/3/person/popular?api_key=" . TMDB_API_KEY;

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

    return array_slice($data['results'], 0, 10);  // Get only first 10 actors
}

// Popular actors shortcode
function tmdb_popular_actors_shortcode()
{
    $actors_data = fetch_popular_actors_data();
    if ($actors_data) {
        $output = '';
        $output .= "<div class='tmdb-actors-container popular-actors-container'>";
        foreach ($actors_data as $actor) {
            $profile_url = "https://image.tmdb.org/t/p/w500{$actor['profile_path']}";
            $name = $actor['name'];
            $detail_url = home_url('/actor-detail?id=' . $actor['id']);

            $output .= "<div class='tmdb-actor popular-actor'>";
            $output .= "<a href='{$detail_url}'><img class='popular-actor-image' src='{$profile_url}' alt='{$name}'></a>";
            $output .= "<a href='{$detail_url}'><h2 class='popular-actor-name'>{$name}</h2></a>";
            $output .= "</div>";
        }
        $output .= "</div>";
        return $output;
    } else {
        return 'Error: Could not fetch popular actors data.';
    }
}
add_shortcode('tmdb_popular_actors', 'tmdb_popular_actors_shortcode');

// Fetch upcoming movies data
function fetch_upcoming_movies_data()
{
    $api_url = "https://api.themoviedb.org/3/movie/upcoming?api_key=" . TMDB_API_KEY;

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

    return array_slice($data['results'], 0, 10);  // Get only first 10 movies
}

// Upcoming movies shortcode
function tmdb_upcoming_movies_shortcode()
{
    $movies_data = fetch_upcoming_movies_data();
    if ($movies_data) {
        $output = '';
        $output .= "<div class='tmdb-movie-container upcoming-movie-container'>";
        foreach ($movies_data as $movie) {
            $poster_url = "https://image.tmdb.org/t/p/w500{$movie['poster_path']}";
            $title = $movie['title'];
            $release_date = $movie['release_date'];
            $detail_url = home_url('/movie-detail?id=' . $movie['id']);

            $genres = tmdb_movie_genres_shortcode(array("id" => $movie['id']));
            $output .= "<div class='tmdb-movie upcoming-movie'>";
            $output .= "<a href='{$detail_url}'><img class='upcoming-movie-poster' src=\"{$poster_url}\" alt=\"{$title}\"></a>";
            $output .= "<h2 class='upcoming-movie-title'><a href='{$detail_url}'>{$title}</a></h2>";
            $output .= "<p class='upcoming-movie-release'>Release Date: {$release_date}</p>";
            $output .= "<p class='upcoming-movie-genres'>Genres: {$genres}</p>";
            $output .= "</div>";
        }
        return $output;
    } else {
        return 'Error: Could not fetch upcoming movies data.';
    }
}
add_shortcode('tmdb_upcoming_movies', 'tmdb_upcoming_movies_shortcode');


// Movie poster shortcode
function tmdb_movie_poster_shortcode($atts)
{
    $atts = shortcode_atts(
        array('id' => ''),
        $atts,
        'tmdb_movie_poster'
    );
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        $poster_path = $movie_data['poster_path'];
        $poster_url = "https://image.tmdb.org/t/p/w500{$poster_path}";
        return '<img src="' . $poster_url . '" alt="' . $movie_data['title'] . '">';
    } else {
        return 'Error: Could not fetch movie data.';
    }
}
// Movie genres shortcode
function tmdb_movie_genres_shortcode($atts)
{
    $atts = shortcode_atts(
        array('id' => ''),
        $atts,
        'tmdb_movie_genres'
    );
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        $genres = $movie_data['genres'];
        $genre_names = array_map(function ($genre) {
            return $genre['name'];
        }, $genres);
        return implode(', ', $genre_names);
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie title shortcode
function tmdb_movie_title_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_title');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        return $movie_data['title'];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie release date shortcode
function tmdb_movie_release_date_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_release_date');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        return $movie_data['release_date'];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie overview shortcode
function tmdb_movie_overview_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_overview');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        return $movie_data['overview'];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie cast and crew shortcode
function tmdb_movie_cast_and_crew_shortcode($atts)
{
    // This function is a placeholder, as the trailer data is not available in the basic movie data.    
    // You will need to fetch the videos data separately using the TMDb API.    
    return 'Movie cast and crew data is not available in this version of the plugin.';
}

// Movie where to watch shortcode
function tmdb_movie_where_to_watch_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_where_to_watch');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $watch_providers_data = fetch_watch_providers_data($movie_id);
    if ($watch_providers_data) {
        $providers = $watch_providers_data['results']['US']['flatrate'] ?? [];

        // Change 'US' to the desired country code        
        $provider_names = array_map(function ($provider) {
            return $provider['provider_name'];
        }, $providers);
        $providers_list = implode(', ', $provider_names);
        return !empty($providers_list) ? 'Where to watch: ' . $providers_list : 'No streaming providers found.';
    } else {
        return 'Error: Could not fetch where to watch data.';
    }
}

// movie trailer funtion
function fetch_movie_trailer_data($movie_id)
{
    $api_url = "https://api.themoviedb.org/3/movie/$movie_id/videos?api_key=" . TMDB_API_KEY;
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


// Movie trailer shortcode
function tmdb_movie_trailer_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_overview');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_trailer_data($movie_id);
    if ($movie_data) {
        $trailer_url = "";
        foreach ($movie_data["results"] as $key => $trailer) {
            if ($trailer["iso_3166_1"] === "US") {
                $trailer_url = "https://www.youtube.com/embed/" . $trailer["key"];
            }
            if ($trailer["name"] === "Final Trailer") {
                $trailer_url = "https://www.youtube.com/embed/" . $trailer["key"];
            }
        }
        return '<div class="tmdb-movie-trailer">
                <iframe width="560" height="315" src="' . esc_url($trailer_url) . '" frameborder="0" allowfullscreen></iframe>
                </div>';
    } else {
        return 'Error: Could not fetch movie data.';
    }
}


// where to watch funtion
function fetch_watch_providers_data($movie_id)
{
    $api_url = "https://api.themoviedb.org/3/movie/{$movie_id}/watch/providers?api_key=" . TMDB_API_KEY;
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


// Movie popularity shortcode
function tmdb_movie_popularity_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_popularity');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        return $movie_data['popularity'];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie production companies shortcode
function tmdb_movie_production_companies_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_production_companies');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        $companies_names = array();
        foreach ($movie_data['production_companies'] as $key => $companies) {
            array_push($companies_names, $companies["name"]);
        }
        return implode(", ", $companies_names);
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie language shortcode
function tmdb_movie_language_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_language');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_data($movie_id);
    if ($movie_data) {
        $language = array();
        foreach ($movie_data['spoken_languages'] as $key => $languages) {
            array_push($language, $languages["name"]);
        }
        return $language[0];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}


// fetch reviews funtion
function fetch_movie_reviews_data($movie_id)
{
    $api_url = "https://api.themoviedb.org/3/movie/$movie_id/reviews?api_key=" . TMDB_API_KEY;
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

// Movie reviews shortcode
function tmdb_movie_reviews_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_reviews');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_reviews_data($movie_id);
    if ($movie_data) {

        return $movie_data["results"][0]["content"];
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// fetch general movie data funtion
function fetch_movie_general_data($movie_id, $attr = "")
{
    $api_url = "https://api.themoviedb.org/3/movie/$movie_id/$attr?api_key=" . TMDB_API_KEY;
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

// fetch general person data funtion
function fetch_person_general_data($person_id, $attr = "")
{
    $api_url = "https://api.themoviedb.org/3/person/$person_id/$attr?api_key=" . TMDB_API_KEY;
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

// Movie reviews shortcode
function tmdb_movie_alternative_titles_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_alternative_titles');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_general_data($movie_id, "alternative_titles");
    //var_dump($data);
    if ($movie_data) {
        $titles = array();
        foreach ($movie_data["titles"] as $key => $title) {
            array_push($titles, $title["title"]);
        }
        return implode(", ", $titles);
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie reviews shortcode
function tmdb_movie_similar_titles_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_similar_titles');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_general_data($movie_id, "similar");
    if ($movie_data) {
        $titles = array();
        foreach ($movie_data["results"] as $key => $title) {
            $detail_url = home_url('/movie-detail?id=' . $title['id']);
            $title_url = "<a href='{$detail_url}'>" . $title['original_title']  . " </a>";
            array_push($titles, $title_url);
        }
        return implode(", ", $titles);
    } else {
        return 'Error: Could not fetch movie data.';
    }
}

// Movie reviews shortcode
function tmdb_movie_credits_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_movie_credits');
    $movie_id = $atts['id'];
    if (empty($movie_id)) {
        return 'Error: No movie ID provided.';
    }
    $movie_data = fetch_movie_general_data($movie_id, "credits");
    if ($movie_data) {
        $cast = array();
        foreach ($movie_data["cast"] as $key => $actor) {
            $detail_url = home_url('/actor-detail?id=' . $actor['id']);
            $actor_url = "<a href='{$detail_url}'>" . $actor['original_name']  . " </a>";
            array_push($cast, $actor_url);
        }
        return implode(", ", $cast);
    } else {
        return 'Error: Could not fetch movie data.';
    }
}


// person reviews shortcode
function tmdb_person_movie_credits_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_person_movie_credits');
    $person_id = $atts['id'];
    if (empty($person_id)) {
        return 'Error: No person ID provided.';
    }
    $person_data = fetch_person_general_data($person_id, "movie_credits");
    if ($person_data) {
        $titles = array();
        foreach ($person_data["cast"] as $key => $movie) {
            $detail_url = home_url('/movie-detail?id=' . $movie['id']);
            $movie_url = "<a href='{$detail_url}'>" . $movie['original_title']  . " </a>";
            array_push($titles, $movie_url);
        }
        return implode(", ", $titles);
    } else {
        return 'Error: Could not fetch person data.';
    }
}

// person reviews shortcode
function tmdb_person_images_shortcode($atts)
{
    $atts = shortcode_atts(array('id' => ''), $atts, 'tmdb_person_images');
    $person_id = $atts['id'];
    if (empty($person_id)) {
        return 'Error: No person ID provided.';
    }
    $person_data = fetch_person_general_data($person_id, "images");
    if ($person_data) {
        $images = array();
        $images_counter = 0;
        foreach ($person_data["profiles"] as $key => $person_image) {
            if ($images_counter < 3) {
                $person_image_url = "<div class='person-single-image'><img src='https://image.tmdb.org/t/p/original{$person_image['file_path']} '></div>";
                array_push($images, $person_image_url);
                $images_counter++;
            } else {
                break;
            }
        }
        return implode(" ", $images);
    } else {
        return 'Error: Could not fetch person data.';
    }
}
