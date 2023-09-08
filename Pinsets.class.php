<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright (C) 2014 Schmooze Com Inc.
namespace FreePBX\modules;
use UnexpectedValueException;
use BMO;
use PDO;
#[\AllowDynamicProperties]
class Pinsets implements BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}
	public function install() {}
    public function uninstall() {}

	public function doConfigPageInit($page) {
		$request = $_REQUEST;
		isset($request['action'])?$action = $request['action']:$action='';
		isset($request['view'])?$view=$request['view']:$view='';
		isset($request['itemid'])?$itemid=$request['itemid']:$itemid='';
		if(isset($request['action'])) {
			switch ($action) {
				case "add":
					pinsets_add($request);
					needreload();
					unset($_REQUEST['view']);
				break;
				case "delete":
					pinsets_del($itemid);
					needreload();
				break;
				case "edit":
					pinsets_edit($itemid,$request);
					needreload();
					unset($_REQUEST['view']);
				break;
			}
		}

	}
	function listPinsets() {
		$sql = "SELECT * FROM pinsets";
        $ret = $this->db->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
		if(is_array($ret)){
			return $ret;
		}
		return null;
	}
	public function getActionBar($request) {
		$buttons = array();
		switch($request['display']) {
			case 'pinsets':
				$buttons = array(
					'delete' => array(
						'name' => 'delete',
						'id' => 'delete',
						'value' => _('Delete')
					),
					'reset' => array(
						'name' => 'reset',
						'id' => 'reset',
						'value' => _('Reset')
					),
					'submit' => array(
						'name' => 'submit',
						'id' => 'submit',
						'value' => _('Submit')
					)
				);
				if (empty($request['itemid'])) {
					unset($buttons['delete']);
				}
				if (empty($request['view']) || $request['view'] != 'form'){
					$buttons = array();
				}
			break;
		}
		return $buttons;
	}
	public function ajaxRequest($req, &$setting) {
        switch ($req) {
            case 'getJSON':
                return true;
            break;
            default:
                return false;
            break;
        }
    }
    public function ajaxHandler(){
        switch ($_REQUEST['command']) {
            case 'getJSON':
                switch ($_REQUEST['jdata']) {
                    case 'grid':
                        return $this->listPinsets();
                    break;

                    default:
                        return false;
                    break;
                }
            break;

            default:
                return false;
            break;
        }
    }
	public function setDatabase($pdo){
		$this->db = $pdo;
		return $this;
	}
	public function resetDatabase(){
		$this->db = $this->FreePBX->Database;
		return $this;
	}

	public function getRightNav($request) {
		if(isset($request['view']) && $request['view'] == 'form'){
	    return load_view(__DIR__."/views/bootnav.php",array());
		}
    }
    public function upsert($vars){
        $vars['description'] = !empty($vars['description'])?$vars['description']:_("Unnamed");
        $vars['passwords'] = pinsets_clean($vars['passwords']);
        $sql = 'REPLACE INTO pinsets (pinsets_id, description, passwords, addtocdr, deptname) VALUES (:pinsets_id, :description, :passwords, :addtocdr, :deptname)';
        $this->db->prepare($sql)
            ->execute($vars);
    }
    public function addpinsetUsage($pinsetUsage) {
	$sql = 'INSERT INTO pinset_usage (dispname, foreign_id, pinsets_id) values (:dispname, :foreign_id, :pinsets_id)';
	$this->db->prepare($sql)
		->execute($pinsetUsage);
    }
}
