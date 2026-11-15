<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

/**
 * Upload or copy/paste program completion evidence via CSV format.
 *
 * The wizard is \tool_muprog\local\wizard\program_evidence_upload\wizard.
 *
 * @package    tool_muprog
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @copyright  2026 Petr Skoda
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../../config.php');

$programid = required_param('programid', PARAM_INT);

require_login();

(new \tool_muprog\local\wizard\program_evidence_upload\wizard($programid))->execute();
