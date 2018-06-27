<?php

namespace FreePBX\modules\Pinsets\Components;

class ConfigFile{
    // return an array of filenames to write
    // files named like pinset_N
    public $_pinsets = array();
    private static $obj;

    // FreePBX magic ::create() call
    public static function create(){
        if (!isset(self::$obj)) {
            self::$obj = new ConfigFile();
        }
        return self::$obj;
    }

    public function __construct(){
        self::$obj = $this;
    }

    public function get_filename(){
        $files = array();
        foreach (array_keys($this->_pinsets) as $pinset) {
            $files[] = 'pinset_'.$pinset;
        }
        return $files;
    }

    public function addPinsets($setid, $pins){
        $this->_pinsets[$setid] = $pins;
    }

    // return the output that goes in each of the files
    public function generateConf($file){
        $setid = ltrim($file, 'pinset_');
        return $this->_pinsets[$setid];
    }
}
