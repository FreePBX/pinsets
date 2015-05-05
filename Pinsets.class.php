<?PHP
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
}
