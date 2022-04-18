<?php
/* For license terms, see /license.txt */

/**
 * Plugin class for the CourseWay plugin.
 *
 * @package chamilo.plugin.courseway
 *
 * @author Abel Perez <abelper54@gmail.com>
 */
class CourseWayPlugin extends Plugin
{
    public $isAdminPlugin = true;

    /**
     * Constructor.
     */
    protected function __construct()
    {
        parent::__construct(
            '1.0',
            'Abel Perez - idiomasPuentes',
            [
                'enable_plugin_courseway' => 'boolean',
            ]
        );

        $this->isAdminPlugin = true;
    }

    /**
     * Instance the plugin.
     *
     * @staticvar null $result
     *
     * @return CourseWayPlugin
     */
    public static function create()
    {
        static $result = null;

        return $result ? $result : $result = new self();
    }

    /**
     * This method creates the tables required to this plugin.
     */
    public function install()
    {
        
    }

    /**
     * This method drops the plugin tables.
     */
    public function uninstall()
    {

    }

    /**
     * This method update the previous plugin tables.
     */
    public function update()
    {

    }

    /**
     * By default new icon is invisible.
     *
     * @return bool
     */
    public function isIconVisibleByDefault()
    {
        return true;
    }

}
