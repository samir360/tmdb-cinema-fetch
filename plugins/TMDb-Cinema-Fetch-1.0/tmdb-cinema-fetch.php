<?php

/*
Plugin Name: TMDb CinemaFetch
Description: Get movie and actors data from TMDb 
Version: 1.0
Author: Samir Castro
*/


define('TMDB_API_KEY', '77d1550cce1ae3b60d99430a8e36afea');



function add_query_vars($vars)
{
    $vars[] = 'id';
    $vars[] = 'page';
    $vars[] = 'name';
    $vars[] = 'movie';
    return $vars;
}
add_filter('query_vars', 'add_query_vars');


//Register shortcodes
add_shortcode('tmdb_movie_where_to_watch', 'tmdb_movie_where_to_watch_shortcode');
add_shortcode('tmdb_movie_genres', 'tmdb_movie_genres_shortcode');
add_shortcode('tmdb_movie_production_companies', 'tmdb_movie_production_companies_shortcode');
add_shortcode('tmdb_movie_language', 'tmdb_movie_language_shortcode');
add_shortcode('tmdb_movie_reviews', 'tmdb_movie_reviews_shortcode');
add_shortcode('tmdb_movie_alternative_titles', 'tmdb_movie_alternative_titles_shortcode');
add_shortcode('tmdb_movie_similar_titles', 'tmdb_movie_similar_titles_shortcode');
add_shortcode('tmdb_movie_credits', 'tmdb_movie_credits_shortcode');
add_shortcode('tmdb_person_movie_credits', 'tmdb_person_movie_credits_shortcode');


