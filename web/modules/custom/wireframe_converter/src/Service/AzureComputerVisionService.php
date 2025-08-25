<?php

namespace Drupal\wireframe_converter\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

/**
 * Service for interacting with Azure Computer Vision API.
 */
class AzureComputerVisionService {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new AzureComputerVisionService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    ClientInterface $http_client,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Analyze an image using Azure Computer Vision API.
   *
   * @param string $image_path
   *   The path to the image file.
   *
   * @return array|null
   *   The analysis result or null on failure.
   */
  public function analyzeImage($image_path) {
    $config = $this->configFactory->get('wireframe_converter.settings');
    $endpoint = $config->get('azure_endpoint');
    $key = $config->get('azure_key');

    if (empty($endpoint) || empty($key)) {
      $this->loggerFactory->get('wireframe_converter')->error('Azure Computer Vision API credentials not configured.');
      return NULL;
    }

    $this->loggerFactory->get('wireframe_converter')->info('Starting Azure Computer Vision analysis for: @path', ['@path' => $image_path]);
    $this->loggerFactory->get('wireframe_converter')->info('Using endpoint: @endpoint', ['@endpoint' => $endpoint]);

    try {
      $image_data = file_get_contents($image_path);
      if ($image_data === FALSE) {
        $this->loggerFactory->get('wireframe_converter')->error('Unable to read image file: @path', ['@path' => $image_path]);
        throw new \Exception('Unable to read image file.');
      }

      $this->loggerFactory->get('wireframe_converter')->info('Image file read successfully, size: @size bytes', ['@size' => strlen($image_data)]);

      $headers = [
        'Content-Type' => 'application/octet-stream',
        'Ocp-Apim-Subscription-Key' => $key,
      ];

      $params = [
        'visualFeatures' => 'Description,Tags,Color',
        'language' => 'en',
        'model-version' => 'latest',
      ];

      // Ensure proper URL construction without double slashes
      $endpoint = rtrim($endpoint, '/');
      $url = $endpoint . '/vision/v3.2/analyze?' . http_build_query($params);
      $this->loggerFactory->get('wireframe_converter')->info('Making API request to: @url', ['@url' => $url]);

      $response = $this->httpClient->post($url, [
        'headers' => $headers,
        'body' => $image_data,
      ]);

      $this->loggerFactory->get('wireframe_converter')->info('API response received, status: @status', ['@status' => $response->getStatusCode()]);

      $result = json_decode($response->getBody()->getContents(), TRUE);
      
      if ($result === NULL) {
        $this->loggerFactory->get('wireframe_converter')->error('Failed to decode JSON response from Azure API');
        return NULL;
      }

      $this->loggerFactory->get('wireframe_converter')->info('Azure analysis completed successfully');
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('wireframe_converter')->error('Azure API request failed: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('wireframe_converter')->error('Error in analyzeImage: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Extract text from an image using Azure Computer Vision API.
   *
   * @param string $image_path
   *   The path to the image file.
   *
   * @return array|null
   *   The OCR result or null on failure.
   */
  public function extractText($image_path) {
    $config = $this->configFactory->get('wireframe_converter.settings');
    $endpoint = $config->get('azure_endpoint');
    $key = $config->get('azure_key');

    if (empty($endpoint) || empty($key)) {
      $this->loggerFactory->get('wireframe_converter')->error('Azure Computer Vision API credentials not configured.');
      return NULL;
    }

    try {
      $image_data = file_get_contents($image_path);
      if ($image_data === FALSE) {
        throw new \Exception('Unable to read image file.');
      }

      $headers = [
        'Content-Type' => 'application/octet-stream',
        'Ocp-Apim-Subscription-Key' => $key,
      ];

      // Ensure proper URL construction without double slashes
      $endpoint = rtrim($endpoint, '/');
      $url = $endpoint . '/vision/v3.2/read/analyze';

      $response = $this->httpClient->post($url, [
        'headers' => $headers,
        'body' => $image_data,
      ]);

      // Get the operation location for polling.
      $operation_location = $response->getHeader('Operation-Location')[0] ?? NULL;
      
      if (!$operation_location) {
        throw new \Exception('No operation location received from API.');
      }

      // Poll for results.
      $result = $this->pollForResults($operation_location, $key);
      
      $this->loggerFactory->get('wireframe_converter')->info('Successfully extracted text using Azure Computer Vision API.');
      
      return $result;
    }
    catch (RequestException $e) {
      $this->loggerFactory->get('wireframe_converter')->error('Azure Computer Vision API OCR request failed: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('wireframe_converter')->error('Error extracting text: @error', ['@error' => $e->getMessage()]);
      return NULL;
    }
  }

  /**
   * Poll for OCR results.
   *
   * @param string $operation_location
   *   The operation location URL.
   * @param string $key
   *   The API key.
   *
   * @return array|null
   *   The OCR results or null on failure.
   */
  protected function pollForResults($operation_location, $key) {
    $max_attempts = 10;
    $attempt = 0;

    while ($attempt < $max_attempts) {
      try {
        $response = $this->httpClient->get($operation_location, [
          'headers' => [
            'Ocp-Apim-Subscription-Key' => $key,
          ],
        ]);

        $result = json_decode($response->getBody()->getContents(), TRUE);

        if ($result['status'] === 'succeeded') {
          return $result;
        }
        elseif ($result['status'] === 'failed') {
          throw new \Exception('OCR operation failed: ' . ($result['error']['message'] ?? 'Unknown error'));
        }

        // Wait before next attempt.
        sleep(1);
        $attempt++;
      }
      catch (RequestException $e) {
        $this->loggerFactory->get('wireframe_converter')->error('Error polling for OCR results: @error', ['@error' => $e->getMessage()]);
        return NULL;
      }
    }

    $this->loggerFactory->get('wireframe_converter')->error('OCR operation timed out after @attempts attempts', ['@attempts' => $max_attempts]);
    return NULL;
  }

} 