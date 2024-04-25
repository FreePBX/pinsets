<?php /* $Id */
if (!defined('FREEPBX_IS_AUTH')) { die('No direct script access allowed'); }
//	License for all code of this FreePBX module can be found in the license file inside the module directory
//	Copyright 2013 Schmooze Com Inc.
//

// a class for generating passwdfile
/* 	Generates passwd files for pinsets
	We call this with retrieve_conf
 */
function pinsets_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	$pinsets_conf = FreePBX\modules\Pinsets\Components\ConfigFile::create();
	$FreePBX = FreePBX::Create();
	$astman = $FreePBX->astman;
	$astetcdir = $FreePBX->Config->get("ASTETCDIR");
	$allpinsets = $FreePBX->Pinsets->listPinsets();
	if(is_array($allpinsets)) {
		if(!$astman->connected()){
			throw new Exception('Could not talk to the Asterisk Manager. Is Asterisk running and as the correct user?');
		}
		foreach($allpinsets as $item) {
			// write our own pin list files
			$pinsets_conf->addPinsets($item['pinsets_id'],$item['passwords']);
			//lets write to astDB
			$astman->database_deltree("PINSETS/".$item['pinsets_id']);
			$pass =  explode("\n",$item['passwords']);
			foreach($pass as $pin) {
				$astman->database_put("PINSETS/".$item['pinsets_id'],$pin,$item['pinsets_id']);
			}
		}
		$c = 'macro-pinsets';
		// write out a macro that handles the authenticate
		$ext->add($c, 's', '', new ext_set('try','1'));
		$ext->add($c, 's', '', new ext_gotoif('$[${ARG2} = 1]','cdr,1'));
		$ext->add($c, 's', '', new ext_gotoif('$["${DB(AMPUSER/${AMPUSER}/pinless)}" != "NOPASSWD"]','auth:return'));
		$ext->add($c, 's', 'auth', new ext_progress());
		$ext->add($c, 's', '', new ext_read('dtmf','agent-pass',0,'n',1,10));
		$ext->add($c, 's', '', new ext_gotoif('$["${DB(PINSETS/${ARG1}/${dtmf})}" = "${ARG1}"]', 'return:askpin'));
		$ext->add($c, 's', 'askpin', new ext_set('try','$[${try}+1]'));
		$ext->add($c, 's', '', new ext_gotoif('$[${try} > 4]', 'hangup'));
		$ext->add($c, 's', '', new ext_read('dtmf','auth-incorrect',0,'n',1,10));
		$ext->add($c, 's', 'validate', new ext_gotoif('$["${DB(PINSETS/${ARG1}/${dtmf})}" = "${ARG1}"]', 'return:askpin'));
		$ext->add($c, 's', 'hangup', new ext_hangup());
		$ext->add($c, 's', 'return', new ext_return());

		// authenticate with the CDR option (a)
		$ext->add($c, 'cdr', '', new ext_gotoif('$["${DB(AMPUSER/${AMPUSER}/pinless)}" != "NOPASSWD"]', 'auth:return'));
		$ext->add($c, 'cdr', '', new ext_set('try','1'));
		$ext->add($c, 'cdr', 'auth', new ext_progress());
		$ext->add($c, 'cdr', '', new ext_read('dtmf','agent-pass',0,'n',1,10));
		$ext->add($c, 'cdr', '', new ext_gotoif('$["${DB(PINSETS/${ARG1}/${dtmf})}" = "${ARG1}"]', 'setaccountcode:askpin'));
		$ext->add($c, 'cdr', 'askpin', new ext_set('try','$[${try}+1]'));
		$ext->add($c, 'cdr', '', new ext_gotoif('$[${try} > 4]', 'hangup'));
		$ext->add($c, 'cdr', '', new ext_read('dtmf','auth-incorrect',0,'n',1,10));
		$ext->add($c, 'cdr', 'validate', new ext_gotoif('$["${DB(PINSETS/${ARG1}/${dtmf})}" = "${ARG1}"]', 'setaccountcode:askpin'));
		$ext->add($c, 'cdr', 'hangup', new ext_hangup());
		$ext->add($c, 'cdr', 'setaccountcode', new ext_set('CHANNEL(accountcode)','${dtmf}'));
		$ext->add($c, 'cdr', 'return', new ext_return());

	}

	$usage_list = pinsets_list_usage('routing');
	if (is_array($usage_list) && count($usage_list)) {
        $addtocdr = array();
		foreach ($allpinsets as $pinset) {
			$addtocdr[$pinset['pinsets_id']] = $pinset['addtocdr'];
		}
		foreach ($usage_list as $thisroute) {
			$context = 'outrt-'.$thisroute['foreign_id'];
			$patterns = core_routing_getroutepatternsbyid($thisroute['foreign_id']);
			foreach ($patterns as $pattern) {
				$fpattern = core_routing_formatpattern($pattern);
				$exten = $fpattern['dial_pattern'];
				$ext->splice($context, $exten, 1, new ext_macro('pinsets', $thisroute['pinsets_id'].','.$addtocdr[$thisroute['pinsets_id']]),'pinsets');
			}
		}
	}
}

