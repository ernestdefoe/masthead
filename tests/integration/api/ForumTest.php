<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\integration\api;

use ErnestDefoe\Masthead\HomeRoute;
use ErnestDefoe\Masthead\Tests\integration\MastheadTestCase;
use Flarum\Extension\ExtensionManager;
use Flarum\Settings\SettingsRepositoryInterface;
use PHPUnit\Framework\Attributes\Test;

class ForumTest extends MastheadTestCase
{
    private function forum(): array
    {
        [$status, $body] = $this->get('/api');
        $this->assertSame(200, $status);

        return $body['data']['attributes'];
    }

    #[Test]
    public function the_forum_payload_carries_the_masthead()
    {
        $this->setting('ernestdefoe-masthead.footer_links', json_encode([
            ['label' => 'Rules', 'url' => '/p/1-rules'],
            ['label' => 'Bad', 'url' => 'javascript:alert(1)'],
            ['label' => 'Protocol-relative', 'url' => '//evil.example'],
            ['label' => 'Discord', 'url' => 'https://discord.gg/x'],
            ['label' => '', 'url' => '/nameless'],
        ]));
        $this->setting('ernestdefoe-masthead.preset', 'sunday');

        $forum = $this->forum();

        $this->assertSame(1, $forum['mastheadOnline'], 'Only user 3 was seen in the last five minutes');
        $this->assertSame('split', $forum['mastheadHero'], 'Sunday\'s layout');
        $this->assertSame(
            [['label' => 'Rules', 'url' => '/p/1-rules'], ['label' => 'Discord', 'url' => 'https://discord.gg/x']],
            $forum['mastheadFooterLinks']
        );
        $this->assertSame(12, $forum['mastheadTagCount']);
        $this->assertTrue($forum['mastheadLeadImage']);
    }

    #[Test]
    public function an_override_beats_the_preset_and_off_is_honoured()
    {
        $this->setting('ernestdefoe-masthead.preset', 'sunday');
        $this->setting('ernestdefoe-masthead.hero_style', 'off');

        $this->assertSame('off', $this->forum()['mastheadHero']);
    }

    #[Test]
    public function the_portal_page_renders_with_its_data_preloaded()
    {
        $response = $this->send($this->request('GET', '/portal'));
        $html = (string) $response->getBody();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('"mastheadPortal"', $html);
        $this->assertStringContainsString('Story 1', $html, 'The stories are in the page as links, for crawlers');
        $this->assertStringContainsString('data-masthead-corners="soft"', $html);
    }

    #[Test]
    public function enabling_makes_the_portal_the_home_page_and_disabling_hands_it_back()
    {
        $this->app();
        $settings = $this->app()->getContainer()->make(SettingsRepositoryInterface::class);
        $extension = $this->app()->getContainer()->make(ExtensionManager::class)->getExtension('ernestdefoe-masthead');
        $home = new HomeRoute();

        $settings->set('default_route', '/all');
        $home->onEnable($this->app()->getContainer(), $extension);
        $this->assertSame('/portal', $settings->get('default_route'));

        $home->onDisable($this->app()->getContainer(), $extension);
        $this->assertSame('/all', $settings->get('default_route'));

        // A home page the admin chose themselves is left alone both ways.
        $settings->set('default_route', '/tags');
        $home->onEnable($this->app()->getContainer(), $extension);
        $home->onDisable($this->app()->getContainer(), $extension);
        $this->assertSame('/tags', $settings->get('default_route'));
    }
}
