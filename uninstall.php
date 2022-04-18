<?php
/* For license terms, see /license.txt */

/**
 * This script is included by main/admin/settings.lib.php when unselecting a plugin
 * and is meant to remove things installed by the install.php script in both
 * the global database and the courses tables.
 *
 * @package chamilo.plugin.courseway
 */

require_once __DIR__ . '/inc/CourseWayPlugin.php';

if (!api_is_platform_admin()) {
    exit('You must have admin permissions to uninstall plugins');
}

CourseWayPlugin::create()->uninstall();
