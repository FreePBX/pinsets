<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore(){
		$configs = $this->getConfigs();
		if (!empty($configs)) {
				foreach ($configs as $pinset) {
						$this->FreePBX->Pinsets->upsert($pinset);
				}
		}
	}
	public function processLegacy($pdo, $data, $tables, $unknownTables) {
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
	}
}
