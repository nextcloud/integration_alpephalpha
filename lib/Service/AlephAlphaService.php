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

namespace OCA\AlephAlpha\Service;

use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;
use OCA\AlephAlpha\AppInfo\Application;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IL10N;
use OCP\Security\ICrypto;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Service to make requests to Aleph Alpha API
 */
class AlephAlphaService {
	private IClient $client;

	public function __construct(
		private LoggerInterface $logger,
		private IL10N $l10n,
		private IConfig $config,
		private ICrypto $crypto,
		IClientService $clientService,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @return array|string[]
	 */
	public function getModels(): array {
		return $this->request('GET', 'models_available');
	}

	/**
	 * @param string $prompt
	 * @param int $n
	 * @param int $maxTokens
	 * @return array|string[]
	 */
	public function createCompletion(string $prompt, int $n, int $maxTokens): array {
		$model = $this->getModel();

		if (str_ends_with($model, "-control")) {
			$prompt = " ### Instruction:\n" . $prompt . "\n### Response:";
		}

		return $this->request('POST', 'complete', [
			'model' => $model,
			'prompt' => $prompt,
			'maximum_tokens' => $maxTokens,
			'n' => $n,
		]);
	}

	/**
	 * Make an HTTP request to the Aleph Alpha API
	 * @param string $method HTTP request method
	 * @param string $endPoint The path to reach
	 * @return array decoded request result or error
	 */
	public function request(string $method, string $endPoint, ?array $body = null): array {
		try {
			$apiKey = $this->getApiKey();
			if ($apiKey === '') {
				return ['error' => 'An API key is required'];
			}

			$options = [
				'timeout' => $this->getTimeout(),
				'headers' => [
					'Authorization' => 'Bearer ' . $apiKey,
					'Content-Type' => 'application/json',
					'User-Agent' => 'Nextcloud Aleph Alpha integration',
				],
			];
			if ($body !== null) {
				$options['body'] = json_encode($body);
			}

			$url = 'https://api.aleph-alpha.com/' . $endPoint;

			if ($method === 'GET') {
				$response = $this->client->get($url, $options);
			} elseif ($method === 'POST') {
				$response = $this->client->post($url, $options);
			} else {
				return ['error' => $this->l10n->t('Bad HTTP method')];
			}
			$body = $response->getBody();
			$respCode = $response->getStatusCode();

			if ($respCode >= 400) {
				return ['error' => $this->l10n->t('Bad credentials')];
			} else {
				return json_decode($body, true) ?: [];
			}
		} catch (ClientException|ServerException $e) {
			$responseBody = $e->getResponse()->getBody()->getContents();
			$parsedResponseBody = json_decode($responseBody, true);
			$this->logger->warning('Aleph Alpha API error : ' . $e->getMessage(), ['response_body' => $responseBody, 'exception' => $e]);
			return ['error' => $e->getMessage(), 'body' => $parsedResponseBody];
		} catch (Exception|Throwable $e) {
			$this->logger->warning('Aleph Alpha API error : ' . $e->getMessage(), ['exception' => $e]);
			return ['error' => $e->getMessage()];
		}
	}

	public function getTimeout(): int {
		return (int)$this->config->getAppValue(Application::APP_ID, 'request_timeout', Application::DEFAULT_REQUEST_TIMEOUT) ?: Application::DEFAULT_REQUEST_TIMEOUT;
	}

	public function getApiKey(): string {
		$value = $this->config->getAppValue(Application::APP_ID, 'api_key');
		if ($value === '') {
			return '';
		}

		return $this->crypto->decrypt($value);
	}

	public function getModel(): string {
		return $this->config->getAppValue(Application::APP_ID, 'completion_model', Application::DEFAULT_COMPLETION_MODEL) ?: Application::DEFAULT_COMPLETION_MODEL;
	}
}
