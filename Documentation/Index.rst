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
