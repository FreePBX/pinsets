<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
	public function runRestore($jobid){
		$configs = $this->getConfigs();
		if (!empty($configs)) {
				foreach ($configs as $pinset) {
						$this->FreePBX->Pinsets->upsert($pinset);
				}
		}
	}
	public function processLegacy($pdo, $data, $tables, $unknownTables) {
		$this->restoreLegacyDatabase($pdo);
	}
}
