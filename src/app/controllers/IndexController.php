<?php
session_start();

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Manager;
use GuzzleHttp\Client;

class IndexController extends Controller
{
    /**
     * if code is in url, then get token
     *
     * @return void
     */
    public function indexAction()
    {
        $code = $this->request->getQuery('code');

        /**
         * generate token
         */
        
        if ($code) {
            $id = "bcea886f37f84135929fb40dc322d294";
            $secret = "5ce16249bcca4632817665936ab46ebf";
            $code = $this->request->getQuery('code');
            $redirect_uri = 'http://localhost:8080/index';
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($id . ':' . $secret)
            ];
            $client = new Client([
                'base_uri' => 'https://accounts.spotify.com',
                'headers' => $headers
            ]);
            $url = ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri];
            $result = json_decode($client->request('POST', '/api/token', ['form_params' => $url])->getBody(), true);
            $token = $result['access_token'];
            $this->session->set('token', $token);
            echo 'new token '.$token;
        }
        
        /**
         * if filter post is created
         */
        if ($_POST) {
            if (!empty($_POST['check_list'])) {
                $name = $_POST['search'];
                $event = $this->di->get('EventsManager');
                $this->view->album = $event->fire('notifications:filter', $this, array('name'=>$name,'filter'=>implode(",",$_POST['check_list'])));
                $this->view->playlists = $event->fire('notifications:myPlaylists', $this);
            }
        }
    }
}
