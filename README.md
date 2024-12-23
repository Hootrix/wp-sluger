# WP Sluger

A WordPress plugin that automates the generation of SEO-friendly URL slugs using DeepLX and ChatGPT API.

## Features

- Automatically generate SEO-friendly slugs for posts and pages
- Support for both DeepLX and ChatGPT API
- Multiple language styles:
  - English Translation
  - Chinese Pinyin
  - Romanization (For non-Latin scripts)
  - Keep Original (just sanitize)
  - Custom Style with custom prompts
- Real-time API connection testing
- Support for custom ChatGPT models
- Detailed error logging for troubleshooting


## Slug Update Logic
- The plugin only triggers slug generation when the post/page title is changed
- If you modify other content without changing the title, the slug remains unchanged
- This prevents unnecessary API calls and maintains URL stability


## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin" and choose the downloaded zip file
4. Click "Install Now" and then "Activate"

## Configuration

1. Go to Settings > WP Sluger
2. Choose your preferred translation service:
   - **DeepLX**: Free and self-hosted translation service
   - **ChatGPT**: OpenAI's powerful language model

3. Configure the API settings:
   - For DeepLX:
     - Enter your server URL (e.g., http://localhost:1188/translate, http://localhost:1188/translate?token=xxxxxxxxx)
     - Test the connection
   
   - For ChatGPT:
     - Enter your API endpoint (default: https://api.openai.com/v1/chat/completions)
     - Enter your API key
     - Choose model (GPT-3.5 Turbo, GPT-4, GPT-4 Turbo, or custom)
     - Test the connection

4. Choose your preferred language style:
   - **English Translation**: Convert any language to English
   - **Chinese Pinyin**: Convert Chinese characters to Pinyin
   - **Romanization**: Convert non-Latin scripts to Latin alphabet
   - **Keep Original**: Just sanitize the title without translation
   - **Custom Style**: Use your own prompt template

5. For Custom Style:
   - Write your own prompt template
   - Use {title} as a placeholder for the post title
   - Example prompts:
     ```
     Convert {title} to Pinyin and create a URL slug
     Translate {title} to English, keep it concise and create a URL slug
     Create a URL slug from {title} using Japanese romaji
     ```

## License

GPL v2 or later

## Credits

- DeepLX: https://github.com/OwO-Network/DeepLX
- OpenAI: https://openai.com/
