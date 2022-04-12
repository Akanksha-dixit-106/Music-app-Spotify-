<?php

namespace App\Listeners;

use Phalcon\Events\Event;
use Phalcon\Di\Injectable;
use Phalcon\Exception;


/**
 * event listener class
 */
class NotificationsListener extends Injectable
{
    /**
     * get new release
     */

    public function newRelease(
        Event $event,
        $component
    ) {
        $token = $this->session->get('token');
        $result = json_decode($this->client->request('GET', 'browse/new-releases?&type=track&access_token=' . $token)->getBody(), true);
        $albums = array();
        foreach ($result['albums']['items'] as $item) {
            array_push($albums, array(
                'artist' => $item['artists'][0]['name'],
                'name' => $item['name'],
                'release_date' => $item['release_date'],
                'image' => $item['images'][0],
                'id' => $item['id'],
                'type' => $item['album_type']

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
        $token = $this->session->get('token');
        $result = json_decode($this->client->request('GET', 'me/playlists?access_token=' . $token)->getBody(), true)['items'];
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
        $result = json_decode($this->client->request('GET', 'playlists/' . $id . '/tracks?access_token=' . $token)->getBody(), true)['items'];
        // echo '<pre>';
        // print_r($result);
        // die;
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
        $result = json_decode($this->client->request('GET', 'search?q=' . urlencode($back['name']) . '&type=' . $back['filter'] . '&access_token=' . $token)->getBody(), true);

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
