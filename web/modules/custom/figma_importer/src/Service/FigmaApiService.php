<?php

namespace Drupal\figma_importer\Service;

use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class FigmaApiService {
  /**
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  public function __construct($http_client, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('figma_importer');
  }

  /**
   * Fetches Figma file data by file ID.
   *
   * @param string $api_key
   *   The Figma API key.
   * @param string $file_id
   *   The Figma file ID.
   *
   * @return array|null
   *   The Figma file data as an array, or NULL on failure.
   */
  public function fetchFile($api_key, $file_id) {
    $url = "https://api.figma.com/v1/files/{$file_id}";
    try {
      $response = $this->httpClient->request('GET', $url, [
        'headers' => [
          'X-Figma-Token' => $api_key,
        ],
      ]);
      $data = json_decode($response->getBody(), TRUE);
      return $data;
    }
    catch (RequestException $e) {
      $this->logger->error('Figma API request failed: @message', ['@message' => $e->getMessage()]);
      return NULL;
    }
  }

  public function getImageUrl(string $file_id, string $node_id, string $api_key): ?string {
    $client = \Drupal::httpClient();
    $response = $client->get("https://api.figma.com/v1/images/$file_id", [
      'headers' => [
        'X-Figma-Token' => $api_key,
      ],
      'query' => [
        'ids' => $node_id,
        'format' => 'png',
      ],
    ]);
    $data = json_decode($response->getBody(), true);
    return $data['images'][$node_id] ?? NULL;
  }  
} 