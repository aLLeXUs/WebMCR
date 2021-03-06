<?php

class user{
	// Set default system vars
	private $core, $db, $config, $lng;

	// Set default user vars
	public $email, $login, $group, $group_desc, $password, $salt, $tmp, $ip, $ip_create, $data, $permissions, $permissions_v2, $gender;

	public $id = 0;

	public $is_auth = false;

	public $is_skin = false;

	public $is_cloak = false;

	public $skin = '.default';

	public $cloak = '';

	public $money= 0;

	public $realmoney = 0;

	public $bank = 0;

	public $gid = -1;

	public function get_default_permissions(){
		$query = $this->db->query("SELECT `value`, `type`, `default` FROM `mcr_permissions`");

		if(!$query || $this->db->num_rows($query)<=0){ return; }

		$array = array();

		while($ar = $this->db->fetch_assoc($query)){

			switch($ar['type']){
				case 'integer':
					$array[$ar['value']] = intval($ar['default']);
				break;

				case 'float':
					$array[$ar['value']] = floatval($ar['default']);
				break;

				case 'string':
					$array[$ar['value']] = $this->db->safesql($ar['default']);
				break;

				default:
					$array[$ar['value']] = ($ar['default']=='true') ? true : false;
				break;
			}

		}

		$permissions = json_encode($array);

		return array(json_decode($permissions), json_decode($permissions, true));
	}

	public function __construct($core){
		$this->core			= $core;
		$this->db			= $core->db;
		$this->config		= $core->config;
		$this->lng			= $core->lng;

		$this->login		= $this->lng['u_group_def'];
		$this->group		= $this->lng['u_group_def'];

		$this->group_desc	= $this->lng['u_group_desc_def'];

		// Set now ip
		$this->ip	= $this->ip();

		// Check cookies
		if(!isset($_COOKIE['mcr_user'])){
			$perm_ar = @$this->get_default_permissions();
			$this->permissions = $perm_ar[0];
			$this->permissions_v2 = $perm_ar[1];
			return false;
		}

		$cookie	= explode("_", $_COOKIE['mcr_user']);

		if(!isset($cookie[0], $cookie[1])){ $this->set_unauth(); $this->core->notify(); }

		$uid	= intval($cookie[0]);
		$hash	= $cookie[1];

		$query = $this->db->query("SELECT `u`.gid, `u`.login, `u`.email, `u`.password, `u`.`salt`, `u`.`tmp`, `u`.ip_create, `u`.`data`, `u`.`is_skin`, `u`.`is_cloak`,
											`g`.title, `g`.`description`, `g`.`permissions`, `i`.`money`, `i`.realmoney, `i`.bank
									FROM `mcr_users` AS `u`
									INNER JOIN `mcr_groups` AS `g`
										ON `g`.id=`u`.gid
									LEFT JOIN `mcr_iconomy` AS `i`
										ON `i`.login=`u`.login
									WHERE `u`.id='$uid'");

		if(!$query || $this->db->num_rows($query)<=0){ $this->set_unauth(); $this->core->notify(); }

		$ar			= $this->db->fetch_assoc($query);

		$tmp		= $this->db->HSC($ar['tmp']);
		$password	= $this->db->HSC($ar['password']);

		$new_hash	= $uid.$tmp.$this->ip.md5($this->config->main['mcr_secury']);

		$ar_hash	= $uid.'_'.md5($new_hash);

		// Check security auth
		if($_COOKIE['mcr_user'] !== $ar_hash){ $this->set_unauth(); $this->core->notify(); }

		// Identificator
		$this->id			= $uid;

		// Group identificator
		$this->gid			= intval($ar['gid']);

		// Username
		$this->login		= $this->db->HSC($ar['login']);

		// E-Mail
		$this->email		= $this->db->HSC($ar['email']);

		// Password hash
		$this->password		= $password;

		// Salt of password
		$this->salt			= $ar['salt'];

		// Temp hash
		$this->tmp			= $tmp;

		// Register ip
		$this->ip_create	= $this->db->HSC($ar['ip_create']);

		// Other information
		$this->data			= json_decode($ar['data']);

		// Group title
		$this->group		= $this->db->HSC($ar['title']);

		// Group description
		$this->group_desc	= $this->db->HSC($ar['description']);

		// Permissions
		$this->permissions	= @json_decode($ar['permissions']);

		// Permissions
		$this->permissions_v2	= @json_decode($ar['permissions'], true);

		// Is auth status
		$this->is_auth		= true;

		// Is default skin
		$this->is_skin		= (intval($ar['is_skin'])==1) ? true : false;

		// Is isset cloak
		$this->is_cloak		= (intval($ar['is_cloak'])==1) ? true : false;

		$this->skin			= ($this->is_skin || $this->is_cloak) ? $this->login : '.default';

		$this->cloak		= ($this->is_cloak) ? $this->login : '';

		// Gender
		$this->gender		= (intval($this->data->gender)==1) ? $this->lng['gender_w'] : $this->lng['gender_m'];

		// Game money balance
		$this->money		= floatval($ar['money']);

		// Real money balance
		$this->realmoney	= floatval($ar['realmoney']);

		// Bank money balance (for plugins)
		$this->bank			= floatval($ar['bank']);

	}

	public function set_unauth(){
		if(isset($_COOKIE['mcr_user'])){ setcookie("mcr_user", "", time()-3600, '/'); }

		return true;
	}

	private function ip(){

		if(!empty($_SERVER['HTTP_CF_CONNECTING_IP'])){
			$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		}elseif(!empty($_SERVER['HTTP_X_REAL_IP'])){
			$ip = $_SERVER['HTTP_X_REAL_IP'];
		}elseif(!empty($_SERVER['HTTP_CLIENT_IP'])){
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}else{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		return mb_substr($ip, 0, 16, "UTF-8");
	}
}

?>