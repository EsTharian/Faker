<?php

namespace Faker\Provider;

/**
 * Depends on image generation from https://picsum.photos/
 * So, there is no category this time.
 */
class Image extends Base
{
    /**
     * Generate the URL that will return a random image
     *
     * Set randomize to false to remove the random GET parameter at the end of the url.
     *
     * @example 'https://picsum.photos/640/480.jpg'
     *
     * @param integer $width
     * @param integer $height
     * @param bool $randomize
     * @param string|null $word
     * @param bool $gray
     * @param integer|null $blur You can adjust the amount of blur by providing a number between 1 and 10.
     * @param string|null $ext If you need a file ending, you can declare as `.jpg` or to get an image in the WebP format, you can declare as `.webp`.
     *
     * @return string
     */
    public static function imageUrl($width = 640, $height = 480, $randomize = true, $word = null, $gray = false, $blur = null, $ext=null)
    {
        $now = round((mt_rand() / mt_getrandmax() + microtime(true)) * 1000);

        $baseUrl = "https://picsum.photos/";
        $url = "{$width}/{$height}{$ext}";

        $is_arg = false;

        if ($gray) {
            $url .= "?grayscale";
            $is_arg = true;
        }

        if ($blur) {
            $url .= $is_arg ? "&blur={$blur}" : "?blur={$blur}";
            $is_arg = true;
        }

        if ($randomize) {
            $url .= $is_arg ? "&random={$now}" : "?random={$now}";
        }

        return $baseUrl . $url;
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.jpg'
     */
    public static function image($dir = null, $width = 640, $height = 480, $fullPath = true, $randomize = true, $word = null, $gray = false, $blur = null, $ext=null)
    {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name . $ext ?? '.jpg';
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height, $randomize, $word, $gray, $blur = null, $ext=null);

        // save file
        if (function_exists('curl_exec')) {
            // use cURL
            $fp = fopen($filepath, 'w');
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_FILE, $fp);
            $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
            fclose($fp);
            curl_close($ch);

            if (!$success) {
                unlink($filepath);

                // could not contact the distant URL or HTTP error - fail silently.
                return false;
            }
        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }
}
