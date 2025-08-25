<?php

namespace Drupal\wireframe_converter\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;

/**
 * Service for converting wireframes to Drupal content.
 */
class WireframeConverterService {

  /**
   * The Azure Computer Vision service.
   *
   * @var \Drupal\wireframe_converter\Service\AzureComputerVisionService
   */
  protected $azureVisionService;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new WireframeConverterService object.
   *
   * @param \Drupal\wireframe_converter\Service\AzureComputerVisionService $azure_vision_service
   *   The Azure Computer Vision service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    AzureComputerVisionService $azure_vision_service,
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->azureVisionService = $azure_vision_service;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * Convert a wireframe image to Drupal content.
   *
   * @param string $image_path
   *   The path to the wireframe image.
   * @param array $options
   *   Additional options for conversion.
   *
   * @return array
   *   The conversion result with status and created content.
   */
  public function convertWireframe($image_path, array $options = []) {
    $result = [
      'success' => FALSE,
      'message' => '',
      'content' => [],
    ];

    try {
      // Debug: Log the start of conversion
      $this->loggerFactory->get('wireframe_converter')->info('Starting wireframe conversion for: @path', ['@path' => $image_path]);

      // Analyze the image using Azure Computer Vision.
      $this->loggerFactory->get('wireframe_converter')->info('Calling Azure Computer Vision analyzeImage...');
      $analysis = $this->azureVisionService->analyzeImage($image_path);
      
      if (!$analysis) {
        $this->loggerFactory->get('wireframe_converter')->error('Azure Computer Vision analysis returned null');
        throw new \Exception('Failed to analyze image with Azure Computer Vision API.');
      }

      $this->loggerFactory->get('wireframe_converter')->info('Azure analysis completed successfully');

      // Extract text from the image.
      $this->loggerFactory->get('wireframe_converter')->info('Calling Azure Computer Vision extractText...');
      $ocr_result = $this->azureVisionService->extractText($image_path);
      
      if (!$ocr_result) {
        $this->loggerFactory->get('wireframe_converter')->error('Azure Computer Vision OCR returned null');
        throw new \Exception('Failed to extract text from image.');
      }

      $this->loggerFactory->get('wireframe_converter')->info('Azure OCR completed successfully');

      // Process the analysis results.
      $wireframe_data = $this->processAnalysisResults($analysis, $ocr_result);
      
      // Create Drupal content based on the wireframe data.
      $content = $this->createDrupalContent($wireframe_data, $options);
      
      $result['success'] = TRUE;
      $result['message'] = 'Wireframe successfully converted to Drupal content.';
      $result['content'] = $content;
      $result['wireframe_data'] = $wireframe_data;

      $this->loggerFactory->get('wireframe_converter')->info('Successfully converted wireframe to Drupal content.');
    }
    catch (\Exception $e) {
      $result['message'] = 'Error converting wireframe: ' . $e->getMessage();
      $this->loggerFactory->get('wireframe_converter')->error('Error converting wireframe: @error', ['@error' => $e->getMessage()]);
    }

    return $result;
  }

  /**
   * Process Azure Computer Vision analysis results.
   *
   * @param array $analysis
   *   The image analysis results.
   * @param array $ocr_result
   *   The OCR results.
   *
   * @return array
   *   Processed wireframe data.
   */
  protected function processAnalysisResults(array $analysis, array $ocr_result) {
    $wireframe_data = [
      'title' => '',
      'subtitle' => '',
      'date' => '',
      'description' => '',
      'navigation' => [],
      'sections' => [],
      'elements' => [],
      'text_content' => [],
      'lists' => [],
      'images' => [],
    ];

    // Extract title from description or tags.
    if (!empty($analysis['description']['captions'])) {
      $wireframe_data['title'] = $analysis['description']['captions'][0]['text'];
    }
    elseif (!empty($analysis['tags'])) {
      $wireframe_data['title'] = $analysis['tags'][0]['name'];
    }

    // Extract description.
    if (!empty($analysis['description']['captions'])) {
      $wireframe_data['description'] = $analysis['description']['captions'][0]['text'];
    }

    // Process extracted text with better structure detection.
    if (!empty($ocr_result['analyzeResult']['readResults'])) {
      $this->processTextContent($ocr_result['analyzeResult']['readResults'], $wireframe_data);
    }

    // Process detected objects as UI elements (if available).
    if (!empty($analysis['objects'])) {
      foreach ($analysis['objects'] as $object) {
        $wireframe_data['elements'][] = [
          'type' => $object['object'],
          'confidence' => $object['confidence'],
          'location' => $object['rectangle'],
        ];
      }
    } else {
      // If objects not available, create basic elements from tags
      if (!empty($analysis['tags'])) {
        foreach ($analysis['tags'] as $tag) {
          $wireframe_data['elements'][] = [
            'type' => $tag['name'],
            'confidence' => $tag['confidence'],
            'location' => null,
          ];
        }
      }
    }

    // Identify common UI patterns.
    $wireframe_data['sections'] = $this->identifyUISections($wireframe_data);

    return $wireframe_data;
  }

