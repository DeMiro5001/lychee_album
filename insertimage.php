<?php
defined('_JEXEC') or die;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Factory;

class PlgContentLycheeAlbum extends JPlugin
{
    public function onContentPrepare($context, &$article, &$params, $limitstart)
    {
        // Only process content if the context is an article
        if ($context != 'com_content.article') {
            return true;
        }

        // Regular expression to match the {lychee_album} tag with parameters (album_id and server)
        $pattern = '/\{lychee_album\s*(album_id="([^"]+)")?\s*(server="([^"]+)")?\}/';

        // Replace {lychee_album} tag with the actual album content
        $article->text = preg_replace_callback($pattern, function($matches) {
            // Get the album_id from the parameter (if provided)
            $album_id = isset($matches[2]) ? $matches[2] : null;

            // Get the server URL from the parameter (if not provided, return an error)
            $server_url = isset($matches[4]) ? $matches[4] : null;

            if (!$server_url) {
                return '<p>Error: No server URL provided for Lychee album.</p>';
            }

            // Build the API URL
            $api_url = $server_url . '/albums/' . $album_id;

            // Fetch data from Lychee API
            $response = $this->fetchLycheeData($api_url);

            // Check if the response is successful
            if ($response) {
                // Process and embed the album data
                return $this->generateAlbumHtml($response);
            } else {
                // If the API request fails, display an error message
                return '<p>Unable to load Lychee album.</p>';
            }
        }, $article->text);

        return true;
    }

    // Function to fetch data from the Lychee API
    private function fetchLycheeData($url)
    {
        // Use Joomla's HTTP client to fetch the API response
        $http = Factory::getHttp();
        try {
            $response = $http->get($url);
            if ($response->code == 200) {
                // Return the JSON response
                return json_decode($response->body);
            }
        } catch (Exception $e) {
            // Handle error if the request fails
            return false;
        }

        return false;
    }

    // Function to generate HTML from the Lychee album data
    private function generateAlbumHtml($album)
    {
        // Ensure the response is valid and contains album data
        if (!isset($album->title)) {
            return '<p>Invalid album data.</p>';
        }

        $html = '<div class="lychee-album">';
        $html .= '<h3>' . htmlspecialchars($album->title) . '</h3>';
        $html .= '<p>' . htmlspecialchars($album->description) . '</p>';
        
        // Assuming the album contains images as an array of URLs
        if (isset($album->images) && is_array($album->images)) {
            $html .= '<div class="album-images">';
            foreach ($album->images as $image) {
                $html .= '<img src="' . htmlspecialchars($image->url) . '" alt="' . htmlspecialchars($image->title) . '" />';
            }
            $html .= '</div>';
        }

        $html .= '<a href="' . htmlspecialchars($album->url) . '" target="_blank">View Full Album</a>';
        $html .= '</div>';

        return $html;
    }
}
