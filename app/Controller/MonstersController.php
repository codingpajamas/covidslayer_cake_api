<?php

class MonstersController extends AppController {
    public $helpers = array('Html', 'Form');

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('index', 'view');
    }

    public function index() { 
        $this->autoRender = false; 
	    return $this->toJson($this->Monster->find('all'));
    }

    public function view($id = null) {
    	$this->autoRender = false;

        if (!$id) {
        	$this->toJson(['status'=>'Error'], 404);
        }

        $post = $this->Monster->findById($id);

        if (!$post) {
        	$this->toJson(['status'=>'Error'], 404);
        }
        
        return $this->toJson($post['Post']);
    } 

    protected function toJson($data, $status_code=200) {
    	$this->response->type('json');
        $this->response->statusCode($status_code);
        $this->response->body(json_encode($data));
        $this->response->send();
        $this->_stop();
    }
}