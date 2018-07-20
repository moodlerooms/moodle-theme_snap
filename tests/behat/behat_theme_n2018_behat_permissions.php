<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Overrides for behat navigation.
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Exception\ExpectationException as ExpectationException,
    Behat\Mink\Element\NodeElement as NodeElement;

require_once(__DIR__ . '/../../../../lib/tests/behat/behat_permissions.php');

/**
 * Overrides to make behat permissions work with N2018.
 *
 * @author    David Castro <david.castro@blackboard.com>
 * @copyright Copyright (c) 2017 Blackboard Inc.
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_theme_n2018_behat_permissions extends behat_permissions {

    /**
     * Override of behat_permissions::i_set_the_following_system_permissions_of_role
     * @inheritdoc
     */
    public function i_set_the_following_system_permissions_of_role($rolename, $table) {
        global $DB;
        // Find role by name.
        $roleid = $DB->get_field('role', 'id', array('shortname' => strtolower($rolename)), MUST_EXIST);
        // Spawn a new system context instance.
        $systemcontext = \context_system::instance();
        // Add capabilities to role given the table of capabilities.
        // Using getRows() as we are not sure if tests writers will add the header.
        foreach ($table->getRows() as $key => $row) {

            if (count($row) !== 2) {
                $msg = 'You should specify a table with capability/permission columns';
                throw new ExpectationException($msg, $this->getSession());
            }

            list($capability, $permission) = $row;

            // Skip the headers row if it was provided.
            if (strtolower($capability) == 'capability' || strtolower($capability) == 'capabilities') {
                continue;
            }

            // Checking the permission value.
            $permissionconstant = 'CAP_'. strtoupper($permission);
            if (!defined($permissionconstant)) {
                throw new ExpectationException(
                    'The provided permission value "' . $permission . '" is not valid. Use Inherit, Allow, Prevent or Prohibited',
                    $this->getSession()
                );
            }

            // Converting from permission to constant value.
            $permissionvalue = constant($permissionconstant);

            \assign_capability($capability, $permissionvalue,
                $roleid, $systemcontext->id, true);

        }
        $systemcontext->mark_dirty();
    }
}