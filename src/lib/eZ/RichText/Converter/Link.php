<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
declare(strict_types=1);

namespace EzSystems\EzPlatformRichText\eZ\RichText\Converter;

use EzSystems\EzPlatformRichText\eZ\RichText\Converter;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkIterator;
use EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkResolverInterface;
use Psr\Log\LoggerInterface;
use DOMDocument;

class Link implements Converter
{
    /**
     * @deprecated since version 2.5.9, to be removed in 3.0
     *
     * @var \eZ\Publish\API\Repository\LocationService
     */
    protected $locationService;

    /**
     * @deprecated since version 2.5.9, to be removed in 3.0
     *
     * @var \eZ\Publish\API\Repository\ContentService
     */
    protected $contentService;

    /**
     * @deprecated since version 2.5.9, to be removed in 3.0
     *
     * @var \eZ\Publish\Core\MVC\Symfony\Routing\UrlAliasRouter
     */
    protected $urlAliasRouter;

    /**
     * @deprecated since version 2.5.9, to be removed in 3.0
     *
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /** @var \EzSystems\EzPlatformRichText\eZ\RichText\InternalLink\InternalLinkResolverInterface */
    private $internalLinkResolver;

    public function __construct(
        LocationService $locationService,
        ContentService $contentService,
        UrlAliasRouter $urlAliasRouter,
        InternalLinkResolverInterface $internalLinkResolver,
        LoggerInterface $logger = null
    ) {
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->urlAliasRouter = $urlAliasRouter;
        $this->internalLinkResolver = $internalLinkResolver;
        $this->logger = $logger;
    }

    /**
     * Converts internal links (ezcontent:// and ezlocation://) to URLs.
     *
     * @param \DOMDocument $document
     *
     * @return \DOMDocument
     */
    public function convert(DOMDocument $document)
    {
        $document = clone $document;

        /** @var \DOMElement $link */
        foreach (new InternalLinkIterator($document) as $link) {
            $node = $link->getNode();

            $hrefAttributeName = 'xlink:href';
            // For embeds set the resolved href to the separate attribute
            // Original href needs to be preserved in order to generate link parameters
            // This will need to change with introduction of UrlService and removal of URL link
            // resolving in external storage
            if ($node->localName === 'ezlink') {
                $hrefAttributeName = 'href_resolved';
            }

            $node->setAttribute($hrefAttributeName, $this->internalLinkResolver->resolve($link));
        }

        return $document;
    }
}
