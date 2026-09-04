<?php
// Minimal API clients for TMDB and MyAnimeList (MAL) imports.
// Both use cURL directly rather than requiring any external library.

function api_curl_get($url, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AniStream-Admin/1.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['ok' => false, 'error' => $error];
    }
    $data = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300) {
        return ['ok' => false, 'error' => 'HTTP ' . $httpCode . ': ' . ($data['status_message'] ?? $data['message'] ?? 'Request failed')];
    }
    return ['ok' => true, 'data' => $data];
}

// ---- TMDB ---------------------------------------------------------
// $type is 'movie' or 'tv'
function tmdb_search($apiKey, $type, $query) {
    $url = 'https://api.themoviedb.org/3/search/' . $type . '?api_key=' . urlencode($apiKey) . '&query=' . urlencode($query);
    return api_curl_get($url);
}

function tmdb_details($apiKey, $type, $id) {
    $url = 'https://api.themoviedb.org/3/' . $type . '/' . urlencode($id) . '?api_key=' . urlencode($apiKey);
    return api_curl_get($url);
}

function tmdb_poster_url($posterPath) {
    return $posterPath ? 'https://image.tmdb.org/t/p/w500' . $posterPath : '';
}

// ---- MyAnimeList (official API v2) ---------------------------------
function mal_search($clientId, $query) {
    $url = 'https://api.myanimelist.net/v2/anime?q=' . urlencode($query)
        . '&limit=12&fields=id,title,main_picture,synopsis,mean,num_episodes,status,genres,media_type';
    return api_curl_get($url, ['X-MAL-CLIENT-ID: ' . $clientId]);
}

function mal_details($clientId, $id) {
    $url = 'https://api.myanimelist.net/v2/anime/' . urlencode($id)
        . '?fields=id,title,main_picture,synopsis,mean,num_episodes,status,genres,media_type';
    return api_curl_get($url, ['X-MAL-CLIENT-ID: ' . $clientId]);
}
