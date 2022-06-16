<?php

namespace App\Listeners;

use Exception as GlobalException;
use Phalcon\Events\Event;
use Phalcon\Di\Injectable;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Client;
use Phalcon\Exception;
use Users;


/**
 * event listener class
 */
class NotificationsListener extends Injectable
{
    /**
     * get current user
     *
     * @param Event $event
     * @param [type] $component
     * @return void
     */
    public function refresh_token(
        Event $event,
        $component,
        $token
    ) {
        $id = $this->di->get('config')->get('app')->get('client_id');
        $secret = $this->di->get('config')->get('app')->get('client_secret');
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode($id . ':' . $secret)
        ];
        $client = new Client([
            'base_uri' => 'https://accounts.spotify.com',
            'headers' => $headers
        ]);
        $user = Users::findFirst("token='" . $token . "'");
        $url = ['grant_type' => 'refresh_token', 'refresh_token' => $user->refresh_token];
        $result = json_decode($client->request('POST', '/api/token', ['form_params' => $url])->getBody(), true);
        $token = $result['access_token'];
        $this->di->get('session')->set('token', $token);
        $user->token = $token;
        $user->save();
        return $token;
    }
    /**
     * get current user
     *
     * @param Event $event
     * @param [type] $component
     * @return void
     */
    public function getCurrentUser(
        Event $event,
        $component
    ) {
        $token = $this->di->get('session')->get('token');
        $result = json_decode($this->client->request('GET', 'https://api.spotify.com/v1/me?access_token=' . $token)->getBody(), true);
        $session = $this->di->get('session');
        $session->set('user', array('name' => $result['id'], 'id' => $result['display_name']));
    }
    /**
     * get new release
     */

    public function myRecommendation(
        Event $event,
        $component
    ) {
        $token = $this->session->get('token');
        try{
            $result = json_decode($this->client->request('GET', '/recommendations?seed_artists=3Nrfpe0tUJi4K4DXYWgMUX&seed_genres=kpop&seed_tracks=1Yo63a5AzPMyHiYMKYIrld&access_token=' . $token)->getBody(), true)['tracks'];
        } catch (ClientException $e) {
            $token = $this->di->get('EventsManager')->fire('notifications:refresh_token', $this, $token);
            $result = json_decode($this->client->request('GET', 'recommendations?seed_artists=3Nrfpe0tUJi4K4DXYWgMUX&seed_genres=kpop&seed_tracks=1Yo63a5AzPMyHiYMKYIrld&access_token=' . $token)->getBody(), true)['tracks'];
        }
        $albums = array();
        foreach ($result as $item) {
            array_push($albums, array(
                'artist' => $item['album']['artists'][0]['name'],
                'name' => $item['album']['name'],
                'release_date' => $item['album']['release_date'],
                'image' => $item['album']['images'][0]['url'] ?? '',
                'id' => $item['album']['id'],
                'type' => $item['album']['album_type']

            ));
        }
        return $albums;
    }
    /**
     * get all playlist
     *
     * @param Event $event
     * @param [type] $component
     * @return void
     */
    public function myPlaylists(
        Event $event,
        $component
    ) {
        $token = $this->di->get('session')->get('token');
        try {
            $result = json_decode($this->client->request('GET', 'me/playlists?access_token=' . $token)->getBody(), true)['items'];
        } catch (Exception $e) {
            die($e);
        } catch (ClientException $e) {
            $token = $this->di->get('EventsManager')->fire('notifications:refresh_token', $this, $token);
            $result = json_decode($this->client->request('GET', 'me/playlists?access_token=' . $token)->getBody(), true)['items'];
        }
        
        $playlists = array();
        foreach ($result as $list) {
            array_push($playlists, array(
                'description' => $list['description'] ?? '',
                'id' => $list['id'],
                'image' => $list['images'][0]['url'],
                'name' => $list['name'],
                'owner' => $list['owner']['display_name']
            ));
        }
        return $playlists;
    }
    /**
     * get tracks in playlist
     *
     * @param Event $event
     * @param [type] $component
     * @param [type] $playlist_id
     * @return void
     */
    public function playlistTrack(
        Event $event,
        $component,
        $id
    ) {
        $token = $this->session->get('token');
        try {
            $result = json_decode($this->client->request('GET', 'playlists/' . $id . '/tracks?access_token=' . $token)->getBody(), true)['items'];
        } catch (Exception $e) {
            die($e);
        } catch (ClientException $e) {
            $token = $this->di->get('EventsManager')->fire('notifications:refresh_token', $this, $token);
            $result = json_decode($this->client->request('GET', 'playlists/' . $id . '/tracks?access_token=' . $token)->getBody(), true)['items'];
        }
        $tracks = array();
        foreach ($result as $list) {
            array_push($tracks, array(
                'artist' => $list['track']['album']['artists'][0]['name'],
                'playlist' => $id,
                'id' => $list['track']['uri'],
                'image' => $list['track']['album']['images'][0]['url'],
                'name' => $list['track']['name']
            ));
        }
        return $tracks;
    }
    /**
     * filter search by check field
     *
     * @param Event $event
     * @param [type] $component
     * @param [type] $back
     * @return void
     */
    public function filter(
        Event $event,
        $component,
        $back
    ) {
        $token = $this->session->get('token');
        $data = array();
        try {
            $result = json_decode($this->client->request('GET', 'search?q=' . urlencode($back['name']) . '&type=' . $back['filter'] . '&access_token=' . $token)->getBody(), true);
        } catch (Exception $e) {
            die($e);
        } catch (ClientException $e) {
            $token =$this->di->get('EventsManager')->fire('notifications:refresh_token', $this, $token);
            $result = json_decode($this->client->request('GET', 'search?q=' . urlencode($back['name']) . '&type=' . $back['filter'] . '&access_token=' . $token)->getBody(), true);
        }

        foreach ($result as $key => $albums) {
            $album = array();
            $title = $key;
            foreach ($albums['items'] as $item) {
                $others = array();
                switch ($title) {
                    case 'albums':
                        array_push($others, array(
                            'Artist' => $item['artists'][0]['name'],
                            'Release Data' => $item['release_date'],
                        ));
                        break;
                    case 'artists':
                        array_push($others, array(
                            'followers' => $item['followers']['total'],
                            'genre' => is_array($item['genres']) ? $item['genres'][0] : $item['genres'],
                        ));
                        break;
                    case 'tracks':
                        array_push($others, array(
                            'Popularity' => $item['popularity'],
                            'uri' => $item['uri']
                        ));
                        break;
                    case 'playlists':
                        break;
                    case 'shows':
                        break;
                    case 'episodes':
                        break;
                }
                array_push($album, array(
                    'link' => $item['external_urls']['spotify'],
                    'others' => $others,
                    'name' => $item['name'],
                    'id' => $item['id'],
                    'image' => $item['images'][0]['url'] ?? '',
                ));
            }
            array_push($data, array($title => $album));
        }
        return $data;
    }
}