  /**
   * Process text content to extract structured information.
   *
   * @param array $read_results
   *   The OCR read results.
   * @param array &$wireframe_data
   *   Reference to wireframe data to populate.
   */
  protected function processTextContent(array $read_results, array &$wireframe_data) {
    $all_text = [];
    
    foreach ($read_results as $page) {
      foreach ($page['lines'] as $line) {
        $text = $line['text'];
        $all_text[] = [
          'text' => $text,
          'bounding_box' => $line['boundingBox'],
          'confidence' => $line['appearance']['confidence'],
        ];
        
        // Detect title (usually the largest text or contains key words)
        if (strlen($text) > 20 && !$wireframe_data['title']) {
          $wireframe_data['title'] = $text;
        }
        
        // Detect subtitle
        if (strpos(strtolower($text), 'step-by-step') !== FALSE || 
            strpos(strtolower($text), 'guide') !== FALSE) {
          $wireframe_data['subtitle'] = $text;
        }
        
        // Detect date
        if (preg_match('/\b(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\s+\d{1,2},\s+\d{4}\b/i', $text)) {
          $wireframe_data['date'] = $text;
        }
        
        // Detect navigation items
        $nav_keywords = ['home', 'about', 'blog', 'contact', 'privacy', 'terms'];
        foreach ($nav_keywords as $keyword) {
          if (strtolower($text) === $keyword) {
            $wireframe_data['navigation'][] = $text;
            break;
          }
        }
        
        // Detect sections
        if (preg_match('/^SECTION\s+\d+:/i', $text)) {
          $section_name = trim(preg_replace('/^SECTION\s+\d+:\s*/i', '', $text));
          $wireframe_data['sections'][] = [
            'name' => $section_name,
            'type' => 'section',
            'text' => $text,
          ];
        }
        
        // Detect lists
        if (preg_match('/^[\d\.]+\.\s+/', $text) || preg_match('/^[•\-]\s+/', $text)) {
          $wireframe_data['lists'][] = [
            'text' => $text,
            'type' => 'ordered',
            'bounding_box' => $line['boundingBox'],
          ];
        }
      }
    }
    
    $wireframe_data['text_content'] = $all_text;
  }

  /**
   * Identify UI sections based on wireframe analysis.
   *
   * @param array $wireframe_data
   *   The wireframe data.
   *
   * @return array
   *   Identified UI sections.
   */
  protected function identifyUISections(array $wireframe_data) {
    $sections = [];

    // Add sections that were already detected in processTextContent
    if (!empty($wireframe_data['sections'])) {
      foreach ($wireframe_data['sections'] as $section) {
        $sections[] = $section;
      }
    }

    // Look for additional UI patterns in text content
    $header_keywords = ['header', 'nav', 'menu', 'logo', 'brand'];
    $footer_keywords = ['footer', 'copyright', 'contact', 'social'];
    $content_keywords = ['content', 'main', 'body', 'article'];

    foreach ($wireframe_data['text_content'] as $text_item) {
      $text_lower = strtolower($text_item['text']);
      
      // Skip if already categorized
      if (in_array($text_item['text'], $wireframe_data['navigation']) ||
          in_array($text_item['text'], [$wireframe_data['title'], $wireframe_data['subtitle'], $wireframe_data['date']])) {
        continue;
      }
      
      foreach ($header_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== FALSE) {
          $sections[] = [
            'name' => 'Header',
            'type' => 'header',
            'text' => $text_item['text'],
          ];
          break 2;
        }
      }

      foreach ($footer_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== FALSE) {
          $sections[] = [
            'name' => 'Footer',
            'type' => 'footer',
            'text' => $text_item['text'],
          ];
          break 2;
        }
      }

