<?php
# This Source Code Form is subject to the terms of the Mozilla Public
# License, v. 2.0. If a copy of the MPL was not distributed with this
# file, You can obtain one at http://mozilla.org/MPL/2.0/.
interface BugzillaCacheI
{
    public function set($key, $value, $ttl = 300);

    public function get($key);

    public function expire($key);

    public static function setup($updater);
}