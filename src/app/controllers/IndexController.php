<?php

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
        
        $event = $this->di->get('EventsManager');
        // die($event->fire('notifications:myRecommendation', $this,));
        $this->view->token = $this->di->get('session')->get('token');
        $this->view->user =  $this->di->get('session')->get('user');
        $this->view->recommendations = $event->fire('notifications:myRecommendation', $this,);
        /**
         * if filter post is created
         */
        if ($_POST) {
            if (!empty($_POST['check_list'])) {
                $name = $_POST['search'];
                $this->view->album = $event->fire('notifications:filter', $this, array('name' => $name, 'filter' => implode(",", $_POST['check_list'])));
                $this->view->playlists = $event->fire('notifications:myPlaylists', $this);
            }
        }
    }
}