// Fetch movies list data
function fetch_movies_data($page, $sort_by, $query)
{
    $api_url = 'https://api.themoviedb.org/3/discover/movie?api_key=' . TMDB_API_KEY . '&page='  . $page;

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

// Fetch popular actors data
function fetch_popular_actors_data($page = 1)
{
    $api_url = "https://api.themoviedb.org/3/person/popular?page=" . $page . "&api_key=" . TMDB_API_KEY;

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

    // Get only first 10 actors
    return array_slice($data['results'], 0, 10);
}

// Popular actors shortcode. Get popular actors and save them in our wordpress
function tmdb_popular_actors_shortcode()
{
    // Fetch Part
    $actors_data = fetch_popular_actors_data();
    if ($actors_data) {
        // Array for actor with aren't in our site
        $new_popular_actors = [];
        foreach ($actors_data as $actor) {

            $query = new WP_Query([
                'post_type' => 'actor',
                'meta_query' => [
                    [
                        'key' => 'tmdb_id',
                        'value' => $actor['id'],
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
            ]);

            if (!$query->have_posts()) {
                array_push($new_popular_actors, $actor);
            }
        }
        prepare_popular_people($new_popular_actors);
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

        $new_popular_movies = [];
        foreach ($movies_data as $movie) {
            $query = new WP_Query([
                'post_type' => 'movie',
                'meta_query' => [
                    [
                        'key' => 'tmdb_id',
                        'value' => $movie['id'],
                        'compare' => '='
                    ]
                ],
                'posts_per_page' => 1,
            ]);

            if (!$query->have_posts()) {
                array_push($new_popular_movies, $movie);
            }
        }
        prepare_movies($new_popular_movies);
    } else {
        return 'Error: Could not fetch upcoming movies data.';
    }
}
add_shortcode('tmdb_upcoming_movies', 'tmdb_upcoming_movies_shortcode');


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
    if (isset($movie_data["results"][0])) {
        return $movie_data["results"][0]["content"];
    } else {
        return 'There are not reviews for this movie.';
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


function prepare_movies($movies = [])
{
    $movies = fetch_movies_data(1, "", "");
    $prepared_movies = [];
    foreach ($movies["results"] as $key => $movie) {
        if (!isset($movie["id"])) {
            continue;
        }
        // genres
        $genres = tmdb_movie_genres_shortcode(["id" => $movie["id"]]);
        //trailer
        $movie_trailer = "";
        $movie_data = fetch_movie_trailer_data($movie["id"]);
        if ($movie_data) {
            foreach ($movie_data["results"] as $trailer) {
                if ($trailer["iso_3166_1"] === "US") {
                    $movie_trailer = "https://www.youtube.com/embed/" . $trailer["key"];
                }
                if ($trailer["name"] === "Final Trailer") {
                    $movie_trailer = "https://www.youtube.com/embed/" . $trailer["key"];
                }
            }
        }

        $language = tmdb_movie_language_shortcode(["id" => $movie["id"]]);
        $alternative_titles = tmdb_movie_alternative_titles_shortcode(["id" => $movie["id"]]);
        $production_companies = tmdb_movie_production_companies_shortcode(["id" => $movie["id"]]);
        $movie_reviews = tmdb_movie_reviews_shortcode(["id" => $movie["id"]]);
        $similar_titles = tmdb_movie_similar_titles_shortcode(["id" => $movie["id"]]);

        // cast
        $movie_cast = tmdb_movie_credits_shortcode(["id" => $movie["id"]]);
        $cast = fetch_movie_general_data($movie["id"], "credits");
        $where_to_watch = tmdb_movie_where_to_watch_shortcode(["id" => $movie["id"]]);

        $completed_movie = [
            "tmdb_id"               => $movie["id"],
            "movie_title"           => wp_strip_all_tags($movie["title"]),
            "movie_poster"          => "https://image.tmdb.org/t/p/w500" . $movie["poster_path"],
            "movie_genre"           => $genres,
            "alternative_titles"    => $alternative_titles,
            "overview"              => $movie["overview"],
            "production_companies"  => $production_companies,
            "release_date"          => $movie["release_date"],
            "original_language"     => $language,
            "cast"                  => wp_strip_all_tags($movie_cast),
            "popularity"            => $movie["popularity"],
            "reviews"               => $movie_reviews,
            "similar_movies"        => wp_strip_all_tags($similar_titles),
            "vote_average"          => $movie["vote_average"],
            "movie_trailer"         => wp_strip_all_tags($movie_trailer),
        ];

        array_push($prepared_movies, $completed_movie);
        //prepare_cast_people($cast);
    }

    add_movies($prepared_movies);
}

function add_movies(array $movies)
{
    foreach ($movies as $key => $movie) {
        // Check movie
        $query = new WP_Query([
            'post_type' => 'movie',
            'meta_query' => [
                [
                    'key' => 'tmdb_id',
                    'value' => $movie['tmdb_id'],
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
        ]);

        if (!$query->have_posts()) {
            // Create post
            $new_movie = [
                'post_title'    => wp_strip_all_tags($movie['movie_title']),
                'post_content'  => $movie['overview'],
                'post_status'   => 'publish',
                'post_type'     => 'movie',
            ];

            // Insert
            $post_id = wp_insert_post($new_movie);

            if (!is_wp_error($post_id)) {
                // Metadata
                update_post_meta($post_id, 'tmdb_id', $movie['tmdb_id']);
                update_post_meta($post_id, 'alternative_titles', $movie['alternative_titles']);
                update_post_meta($post_id, 'production_companies', $movie['production_companies']);
                update_post_meta($post_id, 'release_date', $movie['release_date']);
                update_post_meta($post_id, 'original_language', $movie['original_language']);
                update_post_meta($post_id, 'cast', $movie['cast']);
                update_post_meta($post_id, 'popularity', $movie['popularity']);
                update_post_meta($post_id, 'reviews', $movie['reviews']);
                update_post_meta($post_id, 'similar_movies', $movie['similar_movies']);
                update_post_meta($post_id, 'vote_average', $movie['vote_average']);
                update_post_meta($post_id, 'movie_trailer', $movie['movie_trailer']);

                $genres = explode(',', $movie['movie_genre']);
                // Taxonomy 'genre'
                if (!empty($movie['movie_genre'])) {
                    foreach ($genres as $key => $genre) {
                        wp_set_object_terms($post_id, $genre, 'genre');
                    }
                }

                // Poster
                if (!empty($movie['movie_poster'])) {
                    $attachment_id = upload_image_from_url($movie['movie_poster'], $post_id);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                        update_field('movie_poster', $attachment_id, $post_id);
                    }
                }
            }
        }

        wp_reset_postdata();
    }
}

function prepare_cast_people($movie)
{
    $actors_completed = [];
    foreach ($movie["cast"] as $key => $person) {
        if (!isset($person["id"])) {
            continue;
        }

        $person_id = $person["id"];
        $request = "https://api.themoviedb.org/3/person/{$person_id}?api_key=" . TMDB_API_KEY;
        $response = wp_remote_get($request);

        $body = wp_remote_retrieve_body($response);
        $actor = json_decode($body, true);

        $images_request = "https://api.themoviedb.org/3/person/{$person_id}/images?api_key=" . TMDB_API_KEY;
        $response = wp_remote_get($images_request);
        $body_images = wp_remote_retrieve_body($response);
        $data_images = json_decode($body_images, true);
        $images = array();
        if (isset($data_images["profiles"])) {
            foreach ($data_images["profiles"] as $key => $person_image) {
                if ($key < 10) {
                    $person_image_url = "https://image.tmdb.org/t/p/original{$person_image['file_path']}";
                    array_push($images, $person_image_url);
                } else {
                    break;
                }
            }
        }

        $movies_worked = tmdb_person_movie_credits_shortcode(["id" => $person_id]);
        $actor_data = [
            'tmdb_id'            => $actor['id'],
            'name'               => $actor['name'],
            'biography'          => $actor['biography'],
            'photo'              => "https://image.tmdb.org/t/p/w500" . $actor['profile_path'],
            'birthday'           => $actor['birthday'],
            'place_of_birth'     => $actor['place_of_birth'],
            'day_of_death'       => $actor['deathday'],
            'website'            => $actor['website'],
            'popularity'         => $actor['popularity'],
            'gallery_of_images'  => $images,
            'list_of_movies'     => wp_strip_all_tags($movies_worked),
        ];

        array_push($actors_completed, $actor_data);
    }

    add_actors($actors_completed);
}

function prepare_popular_people($actors)
{
    $actors_completed = [];
    foreach ($actors as $key => $person) {
        if (!isset($person["id"])) {
            continue;
        }
        $person_id = $person["id"];
        $request = "https://api.themoviedb.org/3/person/{$person_id}?api_key=" . TMDB_API_KEY;
        $response = wp_remote_get($request);

        $body = wp_remote_retrieve_body($response);
        $actor = json_decode($body, true);

        $images_request = "https://api.themoviedb.org/3/person/{$person_id}/images?api_key=" . TMDB_API_KEY;
        $response = wp_remote_get($images_request);
        $body_images = wp_remote_retrieve_body($response);
        $data_images = json_decode($body_images, true);
        $images = array();
        if (isset($data_images["profiles"])) {
            foreach ($data_images["profiles"] as $key => $person_image) {
                if ($key < 10) {
                    $person_image_url = "https://image.tmdb.org/t/p/original{$person_image['file_path']}";
                    array_push($images, $person_image_url);
                } else {
                    break;
                }
            }
        }

        $movies_worked = tmdb_person_movie_credits_shortcode(["id" => $person_id]);
        $actor_data = [
            'tmdb_id'            => $actor['id'],
            'name'               => $actor['name'],
            'biography'          => $actor['biography'],
            'photo'              => "https://image.tmdb.org/t/p/w500" . $actor['profile_path'],
            'birthday'           => $actor['birthday'],
            'place_of_birth'     => $actor['place_of_birth'],
            'day_of_death'       => $actor['deathday'],
            'website'            => isset($actor['website']) ? $actor['website'] : '',
            'popularity'         => $actor['popularity'],
            'gallery_of_images'  => $images,
            'list_of_movies'     => wp_strip_all_tags($movies_worked),
        ];

        array_push($actors_completed, $actor_data);
    }

    add_actors($actors_completed);
}

function add_actors(array $actors)
{
    foreach ($actors as $key => $actor) {

        $query = new WP_Query([
            'post_type' => 'actor',
            'meta_query' => [
                [
                    'key' => 'tmdb_id',
                    'value' => $actor['tmdb_id'],
                    'compare' => '='
                ]
            ],
            'posts_per_page' => 1,
        ]);

        if (!$query->have_posts()) {

            $new_actor = [
                'post_title'    => wp_strip_all_tags($actor['name']),
                'post_content'  => $actor['biography'],
                'post_status'   => 'publish',
                'post_type'     => 'actor',
            ];


            $post_id = wp_insert_post($new_actor);

            if (!is_wp_error($post_id)) {

                update_post_meta($post_id, 'tmdb_id', $actor['tmdb_id']);
                update_post_meta($post_id, 'photo', $actor['photo']);
                update_post_meta($post_id, 'birthday', $actor['birthday']);
                update_post_meta($post_id, 'place_of_birth', $actor['place_of_birth']);
                update_post_meta($post_id, 'day_of_death', $actor['day_of_death']);
                update_post_meta($post_id, 'website', $actor['website']);
                update_post_meta($post_id, 'popularity', $actor['popularity']);
                update_post_meta($post_id, 'list_of_movies', $actor['list_of_movies']);

                // Post Photo
                if (!empty($actor['photo'])) {
                    $attachment_id = upload_image_from_url($actor['photo'], $post_id);
                    if (!is_wp_error($attachment_id)) {
                        set_post_thumbnail($post_id, $attachment_id);
                        update_field('photo', $attachment_id, $post_id);
                    }
                }

                // Galllery part
                if (!empty($actor['gallery_of_images'])) {
                    $gallery_ids = [];
                    foreach ($actor['gallery_of_images'] as $image_url) {
                        $gallery_attachment_id = upload_image_from_url($image_url, $post_id);
                        if (!is_wp_error($gallery_attachment_id)) {
                            $gallery_ids[] = $gallery_attachment_id;
                        }
                    }
                    if (!empty($gallery_ids)) {
                        update_field('gallery_of_images', $gallery_ids, $post_id);
                    }
                }
            } else {
                var_dump("Error at save {$actor['name']}");
            }
        }

        wp_reset_postdata();
    }
}

function upload_image_from_url($image_url, $post_id)
{

    $image_data = file_get_contents($image_url);
    if ($image_data === false) {
        return new WP_Error('image_download_error', 'No se pudo descargar la imagen.');
    }


    $filename = basename($image_url);

    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['path'] . '/' . $filename;

    file_put_contents($file_path, $image_data);

    $file_type = wp_check_filetype($filename, null);
    $attachment = [
        'post_mime_type' => $file_type['type'],
        'post_title'     => sanitize_file_name($filename),
        'post_content'   => '',
        'post_status'    => 'inherit',
    ];

    $attachment_id = wp_insert_attachment($attachment, $file_path, $post_id);

    // Attach part
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attachment_id, $file_path);
    wp_update_attachment_metadata($attachment_id, $attach_data);

    return $attachment_id;
}

function resolve_movies_link_shorcode($movies_list)
{
    $movies_list = implode(" ", $movies_list);
    $array_movies = explode(' , ', $movies_list);
    $tags = [];

    foreach ($array_movies as $key => $movie) {
        $post = check_existence_cpt_post_by_title($movie, 'movie');

        if (is_bool($post)) {
            $tag =  $movie;
        } else {
            $slug = sanitize_title($movie);
            $url = home_url("/movie/$slug");
            $tag = "<a href='$url'>$movie</a>";
        }

        array_push($tags, $tag);
    }

    return implode(", ", $tags);
}
add_shortcode('resolve_movies_link', 'resolve_movies_link_shorcode');

function resolve_actors_link_shorcode($string_cast)
{
    $string_cast = implode(" ", $string_cast);
    $array_actors = explode(' , ', $string_cast);
    $tags = [];

    foreach ($array_actors as $key => $person) {
        $post = check_existence_cpt_post_by_title($person, 'actor');

        if (is_bool($post)) {
            $tag =  $person;
        } else {
            $slug = sanitize_title($person);
            $url = home_url("/actor/$slug");
            $tag = "<a href='$url'>$person</a>";
        }

        array_push($tags, $tag);
    }

    return implode(", ", $tags);
}
add_shortcode('resolve_actors_link', 'resolve_actors_link_shorcode');


function check_existence_cpt_post_by_title($post_title, $post_type)
{
    $query = new WP_Query([
        'post_type' => $post_type,
        'title'     => $post_title,
        'posts_per_page' => 1,
    ]);

    if (!$query->have_posts()) {
        return false;
    }

    // have post return it
    return $query->the_post();
}
