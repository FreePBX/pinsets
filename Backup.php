<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Backup Extends Base\BackupBase{
  public function runBackup($id,$transaction){
    $this->addDependency('core');
    $this->addConfigs($this->FreePBX->Pinsets->listPinsets());
  }
}