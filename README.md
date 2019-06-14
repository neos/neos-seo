[![Latest Stable Version](https://poser.pugx.org/neos/seo/v/stable)](https://packagist.org/packages/neos/seo)
[![Total Downloads](https://poser.pugx.org/neos/seo/downloads)](https://packagist.org/packages/neos/seo)
[![License](https://poser.pugx.org/neos/seo/license)](LICENSE)

# Neos.Seo

A package to enable additional SEO features for Neos CMS.

It includes for example:

* Meta tags
* Sitemap
* Social tags
* Structured data

Check the [documentation](https://neos-seo.readthedocs.io/en/stable/) for all features and usage. 

## Installation and usage

1. Run the following command f.e. in your site package:
   ```bash
   composer require --no-update neos/seo
   ```
   
2. Update your dependencies by running the following command in your project root folder:
   ```bash
   composer update
   ```
   
3. [Read the documentation](https://neos-seo.readthedocs.io/en/stable/)


## Contributions

When creating a PR and you change is valid for version 2.1 too, please set this branch also as your PR target.

If you are unsure or change a newer feature, use master as target.

### Doing upmerges

To do an upmerge from 2.1 to 3.x run the following command

    git checkout master && git fetch && git reset --hard origin/master && git merge --no-ff --no-commit origin/2.1 --strategy-option=ours

## License

See [License](LICENSE.txt).
