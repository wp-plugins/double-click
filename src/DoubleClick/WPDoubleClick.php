<?php

namespace DoubleClick;

use DoubleClick\Helper\Options;
use DoubleClick\Helper\AdBlock;
use Zend\View\Model\ViewModel;
use Zend\View\Renderer\PhpRenderer;
use Zend\View\Resolver\AggregateResolver;
use Zend\View\Resolver\RelativeFallbackResolver;
use Zend\View\Resolver\TemplateMapResolver;
use Zend\View\Resolver\TemplatePathStack;

class WPDoubleClick {

    protected static $wpdb;
    protected static $options;
    protected static $render;
    protected static $myOptions = array('dfp_mode');
    protected static $mySiteOptions = array();

    public static function init($wpdb) {
        self::$wpdb = $wpdb;
        self::$render = new PhpRenderer();
        self::$options = self::getOptions();
        self::getResolver(self::$render);
    }

    public static function run($wpdb) {
        self::init($wpdb);
        self::activateDoubleClick();
        if (is_admin()) {
            wp_enqueue_script('DoubleClickAdmin', plugins_url('../public/js/vendor/ControleOnline/admin.js', dirname(__FILE__)));
            wp_enqueue_style('DoubleClick', plugins_url('../public/css/vendor/ControleOnline/admin.css', dirname(__FILE__)));
            add_action('admin_menu', array('\DoubleClick\WPDoubleClick', 'menu'));
        }
        wp_enqueue_style('DoubleClick', plugins_url('../public/css/vendor/ControleOnline/dfp.css', dirname(__FILE__)));
        wp_enqueue_script('DoubleClickAdmin', plugins_url('../public/js/vendor/ControleOnline/dfp.js', dirname(__FILE__)));
        add_action('widgets_init', create_function('', 'return register_widget("\DoubleClick\Helper\Widget");'));
        add_action('init', array('\DoubleClick\Helper\AdBlock', 'count'));
    }

    protected static function getOptions($force = false) {
        if (!self::$options || $force) {
            foreach (self::$myOptions as $option) {
                $options[$option] = get_option($option);
            }
            self::$options = isset($options) ? $options : array();
        }
        return self::$options;
    }

    public static function getPage($page, array $parameters = array()) {
        $viewModel = new ViewModel($parameters);
        $viewModel->setTerminal(true);
        return self::$render->partial('plugin/' . $page . '.phtml', $viewModel);
    }

    public static function menu() {
        add_options_page('Double Click', 'Double Click', 'manage_options', 'DoubleClick', array('\DoubleClick\WPDoubleClick', 'plugin_options'));
    }

    public static function deactivateDoubleClick() {
        
    }

    private static function update_options() {
        $options = filter_input_array(INPUT_POST)? : array();
        foreach ($options as $key => $option) {
            if (in_array($key, self::$myOptions)) {
                $o = get_option($key);
                ($o || $o === '0') ? update_option($key, $option) : add_option($key, $option, '', 'yes');
            }
            if (in_array($key, self::$mySiteOptions)) {
                $o = get_site_option($key);
                ($o || $o === '0') ? update_site_option($key, $option) : add_site_option($key, $option, '', 'yes');
            }
        }
    }

    public static function activateDoubleClick() {
        global $wpdb;
        self::$wpdb = $wpdb;

        $charset_collate = self::$wpdb->get_charset_collate();
        $table_name = self::$wpdb->prefix . 'dfp_sizes';
        $sql_create_table = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            size varchar(50) NOT NULL,
            width bigint(20) unsigned NOT NULL default '0',
            height bigint(20) unsigned NOT NULL default '0',          
            PRIMARY KEY  (id)          
            ) " . $charset_collate . ";";
        \dbDelta($sql_create_table);

        $table_name = self::$wpdb->prefix . 'dfp_slots';
        $sql_create_table = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id bigint(20) unsigned NOT NULL auto_increment,
            slot  varchar(255) NOT NULL ,
            size_id int NULL ,
            dfp_id varchar(255) NOT NULL ,
            PRIMARY KEY (id),
            UNIQUE KEY dfp_id (size_id,dfp_id)
            ) " . $charset_collate . ";";
        \dbDelta($sql_create_table);

        $table_name = self::$wpdb->prefix . 'dfp_slots_taxonomy';
        $sql_create_table = "CREATE TABLE IF NOT EXISTS {$table_name}  (
            id  int NOT NULL AUTO_INCREMENT ,
            slot_id  int NULL ,
            taxonomy_id  int NULL ,
            taxonomy_type enum('category','page','special') NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slot (slot_id,taxonomy_id,taxonomy_type)
            ) " . $charset_collate . ";";
        \dbDelta($sql_create_table);

        $table_name = self::$wpdb->prefix . 'dfp_ad_block';
        $sql_create_table = "CREATE TABLE IF NOT EXISTS {$table_name}  (
            `id`  int NOT NULL AUTO_INCREMENT ,
            `block_date`  date NOT NULL,
            `block_time`  time NOT NULL,
            `block_qtd`  bigint NOT NULL,
            `block_url`  varchar(255) NULL,
            PRIMARY KEY (`id`)            
            ) " . $charset_collate . ";";
        \dbDelta($sql_create_table);
    }

    private static function getResolver($renderer) {
        $resolver = new AggregateResolver();
        $renderer->setResolver($resolver);
        $map = new TemplateMapResolver(array(
            'layout' => __DIR__ . '/view/layout.phtml'
        ));
        $stack = new TemplatePathStack(array(
            'script_paths' => array(
                dirname(__FILE__) . '/View/'
            )
        ));

        $resolver->attach($map)->attach($stack)->attach(new RelativeFallbackResolver($map))->attach(new RelativeFallbackResolver($stack));
    }

    public static function plugin_options() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        self::activateDoubleClick();

        Options::init(self::$wpdb);
        self::$options['dfpSizes'] = Options::getSizes();
        switch (filter_input(INPUT_GET, 'action')) {
            case 'slot':
                Options::addSlots();
                $id = filter_input(INPUT_GET, 'id');
                if ($id) {
                    self::$options['slot'] = Options::getSlot($id);
                    self::$options['categories'] = Options::getTaxonomy($id, 'category');
                    self::$options['pages'] = Options::getTaxonomy($id, 'page');
                    self::$options['special'] = Options::getTaxonomy($id, 'special');
                }
                echo self::getPage('slot', self::$options);
                break;
            case'dfpSizes':
                Options::addSizes();
                self::$options['dfpSizes'] = Options::getSizes();
                echo self::getPage('dfpSizes', self::$options);
                break;
            default:
                self::$options['AdBlockCount'] = AdBlock::getBlocksByDate(date('Y-m-d'));
                self::$options['slots'] = Options::getSlots();
                echo self::getPage('options', self::$options);
                break;
        }
    }

}
