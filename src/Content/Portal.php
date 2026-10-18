<?php

/*
 * This file is part of ernestdefoe/masthead.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ErnestDefoe\Masthead\Content;

use ErnestDefoe\Masthead\Portal\Payload;
use Flarum\Frontend\Document;
use Flarum\Http\RequestUtil;
use Flarum\Http\UrlGenerator;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * The portal's server half: its data, in the page.
 *
 * Preloaded so a full page load paints the hero at once instead of waiting
 * for a second request, and rendered as plain links so a search engine (or a
 * reader with scripts off) still finds the front page's stories.
 */
final class Portal
{
    public function __construct(protected Payload $payload, protected UrlGenerator $url)
    {
    }

    public function __invoke(Document $document, Request $request): Document
    {
        $actor = RequestUtil::getActor($request);

        if (! $actor->can('viewForum')) {
            return $document;
        }

        $data = $this->payload->for($actor);
        $document->payload['mastheadPortal'] = $data;

        $items = '';
        foreach ($data['hero'] as $story) {
            $href = $story['kind'] === 'article'
                ? $this->url->to('forum')->base().$story['url']
                : $this->url->to('forum')->route('discussion', ['id' => $story['slug']]);

            $items .= '<li><a href="'.e($href).'">'.e($story['title']).'</a></li>';
        }

        if ($items !== '') {
            $document->content = '<div class="container"><ul>'.$items.'</ul></div>';
        }

        return $document;
    }
}
