<?php
namespace Drupal\ai_comment_moderation\Service;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

class AiModerationService {
  protected $httpClient;
  protected $logger;
  protected $configFactory;

  public function __construct(ClientInterface $http_client, LoggerInterface $logger, ConfigFactoryInterface $configFactory) {
    $this->httpClient = $http_client;
    $this->logger = $logger;
    $this->configFactory = $configFactory;
  }

  public function moderateText($text) {
    $api_key = $this->configFactory->get('ai_comment_moderation.settings')->get('openai_api_key');

    try {
      $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/moderations', [
        'headers' => [
          'Authorization' => "Bearer $api_key",
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'input' => $text,
        ],
      ]);
      $body = json_decode($response->getBody(), true);
      return $body['results'][0]['flagged'];
    }
    catch (\Exception $e) {
      $this->logger->error($e->getMessage());
      return FALSE;
    }
  }
}

