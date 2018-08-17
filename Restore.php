<?php
namespace FreePBX\modules\Pinsets;
use FreePBX\modules\Backup as Base;
class Restore Extends Base\RestoreBase{
  public function runRestore($jobid){
    $configs = reset($this->getConfigs());
    foreach ($configs as $pinset) {
        $this->FreePBX->Pinsets->upsert($pinset);
    }
  }
  public function processLegacy($pdo, $data, $tables, $unknownTables, $tmpfiledir)
  {
    $tables = array_flip($tables + $unknownTables);
    if (!isset($tables['pinsets'])) {
      return $this;
    }
    $cb = $this->FreePBX->Pinsets;
    $cb->setDatabase($pdo);
    $configs = $cb->listPinsets();
    $cb->resetDatabase();
    foreach (reset($configs) as $pinset) {
      $cb->upsert($pinset);
    }
    return $this;
  }
}
