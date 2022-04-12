<?php

use Phalcon\Mvc\Controller;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class UserController extends Controller
{
    public function indexAction()
    {
    }
    /**
     * log in user
     *
     * @return void
     */
    public function loginAction()
    {
        if ($_POST) {
            $escaper = new \App\Components\MyEscaper();
            $email = $escaper->sanitize($this->request->getPost('email'));
            $password = $escaper->sanitize($this->request->getPost('password'));
            if (!empty($email) and !empty($password)) {
                $user = Users::findFirst("email='" . $email . "' and password = '" . $password . "'");
                if ($user) {
                    $session = $this->di->get('session');
                    // $this->di->get('EventsManager')->fire('notifications:getCurrentUser', $this);
                    // print_r($session->get('user'));
                    // die;
                    $session->set('user', array('name' => $user->name, 'id' => $user->id));
                    if (is_null($user->token)) {
                        header("location:http://localhost:8080/user/connectToSpotify");
                    } else {
                        $session->set('token', $user->token);
                    }
                    header("location:http://localhost:8080/index/index");
                } else {
                    $this->flash->error("E-mail or password is wrong.");
                }
            } else {
                $this->flash->info("One or more field is empty.");
            }
        }
    }

    /**
     * logout user
     *
     * @return void
     */
    public function logoutAction()
    {
        $this->di->get('session')->destroy();
        $this->response->redirect("index/index");
        header("location:http://localhost:8080/index/index");
    }
    /**
     * sign up user
     *
     * @return void
     */
    public function signUpAction()
    {
        if ($_POST) {
            $escaper = new \App\Components\MyEscaper();
            $email = $escaper->sanitize($this->request->getPost('email'));
            $name = $escaper->sanitize($this->request->getPost('name'));
            $password = $escaper->sanitize($this->request->getPost('password'));
            $password2 = $escaper->sanitize($this->request->getPost('password2'));
            if (empty($email) or empty($name) or empty($password) or empty($password2)) {
                $this->response->setContent("One or more field is empty.");
                return;
            }
            if ($password != $password2) {
                $this->response->setContent("Password did'not match.");
                return;
            }
            $user = new Users();
            try {
                $user->assign(
                    array('name' => $name, 'email' => $email, 'password' => $password),
                    [
                        'name',
                        'email',
                        'password'
                    ]
                );
                $user->save();
                if ($user) {
                    $session = $this->di->get('session');
                    $session->set('user', array('name' => $user->name, 'id' => $user->id));
                    header("location:http://localhost:8080/user/connectToSpotify");
                } else {
                    $this->response->setContent($user->getMessages());
                }
            } catch (Exception $e) {
                $this->response->setContent('This E-mail is already registered with us.');
            }
        }
    }
    public function tryAction()
    {
        die('get');
    }
    public function connectToSpotifyAction()
    {
        /**
         * generate token
         */
        $id = $this->di->get('config')->get('app')->get('client_id');
        $secret = $this->di->get('config')->get('app')->get('client_secret');
        $code = $this->request->getQuery('code');
        if ($code) {
            $redirect_uri = 'http://localhost:8080/user/connectToSpotify';
            $headers = [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => 'Basic ' . base64_encode($id . ':' . $secret)
            ];
            $client = new Client([
                'base_uri' => 'https://accounts.spotify.com',
                'headers' => $headers
            ]);
            $url = ['grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri];
            try{
                $result = json_decode($client->request('POST', '/api/token', ['form_params' => $url])->getBody(), true);
            } catch(ClientException $e){
                die('Token expired');
            } catch(Exception $e){
                die($e.getMessage());
            }
            
            $token = $result['access_token'];
            $refresh_token = $result['refresh_token'];
            $this->di->get('session')->set('token', $token);
            $user = Users::findFirst($this->di->get('session')->get('user')['id']);
            $user->token = $token;
            $user->refresh_token = $refresh_token;
            $user->save();
            header("location:http://localhost:8080/index/index");
        }
        /**
         * get authorization code
         */
        else {
            $redirect = 'http://localhost:8080/user/connectToSpotify';
            $scope = 'playlist-read-private playlist-modify-private user-top-read playlist-modify-public playlist-read-collaborative user-read-private';
            $this->response->redirect('https://accounts.spotify.com/authorize?response_type=code&client_id=' . $id . '&scope=' . $scope . '&redirect_uri=' . $redirect.'&show_dialog=true');
        }
    }
}
