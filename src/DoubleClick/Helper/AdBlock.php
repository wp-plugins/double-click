<?php

namespace DoubleClick\Helper;

class AdBlock {

    static function count() {
        $adb = filter_input(INPUT_GET, 'adb');
        if ($adb) {
            $count = (int) get_option('AdBlockCount');            
            $count ? update_option('AdBlockCount', $count + 1) : add_option('AdBlockCount', $count + 1, '', 'yes');
            echo $count + 1;
            exit(0);
        }
    }

}
