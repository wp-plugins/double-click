<?php

namespace DoubleClick\Helper;

class Options {

    private static $wpdb;
    private static $post;

    public static function init($wpdb) {
        self::$wpdb = $wpdb;
        self::$post = filter_input_array(INPUT_POST);
    }

    public static function getTaxonomy($slot_id, $taxonomy) {
        $table_name = self::$wpdb->prefix . 'dfp_slots_taxonomy';
        $categories = self::$wpdb->get_results("SELECT taxonomy_id FROM {$table_name} WHERE slot_id = '{$slot_id}' AND taxonomy_type='" . $taxonomy . "'");
        if ($categories) {
            foreach ($categories as $cat) {
                $return[] = $cat->taxonomy_id;
            }
        }
        return isset($return) ? array_values($return) : array();
    }

    public static function getSlot($slot_id) {
        $table_name = self::$wpdb->prefix . 'dfp_slots';
        return self::$wpdb->get_row("SELECT * FROM {$table_name} WHERE id = '{$slot_id}'");
    }

    public static function getSlots() {
        $table_name = self::$wpdb->prefix . 'dfp_slots';
        $table_sizes = self::$wpdb->prefix . 'dfp_sizes';
        return self::$wpdb->get_results("SELECT {$table_name}.*,{$table_sizes}.size FROM {$table_name} INNER JOIN {$table_sizes} ON ({$table_name}.size_id = {$table_sizes}.id) ORDER BY {$table_sizes}.size");
    }

    public static function getSizes() {
        $table_name = self::$wpdb->prefix . 'dfp_sizes';
        return self::$wpdb->get_results("SELECT * FROM {$table_name} ORDER BY size");
    }

    public static function addSlots() {
        $table_name = self::$wpdb->prefix . 'dfp_slots';
        if (self::$post['delete']) {
            self::$wpdb->delete($table_name, array('id' => self::$post['slot_id']), array('%d'));
            \wp_redirect(admin_url('admin.php?page=DoubleClick'));
        } elseif (self::$post['size_id'] && self::$post['slot'] && self::$post['dfp_id']) {
            if (self::$post['id']) {
                self::$wpdb->update($table_name, array('dfp_id' => self::$post['dfp_id'], 'size_id' => self::$post['size_id'], 'slot' => self::$post['slot']), array('id' => self::$post['id']), array('%s', '%d', '%s'));
                $id = self::$post['id'];
                $table_name = self::$wpdb->prefix . 'dfp_slots_taxonomy';
                self::$wpdb->delete($table_name, array('slot_id' => $id));
            } else {
                self::$wpdb->insert($table_name, array('dfp_id' => self::$post['dfp_id'], 'size_id' => self::$post['size_id'], 'slot' => self::$post['slot']), array('%s', '%d', '%s'));
                $id = self::$wpdb->insert_id;
            }
            self::addTaxonomy($id, self::$post['post_category'], 'category');
            self::addTaxonomy($id, self::$post['post_page'], 'page');
            self::addTaxonomy($id, self::$post['post_special'], 'special');
            \wp_redirect(admin_url('admin.php?page=DoubleClick'));
        }
    }

    public static function addTaxonomy($slot_id, $data, $taxonomy_type) {
        if ($data) {
            $table_name = self::$wpdb->prefix . 'dfp_slots_taxonomy';
            foreach ($data as $taxonomy) {
                self::$wpdb->insert($table_name, array('taxonomy_type' => $taxonomy_type, 'slot_id' => $slot_id, 'taxonomy_id' => $taxonomy), array('%s', '%s', '%d', '%s'));
            }
        }
    }

    public static function addSizes() {
        $table_name = self::$wpdb->prefix . 'dfp_sizes';
        if (self::$post['delete']) {
            self::$wpdb->delete($table_name, array('id' => self::$post['id']), array('%d'));
        } elseif (self::$post['size'] && self::$post['width'] && self::$post['height']) {
            if (self::$post['id']) {
                self::$wpdb->update($table_name, array('size' => self::$post['size'], 'width' => self::$post['width'], 'height' => self::$post['height']), array('id' => self::$post['id']), array('%s', '%d', '%d'));
            } else {
                self::$wpdb->insert($table_name, array('size' => self::$post['size'], 'width' => self::$post['width'], 'height' => self::$post['height']), array('%s', '%d', '%d'));
            }
        }
    }

}
