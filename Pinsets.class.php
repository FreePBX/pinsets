<?php
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright (C) 2014 Schmooze Com Inc.
namespace FreePBX\modules;
class Pinsets implements \BMO {
	public function __construct($freepbx = null) {
		if ($freepbx == null) {
			throw new Exception("Not given a FreePBX Object");
		}
		$this->FreePBX = $freepbx;
		$this->db = $freepbx->Database;
	}
	public function install() {}
	public function uninstall() {}
	public function backup() {}
	public function restore($backup) {}
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
					redirect_standard();
				break;
				case "delete":
					pinsets_del($itemid);
					needreload();
					redirect_standard();
				break;
				case "edit":
					pinsets_edit($itemid,$request);
					needreload();
					redirect_standard('itemid','view');
				break;
			}
		}

	}
	function listPinsets() {
		$sql = "SELECT * FROM pinsets";
		$stmt = $this->db->prepare($sql);
 		$stmt->execute();
 		$ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
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

	public function getRightNav($request) {
		if(isset($request['view']) && $request['view'] == 'form'){
	    return load_view(__DIR__."/views/bootnav.php",array());
		}
	}
}
