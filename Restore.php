<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		if (!empty($configs)) {
			if(isset($configs['listPinsets'])) {
				foreach ($configs['listPinsets'] as $pinset) {
					$this->FreePBX->Pinsets->upsert($pinset);
				}
			}
			if(isset($configs['pinsetUsage'])) {
				foreach ($configs['pinsetUsage'] as $pinsetUsage) {
					$this->FreePBX->Pinsets->addpinsetUsage($pinsetUsage);
				}
			}
		}
	}
	public function processLegacy($pdo, $data, $tables, $unknownTables) {
		if(!in_array('pinsets',$tables)) {
			$this->log("Backup does not contain pinsets table");
			return;
		}
		$bmo = $this->FreePBX->Pinsets;
		$bmo->setDatabase($pdo);
		$pinsets = $bmo->listPinsets();
		$bmo->resetDatabase();
		foreach($pinsets as $pin) {
				$passwords = explode('\n',$pin['passwords']);
				$pass = implode($passwords,"\n");
				$pin['passwords'] = $pass;
				pinsets_add($pin);
		}
		if(in_array('pinset_usage',$tables)) {
			$pinsetUsage = array('pinset_usage');
			$this->restoreLegacyDatabase($pdo, $pinsetUsage);
		}
	}
}
