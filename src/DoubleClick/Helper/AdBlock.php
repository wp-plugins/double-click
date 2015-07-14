<?php

namespace DoubleClick\Helper;

class AdBlock {

    protected static $wpdb;
    protected static $table_name;

    static function init() {
        global $wpdb;
        if (!self::$wpdb) {
            self::$wpdb = $wpdb;
        }
        if (!self::$table_name) {
            self::$table_name = self::$wpdb->prefix . 'dfp_ad_block';
        }
    }

    static function getBlocksByDate($date) {
        self::init();
        return self::$wpdb->get_row("SELECT SUM(block_qtd) AS block_sum,COUNT(*) AS block_qtd FROM " . self::$table_name . " WHERE block_date = '{$date}'");
    }

    static function count() {
        self::init();
        $count = filter_input(INPUT_GET, 'adb');
        $referer = urldecode(filter_input(INPUT_GET, 'r'));
        if ($count) {
            self::$wpdb->insert(
                    self::$table_name, array(
                'block_date' => date('Y-m-d'),
                'block_time' => date('g:i:s'),
                'block_qtd' => ((int) $count)? : 1,
                'block_url' => $referer
                    ), array('%s', '%s', '%d', '%s')
            );
            echo $count;
            exit(0);
        }
    }

}
