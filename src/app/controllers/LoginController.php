<?php
session_start();

use Phalcon\Mvc\Controller;
use Phalcon\Mvc\Model\Manager;
use GuzzleHttp\Client;

class LoginController extends Controller
{
    public function indexAction()
    {
        /**
         * get authorization code
         */
        $id = "bcea886f37f84135929fb40dc322d294";
        $secret = "5ce16249bcca4632817665936ab46ebf";
        $redirect = 'http://localhost:8080/index';
        $scope = 'playlist-read-private playlist-modify-private user-top-read playlist-modify-public playlist-read-collaborative user-read-private';
        $this->response->redirect('https://accounts.spotify.com/authorize?response_type=code&client_id=' . $id . '&scope=' . $scope . '&redirect_uri=' . $redirect);
    }
}