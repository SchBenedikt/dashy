<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript(OCA\Dashy\AppInfo\Application::APP_ID, OCA\Dashy\AppInfo\Application::APP_ID . '-main');
Util::addStyle(OCA\Dashy\AppInfo\Application::APP_ID, OCA\Dashy\AppInfo\Application::APP_ID . '-main');

?>

<div id="dashy"></div>
