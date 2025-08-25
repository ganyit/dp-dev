# Wireframe Converter Module

A Drupal module that converts wireframe images to Drupal CMS pages using Azure Computer Vision API.

## Features

- Upload wireframe images (PNG, JPG, JPEG)
- Analyze wireframes using Azure Computer Vision API
- Extract text and identify UI elements from wireframes
- Automatically generate Drupal content pages
- Create custom blocks from wireframe sections
- AJAX-powered conversion process
- Configurable settings for Azure API credentials

## Requirements

- Drupal 8/9/10
- Azure Computer Vision API subscription
- PHP with cURL extension
- File upload permissions

## Installation

1. Copy the `wireframe_converter` module to your Drupal installation:
   ```
   cp -r wireframe_converter web/modules/custom/
   ```

2. Enable the module via Drush:
   ```bash
   drush en wireframe_converter -y
   ```

3. Or enable it through the Drupal admin interface:
   - Go to Extend (`/admin/modules`)
   - Find "Wireframe Converter" and check the box
   - Click "Install"

## Configuration

### Azure Computer Vision API Setup

1. Create an Azure Computer Vision resource in the Azure portal
2. Get your endpoint URL and API key
3. Configure the module settings:
   - Go to Configuration > Development > Wireframe Converter (`/admin/config/wireframe-converter`)
   - Enter your Azure endpoint URL (e.g., `https://your-resource.cognitiveservices.azure.com/`)
   - Enter your Azure API key
   - Configure additional settings as needed
   - Save the configuration

### Settings Options

- **Azure Computer Vision Endpoint**: Your Azure Computer Vision API endpoint URL
- **Azure Computer Vision API Key**: Your Azure Computer Vision API key
- **Default Content Type**: The content type to use for generated pages
- **Auto-publish content**: Automatically publish generated content
- **Create blocks from wireframe sections**: Create custom blocks for identified sections
- **Create menu items from navigation**: Create menu items for detected navigation

## Usage

### Converting Wireframes

1. Navigate to Content > Convert Wireframe (`/admin/content/wireframe-converter`)
2. Click "Upload Wireframe" or go directly to `/admin/content/wireframe-converter/upload`
3. Upload a wireframe image file (PNG, JPG, JPEG, max 10MB)
4. Configure conversion options (optional)
5. Click "Convert Wireframe"
6. Wait for the Azure Computer Vision API to process the image
7. Review the generated content and wireframe analysis

### What the Module Does

1. **Image Analysis**: Uses Azure Computer Vision API to analyze the wireframe image
2. **Text Extraction**: Extracts text content using OCR
3. **Element Detection**: Identifies UI elements and objects
4. **Section Identification**: Identifies common UI patterns (header, content, footer)
5. **Content Generation**: Creates Drupal content based on the analysis
6. **Block Creation**: Optionally creates custom blocks for identified sections

### Generated Content

The module creates:
- **Pages**: Basic content pages with structured HTML
- **Blocks**: Custom blocks for identified wireframe sections
- **Menus**: Menu items for detected navigation (optional)

## API Integration

### Azure Computer Vision API Features Used

- **Image Analysis**: Detects objects, text, colors, and descriptions
- **OCR (Read API)**: Extracts text from images
- **Object Detection**: Identifies UI elements and components
- **Tag Analysis**: Categorizes image content

### API Endpoints

- **Analyze**: `/vision/v3.2/analyze` - General image analysis
- **Read**: `/vision/v3.2/read/analyze` - Text extraction

## File Structure

```
wireframe_converter/
├── wireframe_converter.info.yml
├── wireframe_converter.module
├── wireframe_converter.routing.yml
├── wireframe_converter.links.menu.yml
├── wireframe_converter.services.yml
├── wireframe_converter.libraries.yml
├── wireframe_converter.install
├── README.md
├── src/
│   ├── Controller/
│   │   └── WireframeConverterController.php
│   ├── Form/
│   │   ├── WireframeConverterSettingsForm.php
│   │   └── WireframeUploadForm.php
│   └── Service/
│       ├── AzureComputerVisionService.php
│       └── WireframeConverterService.php
├── css/
│   └── wireframe-converter.css
└── js/
    └── wireframe-converter.js
```

## Troubleshooting

### Common Issues

1. **Azure API Credentials Not Configured**
   - Ensure you've configured the Azure endpoint and API key in the settings
   - Check that your Azure subscription is active

2. **File Upload Issues**
   - Verify file permissions on the `public://wireframes/` directory
   - Check that the file is a supported format (PNG, JPG, JPEG)
   - Ensure file size is under 10MB

3. **Conversion Failures**
   - Check the Drupal logs for detailed error messages
   - Verify Azure API quota and limits
   - Ensure the wireframe image is clear and readable

4. **AJAX Errors**
   - Check browser console for JavaScript errors
   - Verify that the AJAX endpoint is accessible
   - Check Drupal permissions for content creation

### Debugging

Enable Drupal logging to see detailed error messages:
```bash
drush config:set system.logging error_level verbose
```

Check the logs:
```bash
drush watchdog:show --type=wireframe_converter
```

## Development

### Adding Custom Content Types

To support additional content types, modify the `WireframeConverterService::createDrupalContent()` method.

### Extending Azure Analysis

To add more Azure Computer Vision features, extend the `AzureComputerVisionService` class.

### Custom UI Patterns

To recognize additional UI patterns, modify the `identifyUISections()` method in `WireframeConverterService`.

## Security Considerations

- Azure API keys are stored in Drupal configuration (encrypted)
- File uploads are validated for type and size
- User permissions are enforced for all operations
- AJAX endpoints require proper authentication

## License

This module is provided as-is for educational and development purposes.

## Support

For issues and feature requests, please create an issue in the project repository.

## Changelog

### Version 1.0.0
- Initial release
- Azure Computer Vision API integration
- Wireframe to Drupal content conversion
- AJAX-powered upload and processing
- Configurable settings
- Custom blocks and menu creation 