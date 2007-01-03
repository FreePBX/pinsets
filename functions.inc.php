<?php /* $Id */

// a class for generating passwdfile
// retrieve_conf will create an object of and <modulename>_conf classes,
// which can be used in <modulename>_get_conf below.
class pinsets_conf {
	// return an array of filenames to write
	// files named like pinset_N
	function get_filename() {
		$files = array();
		if (isset($this->_pinsets) && is_array($this->_pinsets)) {
			foreach (array_keys($this->_pinsets) as $pinset) {
				$files[] = 'pinset_'.$pinset;
			}
			return $files;
		} else {
			// jusr return an empty array
			return array();
		}
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
									$extension = str_replace('|','',$rt);
									// If there are any wildcards in there, add a _ to the start
									if (preg_match("/\.|z|x|\[|\]/i", $extension)) { $extension = "_".$extension; }
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
	global $asterisk_conf;
	
	$filename = $asterisk_conf['astetcdir'].'/pinset_'.$id;
	if (file_exists($filename)) {
		unlink($filename);
	}

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

//removes a pinset from a route and shifts priority for all outbound routing pinsets
function pinsets_adjustroute($route,$action,$routepinset='',$direction='',$newname='') {
    $priority = (int)substr($route,0,3);
    //create a selection of available pinsets
    $pinsets = pinsets_list();
	// loop through all the pinsets
	if(is_array($pinsets)){
		foreach($pinsets as $pinset) {
				
			// get the used_by field
			if(empty($pinset['used_by'])) {
				$usedby = "";
			} else {
				$usedby = $pinset['used_by'];
			}
					
			// remove the target if it's already in this row's used_by field
			//$usedby = str_replace("routing_{$route}","",$usedby);
					
			// create an array from usedby
			$arrUsedby = explode(',',$usedby);
			
			for($i=0;$i<count($arrUsedby);$i++) {
				if (substr($arrUsedby[$i],0,8)=='routing_') {
                    switch($action){
                        case 'delroute':
                    		if ($arrUsedby[$i] == "routing_{$route}") {
								unset($arrUsedby[$i]);
		              		}
                    		$usedbypriority = (int)substr($arrUsedby[$i],8,3);
							$usedbyroute = substr($arrUsedby[$i],12);
                    		if ($usedbypriority > $priority) {
		                        $newpriority = str_pad($usedbypriority - 1, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
							}
						break;
                        case 'prioritizeroute';
                        	$addpriority = ($direction=='up')?-1:1;
                    		$usedbypriority = (int)substr($arrUsedby[$i],8,3);
							$usedbyroute = substr($arrUsedby[$i],12);
                    		if ($priority + $addpriority == $usedbypriority) {
		                        $newpriority = str_pad($priority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
							}
                		    if ($arrUsedby[$i] == "routing_{$route}") {
		                        $newpriority = str_pad($priority + $addpriority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$usedbyroute;
		              		}

						break;
                        case 'renameroute';
                    		if ($arrUsedby[$i] == "routing_{$route}") {
		                        $newpriority = str_pad($priority, 3, "0", STR_PAD_LEFT);
                        		$arrUsedby[$i] = 'routing_'.$newpriority.'-'.$newname;
		              		}
						break;
                        case 'editroute';
                        	$usedbyroute = (int)substr($arrUsedby[$i],12);
                    		if ($arrUsedby[$i] == "routing_{$route}") {
								unset($arrUsedby[$i]);
							}
                        break;
					}
				}
			}	
            
			// save the route in the selected pin
			if ($routepinset == $pinset['pinsets_id'] && $action == 'editroute') {
				$arrUsedby[] = 'routing_'.$route;
			}

			// remove any duplicates
			$arrUsedby = array_values(array_unique($arrUsedby));
				
			// create a new string
			$strUsedby = implode($arrUsedby,',');
	
			// Insure there's no leading or trailing commas
			$strUsedby = trim ($strUsedby, ',');

					
			// store the used_by column in the DB
			sql("UPDATE pinsets SET used_by = \"{$strUsedby}\" WHERE pinsets_id = \"{$pinset['pinsets_id']}\"");
		}
	}
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

			if (is_array($pinsets))
			{
				foreach($pinsets as $item) {
					if (isset($viewing_itemid) && $viewing_itemid <> '' && strpos($item['used_by'], "routing_{$viewing_itemid}") !== false) {
						$selected = 'selected';
					} else {
						$selected = '';
					}
					$hookhtml .= "<option value={$item['pinsets_id']} ".$selected.">{$item['description']}</option>";
				}
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
	switch ($request['display']) {
        case 'routing':
		// if routing was using post for the form (incl delete), i wouldn't need all these conditions
		//		if(isset($request['Submit']) || (isset($request['action']) && ($request['action'] == "delroute" || $request['action'] == "prioritizeroute" || $request['action'] == "renameroute"))) {        

			$action = (isset($request['action']))?$request['action']:null;
			$route = $viewing_itemid;
			if (isset($request['reporoutekey']) && $action == 'prioritizeroute') {
                $outbound_routes = core_routing_getroutenames();
				$route = $outbound_routes[(int)$request['reporoutekey']][0];
            }
			if (isset($request['Submit']) ) {
				$action = (isset($action))?$action:'editroute';
        	}
			if (isset($action)) {            
            	$direction = (isset($request['reporoutedirection']))?$request['reporoutedirection']:null;
                $newname = (isset($request['newroutename']))?$request['newroutename']:null;
				pinsets_adjustroute($route,$action,$request['pinsets'],$direction,$newname);
			}

        break;
	}
}


?>
