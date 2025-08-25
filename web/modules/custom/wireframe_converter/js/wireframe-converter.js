/**
 * Wireframe Converter JavaScript
 */
(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.wireframeConverter = {
    attach: function (context, settings) {
      // Initialize wireframe converter functionality.
      this.initializeWireframeConverter(context);
      
      // Check for conversion triggers in newly added content
      $(context).find('[data-trigger-conversion="true"]').once('wireframe-conversion-trigger').each(function() {
        var $element = $(this);
        var fileId = $element.data('file-id');
        console.log('Conversion trigger detected for file ID:', fileId);
        
        if (fileId) {
          // Use a small delay to ensure the DOM is fully updated
          setTimeout(function() {
            Drupal.behaviors.wireframeConverter.processWireframeConversion(fileId);
          }, 100);
        }
      });
    },

    initializeWireframeConverter: function (context) {
      // Add global function for AJAX processing.
      window.processWireframeConversion = this.processWireframeConversion.bind(this);
    },

    processWireframeConversion: function (fileId) {
      // Show processing message.
      $('#wireframe-conversion-results').html(
        '<div class="processing">Processing wireframe with Azure Computer Vision API...</div>'
      );

      // Make AJAX request to process the wireframe.
      console.log('Processing wireframe with Azure Computer Vision API...');
      console.log('File ID:', fileId);
      
      $.ajax({
        url: Drupal.url('wireframe-converter/process'),
        method: 'POST',
        data: {
          file_id: fileId
        },
        dataType: 'json',
        success: function (response) {
          console.log('AJAX response received:', response);
          if (response.success) {
            Drupal.behaviors.wireframeConverter.displayConversionResults(response);
          } else {
            Drupal.behaviors.wireframeConverter.displayError(response.message);
          }
        },
        error: function (xhr, status, error) {
          console.error('AJAX error:', status, error);
          console.error('Response text:', xhr.responseText);
          Drupal.behaviors.wireframeConverter.displayError('Error processing wireframe: ' + error);
        }
      });
    },

    displayConversionResults: function (response) {
      var resultsHtml = '<div class="conversion-results">';
      resultsHtml += '<h3>Wireframe Conversion Successful</h3>';
      resultsHtml += '<p>' + response.message + '</p>';

      if (response.content && Object.keys(response.content).length > 0) {
        resultsHtml += '<h4>Created Content:</h4>';
        resultsHtml += '<div class="content-list">';
        
        for (var contentType in response.content) {
          var content = response.content[contentType];
          resultsHtml += '<div class="content-item">';
          resultsHtml += '<strong>' + contentType.charAt(0).toUpperCase() + contentType.slice(1) + ':</strong> ';
          resultsHtml += '<a href="' + content.url + '" target="_blank">' + content.title + '</a>';
          resultsHtml += '</div>';
        }
        
        resultsHtml += '</div>';
      }

      // Display wireframe analysis data if available.
      if (response.wireframe_data) {
        resultsHtml += '<h4>Wireframe Analysis:</h4>';
        resultsHtml += '<div class="analysis-data">';
        
        if (response.wireframe_data.title) {
          resultsHtml += '<p><strong>Detected Title:</strong> ' + response.wireframe_data.title + '</p>';
        }
        
        if (response.wireframe_data.description) {
          resultsHtml += '<p><strong>Description:</strong> ' + response.wireframe_data.description + '</p>';
        }
        
        if (response.wireframe_data.elements && response.wireframe_data.elements.length > 0) {
          resultsHtml += '<p><strong>Detected Elements:</strong></p>';
          resultsHtml += '<ul>';
          response.wireframe_data.elements.forEach(function(element) {
            resultsHtml += '<li>' + element.type + ' (confidence: ' + Math.round(element.confidence * 100) + '%)</li>';
          });
          resultsHtml += '</ul>';
        }
        
        if (response.wireframe_data.text_content && response.wireframe_data.text_content.length > 0) {
          resultsHtml += '<p><strong>Extracted Text:</strong></p>';
          resultsHtml += '<ul>';
          response.wireframe_data.text_content.forEach(function(textItem) {
            resultsHtml += '<li>' + textItem.text + '</li>';
          });
          resultsHtml += '</ul>';
        }
        
        resultsHtml += '</div>';
      }

      resultsHtml += '</div>';
      
      $('#wireframe-conversion-results').html(resultsHtml);
    },

    displayError: function (message) {
      var errorHtml = '<div class="messages messages--error">';
      errorHtml += '<div class="message">' + message + '</div>';
      errorHtml += '</div>';
      
      $('#wireframe-conversion-results').html(errorHtml);
    }
  };

  // Add AJAX endpoint for processing wireframes.
  Drupal.AjaxCommands.prototype.processWireframeConversion = function (ajax, response, status) {
    if (response.file_id) {
      console.log('Custom AJAX command triggered with file ID:', response.file_id);
      Drupal.behaviors.wireframeConverter.processWireframeConversion(response.file_id);
    }
  };

  // Add a custom AJAX command for triggering wireframe conversion
  Drupal.AjaxCommands.prototype.triggerWireframeConversion = function (ajax, response, status) {
    if (response.file_id) {
      console.log('Trigger wireframe conversion command with file ID:', response.file_id);
      // Use setTimeout to ensure the DOM is ready
      setTimeout(function() {
        Drupal.behaviors.wireframeConverter.processWireframeConversion(response.file_id);
      }, 100);
    }
  };

})(jQuery, Drupal); 