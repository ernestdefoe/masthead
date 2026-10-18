<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Tests\unit;

use ErnestDefoe\Masthead\Portal\Images;
use ErnestDefoe\Masthead\Presets;
use ErnestDefoe\Masthead\Settings;
use Flarum\Settings\OverrideSettingsRepository;
use Flarum\Settings\UninstalledSettingsRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private function look(array $values): array
    {
        return (new Settings(new OverrideSettingsRepository(new UninstalledSettingsRepository(), $values)))->look();
    }

    #[Test]
    public function the_preset_supplies_everything_by_default()
    {
        $this->assertSame(Presets::ALL['gameday'], $this->look([]));
        $this->assertSame(Presets::ALL['royal'], $this->look([Settings::PRESET => 'royal']));
        $this->assertSame(Presets::ALL['gameday'], $this->look([Settings::PRESET => 'nonsense']));
    }

    #[Test]
    public function overrides_lay_over_the_preset()
    {
        $look = $this->look([Settings::PRESET => 'royal', Settings::ACCENT => '#ABCDEF', Settings::BAR => '#123456']);

        $this->assertSame('#abcdef', $look['accent']);
        $this->assertSame('#123456', $look['bar']);
        $this->assertSame('color-mix(in srgb, #123456 78%, #000)', $look['strip']);
        $this->assertSame('overlay', $look['hero']);

        $this->assertSame('var(--primary-color)', $this->look([Settings::ACCENT => 'primary'])['accent']);
    }

    #[Test]
    public function nothing_but_a_hex_colour_reaches_the_stylesheet()
    {
        // Interpolated as `@masthead-accent: <value>;` — a value that closed
        // the declaration could append LESS of its own.
        foreach (['red;} body{display:none', '#fff; @import "x"', 'url(x)', '#12345', ''] as $bad) {
            $this->assertSame(Presets::ALL['gameday']['accent'], $this->look([Settings::ACCENT => $bad])['accent'], $bad);
        }
    }

    #[Test]
    public function only_safe_addresses_survive()
    {
        $this->assertSame('/p/1', Settings::safeUrl('/p/1'));
        $this->assertSame('mailto:a@b.c', Settings::safeUrl('mailto:a@b.c'));
        $this->assertNull(Settings::safeUrl('javascript:alert(1)'));
        $this->assertNull(Settings::safeUrl('//evil.example'));
        // Browsers read a backslash after the leading slash as a second slash:
        // "/\\evil.example" is a protocol-relative link off the forum.
        $this->assertNull(Settings::safeUrl('/\\evil.example'));
        $this->assertNull(Settings::safeUrl('data:text/html,x'));

        $this->assertSame('https://x.test/a.png', Images::safe('https://x.test/a.png'));
        $this->assertNull(Images::safe('https://x.test/a.png") , url(evil'));
        $this->assertNull(Images::safe('javascript:alert(1)'));
    }
}
