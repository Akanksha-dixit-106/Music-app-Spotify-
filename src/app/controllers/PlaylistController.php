<?php
session_start();

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Manager;
use GuzzleHttp\Client;

class PlaylistController extends Controller
{
    /**
     * get current user
     *
     * @return void
     */
    public function getUser()
    {
        $token = $this->session->get('token');
        $result = json_decode($this->client->request('GET', 'https://api.spotify.com/v1/me?access_token=' . $token)->getBody(), true);
        return array(
            'id' => $result['id'],
            'name' => $result['display_name'],
        );
    }
    /**
     * get all playlist
     *
     * @return void
     */
    public function indexAction()
    {
        $event = $this->di->get('EventsManager');
        $this->view->playlists = $event->fire('notifications:myPlaylists', $this);
    }
    /**
     * create a new playlist
     *
     * @return void
     */
    public function addNewAction()
    {
        $user_id = self::getUser()['id'];

        $token = $this->session->get('token');
        // die($token);
        if ($_POST) {
            $header = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $token
            ];
            $body = $this->request->getPost();
            $this->client->request('POST', 'users/' . $user_id . '/playlists', ['headers' => $header, 'body' => json_encode($body)]);
            $this->response->redirect('playlist/index');
        }
    }
    /**
     * remove track from playlist
     *
     * @param [type] $playlist
     * @param [type] $track_id
     * @return void
     */
    public function removeTrackAction($playlist, $track_id)
    {
        $header = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->session->get('token')
        ];
        // die($track_id);
        $track='{ "tracks": [{ "uri": "'.$track_id.'" }] }';
       $this->client->request('DELETE', 'playlists/'.$playlist.'/tracks',['headers' => $header, 'body'=>$track]);
    }
    /**
     * add track to playlist
     *
     * @param [type] $track_id
     * @return void
     */
    public function addToPlaylistAction($track_id)
    {
        $header = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $this->session->get('token')
        ];
       
        $body ='{"uris": ["'.$track_id.'"]}';
        $playlist_id = $this->request->getPost('playlist');
        $this->client->request('POST', 'playlists/'.$playlist_id.'/tracks',['headers' => $header, 'body'=>$body]);
    }
    /**
     * list all tracks in playlist
     *
     * @param [type] $id
     * @return void
     */
    public function listAction($id)
    {
        $event = $this->di->get('EventsManager');
        $this->view->tracks = $event->fire('notifications:playlistTrack', $this, $id);
        $this->view->playlist = $id;
    }
}
