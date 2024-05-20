<?php

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

function tmdb_movie_detail_shortcode($atts)
{
    $movie_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    var_dump($movie_id);
    echo "<p>movie-detail</p>";

    if (!$movie_id) {
        return 'Error: No se pudo encontrar la película.';
    }

    $movie_data = fetch_movie_data($movie_id);

    if ($movie_data) {
        $output = '<h1>' . esc_html($movie_data['title']) . '</h1>';
        $output .= '<img src="https://image.tmdb.org/t/p/w500' . esc_html($movie_data['poster_path']) . '" alt="' . esc_html($movie_data['title']) . '">';
        $output .= '<p>' . esc_html($movie_data['overview']) . '</p>';
        // Agregar más detalles según sea necesario

        return $output;
    } else {
        return 'Error: No se pudieron obtener los datos de la película.';
    }
}
add_shortcode('tmdb_movie_detail', 'tmdb_movie_detail_shortcode');