function pinsets_list_usage($dispname=true) {
	$sql = 'SELECT * FROM `pinset_usage`';
	if ($dispname !== true) {
		$sql .= " WHERE `dispname` = '$dispname'";
	}
	return sql($sql,'getAll',DB_FETCHMODE_ASSOC);
}

//get the existing meetme extensions
function pinsets_list() {
    FreePBX::Modules()->deprecatedFunction();
	return FreePBX::Pinsets()->listPinsets();
}

function pinsets_get($id){
	$results = sql("SELECT * FROM pinsets WHERE pinsets_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function pinsets_del($id){
	global $amp_conf;
	global $astman;
	$filename = $amp_conf['ASTETCDIR'].'/pinset_'.$id;
	if (file_exists($filename)) {
		unlink($filename);
	}
	$astman->database_deltree("PINSETS/".$id);
	$results = sql("DELETE FROM pinsets WHERE pinsets_id = '$id'","query");
	$results = sql("DELETE FROM pinset_usage WHERE pinsets_id = '$id'","query");
}

function pinsets_add($post){
	if(!pinsets_chk($post))
		return false;
	extract($post);
	$passwords = pinsets_clean($passwords);
	if(empty($description)) $description = _('Unnamed');
	if (empty($deptname)) $deptname = '';
	$results = sql("INSERT INTO pinsets (description,passwords,addtocdr,deptname) values (\"$description\",\"$passwords\",\"$addtocdr\",\"$deptname\")");
}

function pinsets_edit($id,$post){
	if(!pinsets_chk($post))
		return false;
	extract($post);
	$passwords = pinsets_clean($passwords);
	if(empty($description)) $description = _('Unnamed');
	if (empty($deptname)) $deptname = '';
	$results = sql("UPDATE pinsets SET description = \"$description\", passwords = \"$passwords\", addtocdr = \"$addtocdr\", deptname = \"$deptname\" WHERE pinsets_id = \"$id\"");
}

// clean and remove duplicates
function pinsets_clean($passwords) {
	$passwords = explode("\n",$passwords);

	if (!$passwords) {
		$passwords = null;
	}

	foreach (array_keys($passwords) as $key) {
		//trim it
		$passwords[$key] = trim($passwords[$key]);

		// remove invalid chars
		$passwords[$key] = preg_replace("/[^0-9#*]/", "", $passwords[$key]);

		// remove empty passwords
		if ($passwords[$key] == "") {
			unset($passwords[$key]);
		}
	}

	// check for duplicates, and re-sequence
	$passwords = array_values(array_unique($passwords));

	if (is_array($passwords)) {
		return implode("\n",$passwords);
	} else {
		return "";
	}
}

// ensures post vars is valid <~~No it doesn't
function pinsets_chk($post){
	return true;
}

//removes a pinset from a route and shifts priority for all outbound routing pinsets
function pinsets_adjustroute($route_id,$action,$routepinset='') {
	global $db;
	$dispname = 'routing';
	$route_id = $db->escapeSimple($route_id);
	$routepinset = $db->escapeSimple($routepinset);

	switch ($action) {
	case 'delroute':
		sql('DELETE FROM pinset_usage WHERE foreign_id ='.q($route_id)." AND dispname = '$dispname'");
		break;
	case 'addroute':
		if ($routepinset != '') {
			sql("REPLACE INTO pinset_usage (pinsets_id, dispname, foreign_id) VALUES ($routepinset, '$dispname', '$route_id')");
		}
		break;
	case 'delayed_insert_route':
		if ($routepinset != '') {
			sql("REPLACE INTO pinset_usage (pinsets_id, dispname, foreign_id) VALUES ($routepinset, '$dispname', '$route_id')");
		}
		break;
	case 'editroute':
		if ($routepinset != '') {
			sql("REPLACE INTO pinset_usage (pinsets_id, dispname, foreign_id) VALUES ($routepinset, '$dispname', '$route_id')");
		} else {
			sql('DELETE FROM pinset_usage WHERE foreign_id ='.q($route_id)." AND dispname = '$dispname'");
		}
		break;
	}
}

// provide hook for routing
function pinsets_hook_core($viewing_itemid, $target_menuid) {
	global $db;
	switch ($target_menuid) {
	case 'routing':
		//create a selection of available pinsets
		$pinsets = FreePBX::Pinsets()->listPinsets();
;
		if ($viewing_itemid == '') {
			$selected_pinset = '';
		} else {
			$selected_pinset = $db->getOne("SELECT pinsets_id FROM pinset_usage WHERE dispname='routing' AND foreign_id='".$db->escapeSimple($viewing_itemid)."'");
			if(DB::IsError($selected_pinset)) {
				die_freepbx($selected_pinset->getMessage());
			}
		}

		$hookhtml = '
			<!--PINSET HOOK-->
			<div class="element-container">
				<div class="row">
					<div class="col-md-12">
						<div class="row">
							<div class="form-group">
								<div class="col-md-3">
									<label class="control-label" for="pinsets">'. _("PIN Set").'</label>
									<i class="fa fa-question-circle fpbx-help-icon" data-for="pinsets"></i>
								</div>
								<div class="col-md-9">
									<select name="pinsets" class="form-control">
										<option value="">'._('None').'</option>';
		if (is_array($pinsets)) {
			foreach($pinsets as $item) {
				$selected = $selected_pinset == $item['pinsets_id'] ? 'selected' : '';
				$hookhtml .= "<option value={$item['pinsets_id']} ".$selected.">{$item['description']}</option>";
			}
		}

		$hookhtml .= '				</select>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<span id="pinsets-help" class="help-block fpbx-help-block">'._('Optional: Select a PIN set to use. If using this option, leave the Route Password field blank.').'</span>
					</div>
				</div>
			</div>
			<!--END PINSETHOOK-->
			';
		return $hookhtml;
		break;
	default:
		return false;
		break;
	}
}

function pinsets_hookProcess_core($viewing_itemid, $request) {

	// Record any hook selections made by target modules
	// We'll add these to the pinset's "used_by" column in the format <targetmodule>_<viewing_itemid>
	// multiple targets could select a single pinset, so we'll comma delimiter them

	// this is really a crappy way to store things.
	// Any module that is hooked by pinsets when submitted will result in all the "used_by" fields being re-written
	switch ($request['display']) {
	case 'routing':
		$action = (isset($request['action']))?$request['action']:null;
		$route_id = $viewing_itemid;
		if (isset($request['Submit']) ) {
			$action = (isset($action))?$action:'editroute';
		}
		if ($action) {
			pinsets_adjustroute($route_id,$action,$request['pinsets'] ?? '');
		}
		break;
	}
}
?>
