<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
	$configs = [
		"listPinsets" => $this->FreePBX->Pinsets->listPinsets(),
		"pinsetUsage" => $this->dumppinsetUsage(),
		];
	$this->addDependency('core');
	$this->addConfigs($configs);
  }
  public function dumppinsetUsage() {
	$sql = 'SELECT * FROM pinset_usage';
	$stmt = $this->FreePBX->Database->prepare($sql);
	$stmt->execute();
	return $stmt->fetchAll(\PDO::FETCH_ASSOC);
  }
}
