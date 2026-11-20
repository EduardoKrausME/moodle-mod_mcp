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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Tests for the construction screen connection indicators.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp;

use advanced_testcase;
use mod_mcp\output\activity;

/**
 * Only the current user's active OAuth connections count for the activity guide.
 *
 * @covers \mod_mcp\output\activity
 */
final class output_activity_test extends advanced_testcase {
    /**
     * The empty activity has no connection for the current user.
     */
    public function test_empty_activity_without_connection(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module("mcp", ["course" => $course->id]);
        $cm = get_coursemodule_from_instance("mcp", $module->id);

        $data = activity::page($module, $cm, true, false);
        $this->assertFalse($data["hascontent"]);
        $this->assertFalse($data["hasconnection"]);
        $this->assertArrayNotHasKey("haslocalmcp", $data);
        $this->assertSame(
            (new \moodle_url("/local/mcp/new_connection.php"))->out(false),
            $data["connectionurl"]
        );
        $this->assertFalse(activity::has_active_connection(0));
    }

    /**
     * Connections must belong to this user and have active grant and client states.
     */
    public function test_only_enabled_nonrevoked_own_connections_count(): void {
        global $DB;

        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $now = time();

        $clientid = $DB->insert_record("local_mcp_oauth_client", (object)[
            "clientid" => "mod_mcp_output_test",
            "name" => "Test client",
            "redirecturis" => '["https://example.test/callback"]',
            "enabled" => 1,
            "clienttype" => "public",
            "dynamic" => 0,
            "timecreated" => $now,
            "timemodified" => $now,
        ]);
        $connectionid = $DB->insert_record("local_mcp_connection", (object)[
            "userid" => $user->id,
            "clientid" => $clientid,
            "scopes" => "mcp:read mcp:write",
            "enabled" => 1,
            "timecreated" => $now,
        ]);

        $this->assertTrue(activity::has_active_connection((int)$user->id));
        $this->assertFalse(activity::has_active_connection((int)$other->id));

        $DB->set_field("local_mcp_connection", "enabled", 0, ["id" => $connectionid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));

        $DB->set_field("local_mcp_connection", "enabled", 1, ["id" => $connectionid]);
        $DB->set_field("local_mcp_connection", "revokedat", $now, ["id" => $connectionid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));

        $DB->set_field("local_mcp_connection", "revokedat", null, ["id" => $connectionid]);
        $DB->set_field("local_mcp_oauth_client", "enabled", 0, ["id" => $clientid]);
        $this->assertFalse(activity::has_active_connection((int)$user->id));
    }
}