      foreach ($content_keywords as $keyword) {
        if (strpos($text_lower, $keyword) !== FALSE) {
          $sections[] = [
            'name' => 'Content',
            'type' => 'content',
            'text' => $text_item['text'],
          ];
          break 2;
        }
      }
    }

    return $sections;
  }

  /**
   * Create Drupal content based on wireframe data.
   *
   * @param array $wireframe_data
   *   The processed wireframe data.
   * @param array $options
   *   Additional options for content creation.
   *
   * @return array
   *   Created content entities.
   */
  protected function createDrupalContent(array $wireframe_data, array $options = []) {
    $created_content = [];

    // Create a basic page node.
    $node_data = [
      'type' => 'page',
      'title' => $wireframe_data['title'] ?: 'Wireframe Generated Page',
      'body' => [
        'value' => $this->generatePageContent($wireframe_data),
        'format' => 'full_html',
      ],
      'status' => 1,
    ];

    // Add custom fields if they exist.
    if (!empty($wireframe_data['description'])) {
      $node_data['field_description'] = [
        'value' => $wireframe_data['description'],
        'format' => 'plain_text',
      ];
    }

    try {
      $node = Node::create($node_data);
      $node->save();
      $created_content['page'] = $node;

      // Create additional content types based on wireframe analysis.
      if (!empty($wireframe_data['sections'])) {
        $this->createAdditionalContent($wireframe_data, $created_content);
      }

      $this->loggerFactory->get('wireframe_converter')->info('Created Drupal content from wireframe: @title', ['@title' => $node_data['title']]);
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('wireframe_converter')->error('Error creating Drupal content: @error', ['@error' => $e->getMessage()]);
      throw $e;
    }

    return $created_content;
  }

  /**
   * Generate page content from wireframe data.
   *
   * @param array $wireframe_data
   *   The wireframe data.
   *
   * @return string
   *   Generated HTML content.
   */
  protected function generatePageContent(array $wireframe_data) {
    $content = '';

    // Add header with navigation
    if (!empty($wireframe_data['navigation'])) {
      $content .= '<header class="wireframe-header">';
      $content .= '<div class="header-container">';
      
      // Logo placeholder
      $content .= '<div class="logo">LOGO</div>';
      
      // Navigation menu
      if (!empty($wireframe_data['navigation'])) {
        $content .= '<nav class="main-navigation">';
        foreach ($wireframe_data['navigation'] as $nav_item) {
          $content .= '<a href="#" class="nav-link">' . htmlspecialchars($nav_item) . '</a>';
        }
        $content .= '</nav>';
      }
      
      $content .= '</div>';
      $content .= '</header>';
    }

    // Add main content area
    $content .= '<main class="wireframe-content">';
    
    // Featured image placeholder
    $content .= '<div class="featured-image">';
    $content .= '<div class="image-placeholder">';
    $content .= '<svg width="100%" height="200" viewBox="0 0 400 200">';
    $content .= '<rect width="100%" height="100%" fill="none" stroke="#ccc" stroke-width="1"/>';
    $content .= '<circle cx="100" cy="50" r="20" fill="none" stroke="#ccc" stroke-width="1"/>';
    $content .= '<path d="M50 150 L150 100 L250 150" fill="none" stroke="#ccc" stroke-width="1"/>';
    $content .= '</svg>';
    $content .= '</div>';
    $content .= '</div>';
    
    // Article title
    if (!empty($wireframe_data['title'])) {
      $content .= '<h1 class="article-title">' . htmlspecialchars($wireframe_data['title']) . '</h1>';
    }
    
    // Subtitle
    if (!empty($wireframe_data['subtitle'])) {
      $content .= '<p class="article-subtitle">' . htmlspecialchars($wireframe_data['subtitle']) . '</p>';
    }
    
    // Date
    if (!empty($wireframe_data['date'])) {
      $content .= '<p class="article-date">' . htmlspecialchars($wireframe_data['date']) . '</p>';
    }
    
    // Introduction section
    if (!empty($wireframe_data['description'])) {
      $content .= '<div class="introduction-section">';
      $content .= '<div class="intro-container">';
      $content .= '<div class="intro-image">';
      $content .= '<svg width="80" height="80" viewBox="0 0 80 80">';
      $content .= '<rect width="100%" height="100%" fill="none" stroke="#ccc" stroke-width="1"/>';
      $content .= '<circle cx="40" cy="20" r="8" fill="none" stroke="#ccc" stroke-width="1"/>';
      $content .= '<path d="M20 60 L40 40 L60 60" fill="none" stroke="#ccc" stroke-width="1"/>';
      $content .= '</svg>';
      $content .= '</div>';
      $content .= '<div class="intro-text">';
      $content .= '<p>' . htmlspecialchars($wireframe_data['description']) . '</p>';
      $content .= '</div>';
      $content .= '</div>';
      $content .= '</div>';
    }
    
    // Process sections
    if (!empty($wireframe_data['sections'])) {
      foreach ($wireframe_data['sections'] as $section) {
        $content .= '<section class="content-section">';
        $content .= '<h2 class="section-title">' . htmlspecialchars($section['name']) . '</h2>';
        
        // Add lists that belong to this section
        if (!empty($wireframe_data['lists'])) {
          $content .= '<ul class="section-list">';
          foreach ($wireframe_data['lists'] as $list_item) {
            $content .= '<li>' . htmlspecialchars($list_item['text']) . '</li>';
          }
          $content .= '</ul>';
        }
        
        $content .= '</section>';
      }
    }
    
    // Add any remaining text content
    if (!empty($wireframe_data['text_content'])) {
      foreach ($wireframe_data['text_content'] as $text_item) {
        // Skip items already processed
        if (in_array($text_item['text'], [$wireframe_data['title'], $wireframe_data['subtitle'], $wireframe_data['date']])) {
          continue;
        }
        if (in_array($text_item['text'], $wireframe_data['navigation'])) {
          continue;
        }
        if (preg_match('/^SECTION\s+\d+:/i', $text_item['text'])) {
          continue;
        }
        if (preg_match('/^[\d\.]+\.\s+/', $text_item['text']) || preg_match('/^[•\-]\s+/', $text_item['text'])) {
          continue;
        }
        
        $content .= '<p class="content-text">' . htmlspecialchars($text_item['text']) . '</p>';
      }
    }
    
    $content .= '</main>';

    // Add footer
    if (!empty($wireframe_data['navigation'])) {
      $content .= '<footer class="wireframe-footer">';
      $content .= '<div class="footer-navigation">';
      foreach ($wireframe_data['navigation'] as $nav_item) {
        $content .= '<a href="#" class="footer-link">' . htmlspecialchars($nav_item) . '</a>';
      }
      $content .= '</div>';
      $content .= '</footer>';
    }

    return $content;
  }

  /**
   * Create additional content based on wireframe analysis.
   *
   * @param array $wireframe_data
   *   The wireframe data.
   * @param array $created_content
   *   Reference to created content array.
   */
  protected function createAdditionalContent(array $wireframe_data, array &$created_content) {
    // Create menu items if navigation is detected.
    if (!empty($wireframe_data['navigation'])) {
      $this->createMenuItems($wireframe_data['navigation'], $created_content);
    }

    // Create blocks for identified sections.
    $this->createBlocks($wireframe_data, $created_content);
  }

  /**
   * Create menu items from navigation elements.
   *
   * @param array $header_items
   *   Header navigation items.
   * @param array $created_content
   *   Reference to created content array.
   */
  protected function createMenuItems(array $header_items, array &$created_content) {
    // This would create menu items based on detected navigation elements.
    // Implementation depends on your specific menu structure.
    $this->loggerFactory->get('wireframe_converter')->info('Navigation elements detected in wireframe.');
  }

  /**
   * Create blocks from wireframe sections.
   *
   * @param array $wireframe_data
   *   The wireframe data.
   * @param array $created_content
   *   Reference to created content array.
   */
  protected function createBlocks(array $wireframe_data, array &$created_content) {
    // This would create custom blocks based on wireframe sections.
    // Implementation depends on your block structure.
    $this->loggerFactory->get('wireframe_converter')->info('Creating blocks from wireframe sections.');
  }

} 