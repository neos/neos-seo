.. _fallback-definitions:

Fallback definitions
====================

As this package provides many SEO related fields and mechanisms it can be a lot of work for editors
to fill everything the way it should be.

Therefore several default fallback chains are provided for the output.
Those are described in the following sections.

Our goal is to make it easy for integrators to extend these chains.
You can find them in the individual `Fusion` objects.
And feel free to created issues on Github if you think that something could be improved!

Open Graph Metatags
-------------------

+-----------------+--------------------------------------+-------------------------------------+
| Tag             | Default                              | Fallbacks                           |
+=================+======================================+=====================================+
| og:type         | Property `openGraphType`             | `website`                           |
+-----------------+--------------------------------------+-------------------------------------+
| og:title        | Property `openGraphTitle`            | 1. Property `titleOverride`         |
|                 |                                      | 2. Property `title`                 |
+-----------------+--------------------------------------+-------------------------------------+
| og:description  | Property `openGraphDescription`      | Property `metaDescription`          |
+-----------------+--------------------------------------+-------------------------------------+
| og:site_name    | Property `titleOverride` of homepage | Name defined in `Site Management`   |
+-----------------+--------------------------------------+-------------------------------------+
| og:image        | Property `openGraphImage`            | n/a                                 |
+-----------------+--------------------------------------+-------------------------------------+
| og:image:width  | Width of processed `og:image`        | n/a                                 |
+-----------------+--------------------------------------+-------------------------------------+
| og:image:height | Height of processed `og:image`       | n/a                                 |
+-----------------+--------------------------------------+-------------------------------------+
| og:image:alt    | Caption of image in `og:image`       | Label of image                      |
+-----------------+--------------------------------------+-------------------------------------+
| og:url          | Page Url                             | n/a                                 |
+-----------------+--------------------------------------+-------------------------------------+
| og:locale       | Locale from language dimension       | n/a                                 |
+-----------------+--------------------------------------+-------------------------------------+

TwitterCard Metatags
--------------------

+---------------------+-------------------------------------------------+-------------------------------------------------+
| Tag                 | Default                                         | Fallbacks                                       |
+=====================+=================================================+=================================================+
| twitter:card        | Property `twitterCardType`                      | `summary`                                       |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:title       | Property `twitterCardTitle`                     | 1. Property `openGraphTitle`                    |
|                     |                                                 | 2. Property `titleOverride`                     |
|                     |                                                 | 3. Property `title`                             |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:description | Property `twitterCardDescription`               | 1. Property `openGraphDescription`              |
|                     |                                                 | 2. Property `metaDescription`                   |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:creator     | Property `twitterCardCreator`                   | Configuration `Neos.Seo.twitterCard.siteHandle` |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:image       | Property `twitterCardImage`                     | Property `openGraphImage`                       |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:url         | Page Url                                        | n/a                                             |
+---------------------+-------------------------------------------------+-------------------------------------------------+
| twitter:site        | Configuration `Neos.Seo.twitterCard.siteHandle` | n/a                                             |
+---------------------+-------------------------------------------------+-------------------------------------------------+
