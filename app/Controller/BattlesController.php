<?php
App::import('Vendor', 'JWT', array('file' => 'firebase/php-jwt/Authentication/JWT.php')); 

class BattlesController extends AppController {
    public $helpers = array('Html', 'Form');
    protected $key = 'secret_po';
    protected $lastLog = null;
    protected $battle = null;
    protected $monster = null;
    protected $user = null;

    public function beforeFilter() {
        parent::beforeFilter();
        $this->Auth->allow('index', 'view', 'start', 'activity');
    }

    public function index() { 
        $this->autoRender = false; 

        // get 
        $user = $this->getUser($this->request->query('_token'));

        if($user){ 
            $battles = $this->Battle->find('all', array(
                'conditions'=>array('user_id'=>$user->id),
                'order'=>array('id DESC')
            ));

    	    return $this->toJson($battles);
        }

        $this->toJson(['status'=>'Error'], 401);
    }

    public function start() {
        $this->autoRender = false; 

        if ($this->request->is('post')) {
            // get a random monster
            $this->loadModel('Monster');
            $monster = $this->Monster->find('first', array('order'=>'rand()'));
            
            // get the user
            $user = $this->getUser($this->request->query('_token')); 

            // create a battle and return enemy info
            if($user && $monster) { 
                $battle = $this->Battle->save(array(
                    'user_id' => $user->id,
                    'monster_id' => $monster['Monster']['id'],
                    'status' => 'start' 
                ));

                // create battle log
                $this->loadModel('Log');
                $log = $this->Log->save(array(
                    'user_id' => $user->id,
                    'monster_id' => $monster['Monster']['id'],
                    'battle_id' => $battle['Battle']['id'],
                    'user_hp' => 100,
                    'monster_hp' => 100,
                    'details' => 'Battle Start!' 
                ));

                return $this->toJson([
                    'battle'=>$battle['Battle'], 
                    'monster'=>$monster['Monster'],
                    'log' => $log['Log']
                ], 200);
            }
        }

        $this->toJson(['status'=>'Error'], 401);
    } 

    public function activity($id = null) {
        $this->autoRender = false; 

        // get a battle info 
        $this->battle = $this->Battle->findById($id); 
        
        // get the user
        $this->user = $this->getUser($this->request->query('_token'));  
        
        

        // create a battle  logs and return enemy info
        if($this->user && isset($this->battle['Battle']['id'])) {
            $userAtk = null;
            $monsterAtk = null;

            // get the monster
            $this->loadModel('Monster');
            $this->monster = $this->Monster->findById($this->battle['Battle']['monster_id']);

            // get the last battle activity
            $this->loadModel('Log');
            $this->lastLog = $this->Log->find('first', array(
                'conditions' => array('battle_id' => $this->battle['Battle']['id']),
                'order'=> array('id DESC')
            )); 

            if(in_array($this->battle['Battle']['status'], ['start', 'figthing']) ){
                $userAtk = $this->userAttack();    
            }
            
            // need to recheck batte status as it may finished in player atk
            if(in_array($this->battle['Battle']['status'], ['start', 'figthing']) ){
                $monsterAtk = $this->monsterAttack();
            }

            $this->toJson([
                'userAtk'=>$userAtk,
                'monsterAtk' =>$monsterAtk
            ], 200); 
        }

        $this->toJson(['status'=>'Error'], 401);
    } 

    protected function userAttack(){
        $intUserAtk = rand(1,10);
        $intUserHeal = rand(4,8);
        $userHp = $this->lastLog['Log']['user_hp']; 
        $monsterHp = $this->lastLog['Log']['monster_hp']; 
        $details = '['.$this->user->fullname.'] attack ' . $this->monster['Monster']['name'] . ' by ' . $intUserAtk;
        $status = 'figthing';

        if($this->request->data['type'] == 'power_attack') {
            $intUserAtk = $intUserAtk * 2; 
            $details = '['.$this->user->fullname.'] attack ' . $this->monster['Monster']['name'] . ' by ' . $intUserAtk;
        } elseif($this->request->data['type'] == 'heal') {
            $intUserAtk = 0;
            $userHp = $userHp + $intUserHeal;
            $details = '['.$this->user->fullname.'] heal for ' . $intUserHeal;

            // if user's HP is more than 100 after healing, revert to 100
            if($userHp > 100) {
                $userHp = 100;
            }
        } elseif($this->request->data['type'] == 'give_up') {
            $userHp = 0; 
        }

        // check if player has enuf HP
        if($userHp < 1) {
            $userHp = 0;
            $status = 'lost';
            $details = 'Wasted! Player lost!';
        }

        // check if player has enuf HP
        $monsterHp = $monsterHp - $intUserAtk;

        if($monsterHp < 1) {
            $userHp = 0;
            $status = 'won';
            $details = '['.$this->user->fullname.'] won the figth';
        }

        // update battle status
        $this->Battle->clear(); 
        $this->battle = $this->Battle->save([
            'id' => $this->battle['Battle']['id'],
            'user_id' => $this->user->id,
            'monster_id' => $this->battle['Battle']['monster_id'],
            'status' => $status
        ]);  

        $playerLog = $this->Log->save(array(
            'user_id' => $this->user->id,
            'monster_id' => $this->battle['Battle']['monster_id'],
            'battle_id' => $this->battle['Battle']['id'],
            'user_hp' => $userHp,
            'monster_hp' => $monsterHp,
            'details' => $details 
        ));

        $this->lastLog = $playerLog;

        return [
            'details' => $details, 
            'playerLog' => $playerLog
        ];
    }

    protected function monsterAttack(){
        $intMonsterAtk = rand(1,10); 
        $userHp = $this->lastLog['Log']['user_hp']; 
        $monsterHp = $this->lastLog['Log']['monster_hp']; 
        $details = '['.$this->monster['Monster']['name'].'] attack ' . $this->user->fullname . ' by ' . $intMonsterAtk;
        $status = 'figthing';

        $userHp = $userHp - $intMonsterAtk;

        // check if player has enuf HP
        if($userHp < 1) {
            $userHp = 0;
            $status = 'lost';
            $details = 'Wasted! Player lost!';
        } 

        // update battle status
        $this->Battle->clear(); 
        $this->battle = $this->Battle->save([
            'id' => $this->battle['Battle']['id'],
            'user_id' => $this->user->id,
            'monster_id' => $this->battle['Battle']['monster_id'],
            'status' => $status
        ]);  

        $monsterLog = $this->Log->save(array(
            'user_id' => $this->user->id,
            'monster_id' => $this->battle['Battle']['monster_id'],
            'battle_id' => $this->battle['Battle']['id'],
            'user_hp' => $userHp,
            'monster_hp' => $monsterHp,
            'details' => $details 
        ));

        $this->lastLog = $monsterLog;

        return [
            'details' => $details, 
            'monsterLog' => $monsterLog
        ];
    }

    protected function getUser($token) {
        try {
            return JWT::decode($token, $this->key, array('HS256'));
        }  catch (\Exception $e) { // Also tried JwtException
            return null;
        }
    }

    protected function toJson($data, $status_code=200) {
    	$this->response->type('json');
        $this->response->statusCode($status_code);
        $this->response->body(json_encode($data));
        $this->response->send();
        $this->_stop();
    }
}