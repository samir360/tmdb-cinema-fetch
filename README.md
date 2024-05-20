# TMDb CinemaFetch

Retrieves movie and actor data from the TMDb API for display on WordPress websites.

## Plugin Details

- **Plugin Name:** TMDb CinemaFetch
- **Description:** Retrieves movie and actor data from the TMDb API.
- **Version:** 1.0
- **Author:** Samir Castro

## Overview

The **TMDb CinemaFetch** plugin retrieves movie and actor information from the TMDb (The Movie Database) API. It enables users to display details such as movie title, release date, synopsis, genres, poster, movie list, actor list, popular actors, upcoming movies, and streaming availability on their WordPress websites using shortcodes.

## Installation

1. Download the plugin ZIP file from Releases.
2. Log in to your WordPress admin panel.
3. Navigate to **Plugins** and click **Add New**.
4. Click on the **Upload Plugin** button.
5. Select the plugin ZIP file and click **Install Now**.
6. After the installation is complete, click **Activate** to enable the plugin.

## Configuration

1. Obtain a TMDb API key:
    - Visit the TMDb website and sign up for an account if you don't have one.
    - Go to your account settings and navigate to the API section.
    - Generate an API key for your application.
2. Update the plugin code with your TMDb API key:
    - Open the plugin file (`tmdb-cinemafetch.php`) in a text editor.
    - Locate the line `define('TMDB_API_KEY', 'your-tmdb-api-key');`.
    - Replace `'your-tmdb-api-key'` with your actual TMDb API key.
    - Save your changes.

## Shortcodes

The **TMDb CinemaFetch** plugin offers the following shortcodes to display movie and actor information:

- `[tmdb_movie_title id="12345"]`: Displays the movie title.
- `[tmdb_movie_release_date id="12345"]`: Displays the movie release date.
- `[tmdb_movie_overview id="12345"]`: Displays the movie overview.
- `[tmdb_movie_cast_and_crew id="12345"]`: Displays the movie cast and crew data (not available in this version).
- `[tmdb_movie_trailer id="12345"]`: Displays the movie trailer data.
- `[tmdb_movie_where_to_watch id="12345"]`: Displays the streaming providers where the movie is available.
- `[tmdb_movie_poster id="12345"]`: Displays the movie poster image.
- `[tmdb_movie_genres id="12345"]`: Displays the movie genres.
- `[tmdb_movie_list]`: Displays a list of movies with sorting and search options.
- `[tmdb_actor_list]`: Displays a list of actors with search options by name and movie.
- `[tmdb_popular_actors]`: Displays a list of popular actors.
- `[tmdb_upcoming_movies]`: Displays a list of upcoming movies.
- `[tmdb_movie_detail]`: Displays detailed information about a specific movie.
- `[tmdb_actor_detail]`: Displays detailed information about a specific actor.
- `[tmdb_movie_popularity id="12345"]`: Displays the movie's popularity.
- `[tmdb_movie_production_companies id="12345"]`: Displays the movie's production companies.
- `[tmdb_movie_language id="12345"]`: Displays the spoken language of the movie.
- `[tmdb_movie_reviews id="12345"]`: Displays reviews of the movie.
- `[tmdb_movie_alternative_titles id="12345"]`: Displays alternative titles of the movie.
- `[tmdb_movie_similar_titles id="12345"]`: Displays similar movie titles.
- `[tmdb_movie_credits id="12345"]`: Displays the movie's credits.
- `[tmdb_person_movie_credits id="12345"]`: Displays a person's movie credits.
- `[tmdb_person_images id="12345"]`: Displays a person's images.

## Usage

To use the shortcodes, place them in the desired location (e.g., post content, page content, widget, etc.) within your WordPress site.

Examples:

- To display the movie title: `[tmdb_movie_title id="12345"]` Replace `12345` with the movie ID you wish to fetch.
- To display the movie release date: `[tmdb_movie_release_date id="12345"]`
- To display the movie overview: `[tmdb_movie_overview id="12345"]`
- To display the movie genres: `[tmdb_movie_genres id="12345"]`
- To display the movie poster: `[tmdb_movie_poster id="12345"]`
- To display the streaming providers where the movie is available: `[tmdb_movie_where_to_watch id="12345"]`
- To display a list of movies: `[tmdb_movie_list]`
- To display a list of actors: `[tmdb_actor_list]`
- To display a list of popular actors: `[tmdb_popular_actors]`
- To display a list of upcoming movies: `[tmdb_upcoming_movies]`
- To display detailed movie information: `[tmdb_movie_detail]`
- To display detailed actor information: `[tmdb_actor_detail]`
- To display the movie's popularity: `[tmdb_movie_popularity id="12345"]`
- To display the movie's production companies: `[tmdb_movie_production_companies id="12345"]`
- To display the spoken language of the movie: `[tmdb_movie_language id="12345"]`
- To display reviews of the movie: `[tmdb_movie_reviews id="12345"]`
- To display alternative titles of the movie: `[tmdb_movie_alternative_titles id="12345"]`
- To display similar movie titles: `[tmdb_movie_similar_titles id="12345"]`
- To display the movie's credits: `[tmdb_movie_credits id="12345"]`
- To display a person's movie credits: `[tmdb_person_movie_credits id="12345"]`
- To display a person's images: `[tmdb_person_images id="12345"]`

Note: Replace `12345` with the movie or person ID you wish to fetch.

## Limitations

- The plugin might encounter errors if the TMDb API key is not provided or is invalid.
- The data of actors and movies are not saved in the database.

---

I hope this README is helpful for you and your users.
---
