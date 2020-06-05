<?php
App::import('Vendor', 'JWT', array('file' => 'firebase/php-jwt/Authentication/JWT.php')); 
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');

class UsersController extends AppController {
    public $helpers = array('Html', 'Form'); 
    protected $key = 'secret_po';

    public function beforeFilter() {
        parent::beforeFilter();  
        $this->Auth->allow('me'); 
    } 

    public function register() { 

    	$this->autoRender = false; 

        if ($this->request->is('post')) {
            $this->User->create();
            $this->User->set($this->request->data);

            if ($this->User->validates()) {
            	$user = $this->User->save($this->request->data);
			    // return $this->toJson($user, 200);

                unset($user['User']['password']);
                $jwt = JWT::encode($user['User'], $this->key); 
                
                return $this->toJson([
                    'token'=>$jwt,
                    'profile'=>$user['User']
                ], 200); 
			}

			// didn't validate logic
		    $errors = $this->User->validationErrors;
		    $this->toJson($errors, 400);
        }

        $this->toJson(['status'=>'Error'], 404);
    } 

    public function login() {
        $this->autoRender = false; 
        $user = null;

        $passwordHasher = new SimplePasswordHasher();
        $hashPassword =  isset($this->request->data['password']) ? $passwordHasher->hash($this->request->data['password']) : null;

        if ($this->request->is('post')) { 
            $credentials = array(
                'email'=>$this->request->data['email'], 
                'password'=>$hashPassword
            );

            $data = $this->User->find('first', array('conditions' => $credentials)); 

            if(isset($data['User'])) {
                unset($data['User']['password']);
                $jwt = JWT::encode($data['User'], $this->key); 
                
                return $this->toJson([
                    'token'=>$jwt,
                    'profile'=>$data['User']
                ], 200); 
            }
        }

        $this->toJson(['status'=>'Error', 'data'=>$user], 401);
    } 

    public function me() {
        $this->autoRender = false; 

        $user = $this->getUser($this->request->query('_token'));

        if($user) {
            $this->toJson(['user'=>$user], 200);
        }

        $this->toJson(['status'=>'Unauthorized'], 401);
        
    } 

    protected function getUser($token) {
        try {
            return JWT::decode($token, $this->key, array('HS256'));
        }  catch (\Exception $e) { // Also tried JwtException
            return null;
        }
    }

    public function decode() {
        $this->autoRender = false; 

        $decoded = JWT::decode($this->request->query('_token'), $this->key, array('HS256'));
        return $this->toJson(['object'=>$decoded], 200);
    }

    protected function toJson($data, $status_code=200) { 
        $this->response->header('Access-Control-Allow-Origin','*');
        $this->response->header('Access-Control-Allow-Methods','*');
        $this->response->header('Access-Control-Allow-Headers','X-Requested-With');
        $this->response->header('Access-Control-Allow-Headers','Content-Type, x-xsrf-token');
        $this->response->header('Access-Control-Max-Age','172800');
    	$this->response->type('json');
        $this->response->statusCode($status_code);
        $this->response->body(json_encode($data));
        $this->response->send();
        $this->_stop();
    }
}