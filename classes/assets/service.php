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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * Safe activity asset storage in Moodle File API.
 *
 * @package   mod_mcp
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mcp\assets;

use context_module;
use moodle_exception;
use moodle_url;

/** Store only allowlisted non-executable assets, never HTML or SVG. */
final class service {
    /** Upload a small encoded asset. Large videos should use Moodle's native file uploader. */
    public static function upload(int $cmid, string $filename, string $base64, int $userid): array {
        global $DB;
        $cm = get_coursemodule_from_id("mcp", $cmid, 0, false, MUST_EXIST);
        $ctx = context_module::instance($cmid);
        require_capability("mod/mcp:manage", $ctx, $userid);
        $bytes = base64_decode($base64, true);
        if ($bytes === false || strlen($bytes) < 1 || strlen($bytes) > 8388608) {
            throw new moodle_exception("invaliddata");
        }
        $filename = clean_param($filename, PARAM_FILE);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $types = [
            "jpg" => "image/jpeg", "jpeg" => "image/jpeg", "png" => "image/png",
            "gif" => "image/gif", "webp" => "image/webp", "mp3" => "audio/mpeg",
            "wav" => "audio/x-wav", "ogg" => "audio/ogg", "mp4" => "video/mp4",
            "webm" => "video/webm", "pdf" => "application/pdf",
        ];
        if (!isset($types[$extension])) {
            throw new moodle_exception("invaliddata");
        }
        $detected = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if ($detected !== $types[$extension] &&
                !($extension === "wav" && $detected === "audio/wav")) {
            throw new moodle_exception("invaliddata");
        }
        $fs = get_file_storage();
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $filename = substr($name, 0, 140) . "." . $extension;
        if ($fs->file_exists($ctx->id, "mod_mcp", "assets", 0, "/", $filename)) {
            throw new moodle_exception("invaliddata");
        }
        $file = $fs->create_file_from_string([
            "contextid" => $ctx->id, "component" => "mod_mcp", "filearea" => "assets",
            "itemid" => 0, "filepath" => "/", "filename" => $filename,
            "userid" => $userid, "license" => "allrightsreserved",
        ], $bytes);
        $url = moodle_url::make_pluginfile_url($ctx->id, "mod_mcp", "assets",
            0, "/", $file->get_filename());
        return ["filename" => $file->get_filename(), "url" => $url->out(false),
            "size" => $file->get_filesize(), "mimetype" => $file->get_mimetype()];
    }
}

