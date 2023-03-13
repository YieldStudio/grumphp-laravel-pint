# GrumPHP Laravel Pint

A [Laravel Pint](https://laravel.com/docs/9.x/pint) task for [GrumPHP](https://github.com/phpro/grumphp).

[![Latest Version](https://img.shields.io/github/release/yieldstudio/grumphp-laravel-pint?style=flat-square)](https://github.com/yieldstudio/grumphp-laravel-pint/releases)
[![GitHub Workflow Status](https://img.shields.io/github/workflow/status/yieldstudio/grumphp-laravel-pint/tests?style=flat-square)](https://github.com/yieldstudio/grumphp-laravel-pint/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/yieldstudio/grumphp-laravel-pint?style=flat-square)](https://packagist.org/packages/yieldstudio/grumphp-laravel-pint)

## Installation

	composer require yieldstudio/grumphp-laravel-pint

## Usage

In your grumphp.yml : 

```yaml
grumphp:
  extensions:
    - YieldStudio\GrumPHPLaravelPint\ExtensionLoader
  tasks:
    laravel_pint:
      # These are all optional and have been set to sensible defaults.
      config: pint.json
      preset: laravel
      auto_git_stage: false
      triggered_by:
        - php
      ignore_patterns:
        - /^a-patten-to-ignore-files-or-folders\/.*/
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### Security

If you've found a bug regarding security please mail [contact@yieldstudio.fr](mailto:contact@yieldstudio.fr) instead of using the issue tracker.

## Credits

- [James Hemery](https://github.com/jameshemery)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

