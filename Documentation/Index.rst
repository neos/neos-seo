Page title
----------

The default `<title>` tag rendering in the `TYPO3.Neos:Page` TypoScript object is a "reverse breadcrumb" of the regular
title field(s). This is done in `head.titleTag.default`.

A new field `titleOverride` is added to `TYPO3.Neos:Document` via the `TYPO3.Neos.Seo:TitleTagMixin`. The new field is
used as the `<title>` tag content if it is filled (see `head.titleTag.content` in `TYPO3.Neos:Page`).

Basic meta tags
---------------

The fields for keywords and description are added to `TYPO3.Neos:Document` via the `TYPO3.Neos.Seo:SoeMetaTagsMixin`

If they are filled in, `<meta>` tags for their contents will be rendered (see `head.metaTitleTag` and
`head.metaDescriptionTag` in `TYPO3.Neos:Page`).

Two checkboxes allow to set the content for the `<meta name="robots">` tag to any combination of the possible values
(follow, nofollow, index, noindex).

Twitter Cards
-------------

The `TYPO3.Neos.Seo:TwitterCardMixin` (added to `TYPO3.Neos:Document` by default) provides a new inspector tab to
configure Twitter Cards on any document. If a twitter card is enabled, the related meta tags will be rendered as needed
and useful.

The `twitter:site` handle can be set by setting the `TYPO3.Neos.Seo.twitterCard.siteHandle` to a twitter handle::

  TYPO3:
    Neos:
      Seo:
        twitterCard:
          siteHandle: '@typo3neos'

Check the documentation on https://dev.twitter.com/cards/overview for more on twitter cards.

Open Graph
----------

The `TYPO3.Neos.Seo:OpenGraphMixin` (added to `TYPO3.Neos:Document` by default) provides a new inspector tab to
configure Open Graph on any document.
The Open Graph protocol enables any web page to become a rich object in a social graph. The essential ones are:

* og:type
* og:title
* og:description
* og:image
* og:url

In general Open Graph tags are just shown if they have given data, because otherwise Facebook for example will extract data for the generated view from the site itself. So fallbacks are not needed. If you are not satisfied with the generated view you should define your own. 
If a Open Graph Type is enabled, the related meta tags will be rendered according to following rules.

* og:title is only rendered if it includes data
* og:description will use as fallback meta:description or show nothing
* og:url the url of the document
* og:image is only rendered if it includes data

For more information please have a look at http://ogp.me/