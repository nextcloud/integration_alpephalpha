<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\AlephAlpha\Controller;

use OCA\AlephAlpha\AppInfo\Application;
use OCA\AlephAlpha\Service\AlephAlphaService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Security\ICrypto;

class ConfigController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private IConfig $config,
		private AlephAlphaService $alephAlphaService,
		private ICrypto $crypto,
	) {
		parent::__construct($appName, $request);
	}

	/**
	 * Set admin config values
	 *
	 * @param array $values key/value pairs to store in app config
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	public function setAdminConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if ($key === 'api_key') {
				$value = $this->crypto->encrypt($value);
			}

			$this->config->setAppValue(Application::APP_ID, $key, $value);
		}
		return new DataResponse(null);
	}

	/**
	 * Returns the available models
	 *
	 * @return DataResponse
	 */
	public function getModels(): DataResponse {
		return new DataResponse([
			'completion_model' => Application::DEFAULT_COMPLETION_MODEL,
			'data' => $this->alephAlphaService->getModels(),
		]);
	}
}
