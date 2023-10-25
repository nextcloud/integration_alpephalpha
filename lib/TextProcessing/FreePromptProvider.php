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

namespace OCA\AlephAlpha\TextProcessing;

use OCA\AlephAlpha\AppInfo\Application;
use OCA\AlephAlpha\Service\AlephAlphaService;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IL10N;
use OCP\TextProcessing\FreePromptTaskType;
use OCP\TextProcessing\IProvider;

class FreePromptProvider implements IProvider {

	public function __construct(
		private AlephAlphaService $alephAlphaService,
		private IL10N $l10n,
	) {
	}

	public function getName(): string {
		return $this->l10n->t('Aleph Alpha integration');
	}

	/**
	 * @throws Exception
	 */
	public function process(string $prompt): string {
		$response = $this->alephAlphaService->createCompletion($prompt, 1, 100);
		if (isset($response['completions']) && is_array($response['completions']) && count($response['completions']) > 0) {
			$completion = $response['completions'][0];
			if (isset($completion['completion'])) {
				return $completion['completion'];
			}
		}
		throw new Exception('No result in Aleph Alpha response. ' . ($response['error'] ?? ''));
	}

	public function getTaskType(): string {
		return FreePromptTaskType::class;
	}
}
