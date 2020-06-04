<?php 
App::uses('SimplePasswordHasher', 'Controller/Component/Auth');
class User extends AppModel {
	public $validate = array(
        'email' => array(
            'Email is required' => array(
                'rule' => 'notBlank',
                'required' => true
            ),
            'Email is invalid' => array(
                'rule' => 'email',
                'required' => true
            ),
            'Email is taken' => array(
                'rule' => 'isUnique',
                'message' => 'Email is taken'
            ), 
        ),
        'fullname' => array(
            'Fullname is required' => array(
                'rule' => 'notBlank',
                'required' => true
            )
        ),
        'password' => array(
            'Password is required' => array(
                'rule' => 'notBlank',
                'required' => true
            ),
            'Passwords do not match' => array(
                'rule' => 'matchPassword',
                'message' => 'Passwords do not match'
            ), 
        ),
        'password_confirm' => array(
            'rule' => 'notBlank',
            'required' => true,
            'message' => 'Please confirm password'
        )
    );

    public function matchPassword($data) { 
        if(isset($this->data['User']['password_confirm']) && $data['password'] == $this->data['User']['password_confirm']) {
            return true;
        }

        return false;
    }

    public function beforeSave($options = array()) {
        if(isset($this->data['User']['password'])) {
            $passwordHasher = new SimplePasswordHasher();
            $this->data['User']['password'] = $passwordHasher->hash($this->data['User']['password']);
        }

        return true;
    }
}