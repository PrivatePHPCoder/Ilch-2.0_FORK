<?php

/**
 * @copyright Ilch 2
 * @package ilch_phpunit
 */

namespace Modules\Fitness\Service;

use PHPUnit\Framework\TestCase;

class MediaTest extends TestCase
{
    public function testImageUrl()
    {
        self::assertSame('', Media::imageUrl('', 'http://example.org'));
        self::assertSame('http://example.org/application/modules/media/static/upload/a.jpg', Media::imageUrl('application/modules/media/static/upload/a.jpg', 'http://example.org/'));
        self::assertSame('https://cdn.example.org/a.jpg', Media::imageUrl('https://cdn.example.org/a.jpg', 'http://example.org'));
    }

    /**
     * @dataProvider youtubeLinks
     */
    public function testYoutubeLinksUseTheNoCookieDomain(string $link)
    {
        self::assertSame(
            ['provider' => 'YouTube', 'url' => 'https://www.youtube-nocookie.com/embed/aclHkVaku9U'],
            Media::videoEmbed($link)
        );
    }

    public static function youtubeLinks(): array
    {
        return [
            'watch' => ['https://www.youtube.com/watch?v=aclHkVaku9U'],
            'watch with more parameters' => ['https://www.youtube.com/watch?v=aclHkVaku9U&t=42s'],
            'mobile' => ['https://m.youtube.com/watch?v=aclHkVaku9U'],
            'short link' => ['https://youtu.be/aclHkVaku9U'],
            'shorts' => ['https://www.youtube.com/shorts/aclHkVaku9U'],
            'embed' => ['https://www.youtube.com/embed/aclHkVaku9U'],
        ];
    }

    public function testVimeoLink()
    {
        self::assertSame(
            ['provider' => 'Vimeo', 'url' => 'https://player.vimeo.com/video/76979871?dnt=1'],
            Media::videoEmbed('https://vimeo.com/76979871')
        );
    }

    /**
     * @dataProvider invalidLinks
     */
    public function testInvalidOrForeignLinksAreNotEmbedded(string $link)
    {
        self::assertNull(Media::videoEmbed($link));
    }

    public static function invalidLinks(): array
    {
        return [
            'empty' => [''],
            'other site' => ['https://example.org/watch?v=aclHkVaku9U'],
            'fake youtube host' => ['https://youtube.com.example.org/watch?v=aclHkVaku9U'],
            'javascript' => ['javascript:alert(1)'],
            'id with quote' => ['https://www.youtube.com/watch?v=abc"onload=x'],
            'id too short' => ['https://youtu.be/abc'],
            'vimeo without id' => ['https://vimeo.com/channels/staffpicks'],
        ];
    }
}
