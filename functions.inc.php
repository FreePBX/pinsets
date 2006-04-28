<?php /* $Id */

// a class for generating passwdfile
// retrieve_conf will create an object of and <modulename>_conf classes,
// which can be used in <modulename>_get_conf below.
class pinsets_conf {
	// return an array of filenames to write
	// files named like pinset_N
	function get_filename() {
		foreach (array_keys($this->_pinsets) as $pinset) {
			$files[] = 'pinset_'.$pinset;
		}
		return $files;
	}
	
	function addPinsets($setid, $pins) {
		$this->_pinsets[$setid] = $pins;
	}
	
	// return the output that goes in each of the files
	function generateConf($file) {
		$setid = ltrim($file,'pinset_');
		$output = $this->_pinsets[$setid];
		return $output;
	}
}

/* 	Generates passwd files for pinsets
	We call this with retrieve_conf
*/
function pinsets_get_config($engine) {
	global $ext;  // is this the best way to pass this?
	global $asterisk_conf;
	global $pinsets_conf; // our pinsets object (created in retrieve_conf)
	switch($engine) {
		case "asterisk":
			$allpinsets = pinsets_list();
			if(is_array($allpinsets)) {
				foreach($allpinsets as $item) {
					// write our own pin list files
					$pinsets_conf->addPinsets($item['pinsets_id'],$item['passwords']);
				}
				
				// write out a macro that handles the authenticate
				$ext->add('macro-pinsets', 's', '', new ext_gotoif('${ARG2} = 1','cdr,1'));
				$ext->add('macro-pinsets', 's', '', new ext_authenticate($asterisk_conf['astetcdir'].'/pinset_${ARG1}'));
				// authenticate with the CDR option (a)
				$ext->add('macro-pinsets', 'cdr', '', new ext_authenticate($asterisk_conf['astetcdir'].'/pinset_${ARG1}','a'));
			}
		break;
	}
}

function pinsets_hookGet_config($engine) {
	global $ext;
	switch($engine) {
		case "asterisk":
			$hooklist = pinsets_list();
			if(is_array($hooklist)) {
				foreach($hooklist as $thisitem) {
					
					// get the used_by field
					if(empty($thisitem['used_by'])) {
						$usedby = "";
					} else {
						$usedby = $thisitem['used_by'];
					}
					
					// create an array from usedby
					$arrUsedby = explode(',',$usedby);
					
					if(is_array($arrUsedby)){
						foreach($arrUsedby as $strUsedby){
							// if it's an outbound route
							if(strpos($strUsedby,'routing_') !== false) {
								$route = substr($strUsedby,8);
								$context = 'outrt-'.$route;
								
								// get all the routes that are in this context
								$routes = core_routing_getroutepatterns($route);

								// we need to manipulate each route/extension
								foreach($routes as $rt) {
									//strip the pipe out as that's what we use for the dialplan extension
									$extension = '_'.str_replace('|','',$rt);
									// add dialplan
									$ext->splice($context, $extension, 0, new ext_macro('pinsets', $thisitem['pinsets_id'].'|'.$thisitem['addtocdr']));
								}						
								
							}
						}
					}
					
				}
			}
		break;
	}
}


//get the existing meetme extensions
function pinsets_list() {
	$results = sql("SELECT * FROM pinsets","getAll",DB_FETCHMODE_ASSOC);
	if(is_array($results)){
		foreach($results as $result){
			// check to see if we have a dept match for the current AMP User.
			if (checkDept($result['deptname'])){
				// return this item's dialplan destination, and the description
				$allowed[] = $result;
			}
		}
	}
	if (isset($allowed)) {
		return $allowed;
	} else { 
		return null;
	}
}

function pinsets_get($id){
	$results = sql("SELECT * FROM pinsets WHERE pinsets_id = '$id'","getRow",DB_FETCHMODE_ASSOC);
	return $results;
}

function pinsets_del($id){
	$results = sql("DELETE FROM pinsets WHERE pinsets_id = '$id'","query");
}

function pinsets_add($post){
	if(!pinsets_chk($post))
		return false;
	extract($post);
	$passwords = pinsets_clean($passwords);
	if(empty($description)) $description = 'Unnamed';
	$results = sql("INSERT INTO pinsets (description,passwords,addtocdr,deptname) values (\"$description\",\"$passwords\",\"$addtocdr\",\"$deptname\")");
}

function pinsets_edit($id,$post){
	if(!pinsets_chk($post))
		return false;
	extract($post);
	$passwords = pinsets_clean($passwords);
	if(empty($description)) $description = 'Unnamed';
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
		
		// remove blanks
		if ($passwords[$key] == "") unset($passwords[$key]);
	}
	
	// check for duplicates, and re-sequence
	$passwords = array_values(array_unique($passwords));
	
	if (is_array($passwords))
		return implode($passwords,"\n");
	else 
		return "";
}

// ensures post vars is valid
function pinsets_chk($post){
	return true;
}

// provide hook for routing
function pinsets_hook_core($viewing_itemid, $target_menuid) {
	switch ($target_menuid) {
		// only provide display for outbound routing
		case 'routing':
			//create a selection of available pinsets
			$pinsets = pinsets_list();
			$hookhtml = '
				<tr>
					<td><a href="#" class="info">'._("PIN Set").'<span>'._('Optional: Select a PIN set to use. If using this option, leave the Route Password field blank.').'</span></a>:</td>
					<td>
						<select name="pinsets">
							<option value=></option>
			';
			foreach($pinsets as $item) {
				$hookhtml .= "<option value={$item['pinsets_id']} ".(strpos($item['used_by'], "routing_{$viewing_itemid}") !== false ? 'selected' : '').">{$item['description']}</option>";
			}
			$hookhtml .= '
						</select>
					</td>
				</tr>
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
	
	// if routing was using post for the form (incl delete), i wouldn't need all these conditions
	if(isset($request['Submit']) || isset($request['Submit']) || $request['action'] == "delroute") {
		// get all pinsets defined
		$pinsets = pinsets_list();
		
		// loop through all the pinsets
		foreach($pinsets as $pinset) {
			
			// get the used_by field
			if(empty($pinset['used_by'])) {
				$usedby = "";
			} else {
				$usedby = $pinset['used_by'];
			}
			
			// remove the target if it's already in this row's used_by field
			$usedby = str_replace("{$request['display']}_{$viewing_itemid}","",$usedby);
			
			// create an array from usedby
			$arrUsedby = explode(',',$usedby);
			
			// add <targetmodule>_<viewing_itemid> to the array
			if(!empty($request['pinsets']) && ($request['pinsets'] == $pinset['pinsets_id']))
				$arrUsedby[] = "{$request['display']}_{$viewing_itemid}";
			
			// remove any duplicates
			$arrUsedby = array_values(array_unique($arrUsedby));
			
			// create a new string
			$strUsedby = implode($arrUsedby,',');
			
			// store the used_by column in the DB
			sql("UPDATE pinsets SET used_by = \"{$strUsedby}\" WHERE pinsets_id = \"{$pinset['pinsets_id']}\"");
		}
	}
}

?>
