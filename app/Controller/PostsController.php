<?php

class PostsController extends AppController {
    public $helpers = array('Html', 'Form');

    public function index() { 
        $this->autoRender = false; 
	    return $this->toJson($this->Post->find('all'));
    }

    public function view($id = null) {
    	$this->autoRender = false;

        if (!$id) {
        	$this->toJson(['status'=>'Error'], 404);
        }

        $post = $this->Post->findById($id);

        if (!$post) {
        	$this->toJson(['status'=>'Error'], 404);
        }
        
        return $this->toJson($post['Post']);
    }

    public function add() {
    	$this->autoRender = false; 

        if ($this->request->is('post')) {
            $this->Post->create();
            $this->Post->set($this->request->data);

            if ($this->Post->validates()) {
            	$post = $this->Post->save($this->request->data);
			    return $this->toJson($post, 200);
			}

			// didn't validate logic
		    $errors = $this->Post->validationErrors;
		    $this->toJson($errors);
        }

        $this->toJson(['status'=>'Error'], 404);
    } 

    protected function toJson($data, $status_code=200) {
    	$this->response->type('json');
        $this->response->statusCode($status_code);
        $this->response->body(json_encode($data));
        $this->response->send();
        $this->_stop();
    }
}