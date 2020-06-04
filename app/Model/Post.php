<?php 

class Post extends AppModel {
	public $validate = array(
        'title' => array(
            'rule' => 'notBlank',
            'required' => true
        ),
        'body' => array(
            'rule' => 'notBlank',
            'required' => true
        )
    );
}