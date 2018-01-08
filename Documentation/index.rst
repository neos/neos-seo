Neos SEO |version| Documentation
================================

This documentation covering version |release| has been rendered at: |today|

Installation
------------

Install the package through composer::

  composer require neos/seo

Page title
----------

The default `<title>` tag rendering in the `Neos.Neos:Page` Fusion object is a "reverse breadcrumb" of the regular
title field(s). This is done in `head.titleTag.default`.

A new field `titleOverride` is added to `Neos.Neos:Document` via the `Neos.Seo:TitleTagMixin`. The new field is
used as the `<title>` tag content if it is filled (see `head.titleTag.content` in `Neos.Neos:Page`).

Basic meta tags
---------------

The fields for keywords and description are added to `Neos.Neos:Document` via the `Neos.Seo:SoeMetaTagsMixin`

If they are filled in, `<meta>` tags for their contents will be rendered (see `head.metaTitleTag` and
`head.metaDescriptionTag` in `Neos.Neos:Page`).

Two checkboxes allow to set the content for the `<meta name="robots">` tag to any combination of the possible values `follow`, `nofollow`, `index` and `noindex`.

Twitter Cards
-------------

The `Neos.Seo:TwitterCardMixin` (added to `Neos.Neos:Document` by default) provides a new inspector tab to
configure Twitter Cards on any document. If a Twitter Card is enabled, the related meta tags will be rendered as needed
and useful.

The `twitter:site` handle can be configured with the setting `Neos.Seo.twitterCard.siteHandle` by providing a valid Twitter handle::

  Neos:
    Seo:
      twitterCard:
        siteHandle: '@neoscms'

Check the documentation on https://dev.twitter.com/cards/overview for more on Twitter Cards.

Open Graph
----------

The `Neos.Seo:OpenGraphMixin` (added to `Neos.Neos:Document` by default) provides a new inspector tab to
configure Open Graph on any document.
The Open Graph protocol enables any web page to become a rich object in a social graph. The essential ones are:

* `og:type`
* `og:title`
* `og:description`
* `og:image`
* `og:url`

In general Open Graph tags are just shown if they have given data, because otherwise Facebook for example will extract data for the generated view from the site itself. So fallbacks are not needed. If you are not satisfied with the generated view you should define your own.
If a Open Graph Type is enabled, the related meta tags will be rendered according to following rules.

* `og:title` is only rendered if it includes data
* `og:description` will use `meta:description` as a fallback or show nothing
* `og:url` the URL of the document
* `og:image` is only rendered if it includes data

For more information please have a look at http://ogp.me/.

XML sitemap
-----------

The generation of an XML sitemap to submit to search engines can be enabled as follows:

The change frequency and priority for each sitemap entry are used as specified in the respective fields added
to the SEO tab in the inspector of `Neos.Neos:Document` nodes via the `Neos.Seo:XmlSitemapMixin`. For
priority the default value is 0.5 (neutral) and the change frequency is omitted unless specified.

The necessary route to make the sitemap available is automatically included via `Settings.yaml` and will provide
the sitemap via `your.domain/sitemap.xml`. See Settings on how to disable or change the route.

Alternate Language Tag
------------------------

The `Alternate Language Tag` provides information that the site is also available in other languages. By default the tags
are rendered with the `Neos.Neos:DimensionMenu` and the `language` dimension. Given the Neos Demo Site Package as an
example the rendered tags for the homepage would be.

::

  <link rel="alternate" hreflang="en_US" href="http://neos.dev/"/>
  <link rel="alternate" hreflang="en_UK" href="http://neos.dev/uk"/>

According to the following dimension settings, there would be a lot more tags expected. However only two variants of the
homepage exists, thus only `en_US` and its fallback `en_UK` are rendered.

::

  ContentRepository:
    contentDimensions:
      'language':
        label: 'Language'
        icon: 'icon-language'
        default: 'en_US'
        defaultPreset: 'en_US'
        presets:
          'all': ~
          'en_US':
            label: 'English (US)'
            values: ['en_US']
            uriSegment: 'en'
          'en_UK':
            label: 'English (UK)'
            values: ['en_UK', 'en_US']
            uriSegment: 'uk'
          'de':
            label: 'German'
            values: ['de']
            uriSegment: 'de'
          'fr':
            label: 'French'
            values: ['fr']
            uriSegment: 'fr'
          'nl':
            label: 'Dutch'
            values: ['nl', 'de']
            uriSegment: 'nl'
          'dk':
            label: 'Danish'
            values: ['dk']
            uriSegment: 'dk'
          'lv':
            label: 'Latvian'
            values: ['lv']
            uriSegment: 'lv'

Disabling not needed features
-----------------------------

The package provides a number of mixins to help rendering SEO metadata. By default, they are
enabled in the `Configuration/NodeTypes.yaml` file, along with an inspector tab::

  'Neos.Neos:Document':
    superTypes:
      'Neos.Seo:TitleTagMixin': true
      'Neos.Seo:SeoMetaTagsMixin': true
      'Neos.Seo:TwitterCardMixin': true
      'Neos.Seo:CanonicalLinkMixin': true
      'Neos.Seo:OpenGraphMixin': true
      'Neos.Seo:XmlSitemapMixin': true
    ui:
      inspector:
        tabs:
          seo:
            label: 'Neos.Seo:NodeTypes.Document:tabs.seo'
            position: 30
            icon: 'icon-bullseye'

  'Neos.Neos:Shortcut':
    superTypes:
      'Neos.Seo:TitleTagMixin': false
      'Neos.Seo:SeoMetaTagsMixin': false
      'Neos.Seo:TwitterCardMixin': false
      'Neos.Seo:CanonicalLinkMixin': false
      'Neos.Seo:OpenGraphMixin': false
      'Neos.Seo:XmlSitemapMixin': false

Then to enable rendering of all SEO meta tags, the following code is used::

  prototype(Neos.Neos:Page) {
    htmlTag.attributes.lang = Neos.Seo:LangAttribute
    head {
      titleTag = Neos.Seo:TitleTag
      metaDescriptionTag = Neos.Seo:MetaDescriptionTag
      metaKeywordsTag = Neos.Seo:MetaKeywordsTag
      metaRobotsTag = Neos.Seo:MetaRobotsTag
      canonicalLink = Neos.Seo:CanonicalLink
      alternateLanguageLinks = Neos.Seo:AlternateLanguageLinks
      twitterCard = Neos.Seo:TwitterCard
      openGraphMetaTags = Neos.Seo:OpenGraphMetaTags
    }
  }

If not all of the features are needed in a project, they can be disabled as needed. This example removes OpenGraph
support.

*Packages/Sites/Acme.AcmeCom/Configuration/NodeTypes.yaml*::

  'Neos.Neos:Document':
    superTypes:
      'Neos.Seo:OpenGraphMixin': false

*Packages/Sites/Acme.AcmeCom/Resources/Private/Fusion/Root.fusion*::

  prototype(Neos.Neos:Page).head.openGraphMetaTags >
