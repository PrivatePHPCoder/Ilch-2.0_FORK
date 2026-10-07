<?php

/**
 * @copyright Ilch 2
 * @package ilch
 */

namespace Modules\Fitness\Service;

/**
 * Helpers for images and videos of exercises and programs.
 */
class Media
{
    /**
     * Returns the URL of an image. Relative paths (media library) get the base URL.
     *
     * @param string $path relative path or http(s) URL
     * @param string $baseUrl base URL of the site without trailing slash
     * @return string empty if no image is set
     */
    public static function imageUrl(string $path, string $baseUrl): string
    {
        if ($path === '') {
            return '';
        }

        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Turns a YouTube or Vimeo link into an embed URL.
     *
     * The embed URL is built from the checked video id only, never from the given link.
     * YouTube videos use the privacy-enhanced domain youtube-nocookie.com.
     *
     * @param string $url
     * @return array{provider: string, url: string}|null null for unknown links
     */
    public static function videoEmbed(string $url): ?array
    {
        $parts = parse_url(trim($url));
        if (!$parts || empty($parts['host']) || !in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return null;
        }

        $host = strtolower(preg_replace('/^(www\.|m\.)/', '', $parts['host']));
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        $youtubeId = null;
        if ($host === 'youtube.com' || $host === 'youtube-nocookie.com') {
            if ($path === '/watch' && isset($query['v'])) {
                $youtubeId = (string)$query['v'];
            } elseif (preg_match('~^/(?:embed|shorts|live)/([^/?#]+)~', $path, $match)) {
                $youtubeId = $match[1];
            }
        } elseif ($host === 'youtu.be') {
            $youtubeId = ltrim($path, '/');
        }

        if ($youtubeId !== null) {
            return preg_match('/^[A-Za-z0-9_-]{11}$/', $youtubeId)
                ? ['provider' => 'YouTube', 'url' => 'https://www.youtube-nocookie.com/embed/' . $youtubeId]
                : null;
        }

        if (($host === 'vimeo.com' || $host === 'player.vimeo.com') && preg_match('~/(\d{6,12})(?:$|[/?#])~', $path . '/', $match)) {
            return ['provider' => 'Vimeo', 'url' => 'https://player.vimeo.com/video/' . $match[1] . '?dnt=1'];
        }

        return null;
    }
}
