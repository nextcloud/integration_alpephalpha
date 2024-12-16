<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AlephAlpha\Migration;

use Closure;
use OCA\AlephAlpha\AppInfo\Application;
use OCP\DB\ISchemaWrapper;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;
use OCP\Server;

class Version1000Date20241216142500 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		$config = Server::get(IConfig::class);
		$value = $config->getAppValue(Application::APP_ID, 'api_key');
		if ($value !== '') {
			$crypto = Server::get(ICrypto::class);
			$config->setAppValue(Application::APP_ID, 'api_key', $crypto->encrypt($value));
		}
	}
}
