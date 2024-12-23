jQuery(document).ready(function($) {
    // Show/hide API fields based on selected service
    function toggleApiFields() {
        var service = $('#translation-service').val();
        
        if (service === 'deeplx') {
            $('.hhtjim-wp-sluger-chatgpt-field').hide();
            $('.hhtjim-wp-sluger-deeplx-field').show();
        } else {
            $('.hhtjim-wp-sluger-deeplx-field').hide();
            $('.hhtjim-wp-sluger-chatgpt-field').show();
        }
    }

    // Show/hide custom model input based on model selection
    function toggleCustomModelInput() {
        var selectedModel = $('#chatgpt-model-select').val();
        if (selectedModel === 'custom') {
            $('#custom-model-input').show();
        } else {
            $('#custom-model-input').hide();
        }
    }

    // Show/hide custom prompt input based on language style selection
    function toggleCustomPromptInput() {
        var selectedStyle = $('#language-style-select').val();
        if (selectedStyle === 'custom') {
            $('#custom-prompt-container').show();
        } else {
            $('#custom-prompt-container').hide();
        }
    }

    // Initial toggles
    toggleApiFields();
    toggleCustomModelInput();
    toggleCustomPromptInput();

    // Toggle on change
    $('#translation-service').on('change', toggleApiFields);
    $('#chatgpt-model-select').on('change', toggleCustomModelInput);
    $('#language-style-select').on('change', toggleCustomPromptInput);

    // Test API connection
    $('.test-api').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $resultDiv = $button.siblings('.api-test-result');
        var service = $button.data('service');
        
        // Add loading state
        $button.addClass('loading');
        $resultDiv.removeClass('success error').hide();

        var data = {
            action: 'hhtjim_wp_sluger_test_api',
            service: service,
            nonce: hhtjimWpSlugerAdmin.nonce
        };

        if (service === 'deeplx') {
            data.deeplx_url = $button.siblings('input[name*="deeplx_url"]').val();
        } else {
            data.chatgpt_url = $('input[name*="chatgpt_url"]').val();
            // 获取选择的模型类型
            const selectedModel = $('#chatgpt-model-select').val();
            // 根据是否是自定义模型来获取正确的值
            if (selectedModel === 'custom') {
                // 如果是自定义模型，从自定义输入框获取值
                const customModel = $('input[name*="chatgpt_custom_model"]').val().trim();
                data.chatgpt_model = customModel || ''; // 如果自定义值为空，使用默认模型
            } else {
                // 如果是预定义模型，直接使用选择的值
                data.chatgpt_model = selectedModel;
            }
            data.chatgpt_key = $button.siblings('input[name*="chatgpt_api_key"]').val();
        }

        $.ajax({
            url: hhtjimWpSlugerAdmin.ajaxurl,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    $resultDiv.addClass('success').text(response.data).show();
                } else {
                    $resultDiv.addClass('error').text(response.data).show();
                }
            },
            error: function(xhr, status, error) {
                $resultDiv.addClass('error').text('Connection failed: ' + error).show();
            },
            complete: function() {
                $button.removeClass('loading');
            }
        });
    });
});
