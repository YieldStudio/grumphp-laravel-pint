# GrumPHP Laravel Pint

A [Laravel Pint](https://laravel.com/docs/9.x/pint) task for [GrumPHP](https://github.com/phpro/grumphp).

[![Latest Version](https://img.shields.io/github/release/yieldstudio/grumphp-laravel-pint?style=flat-square)](https://github.com/yieldstudio/grumphp-laravel-pint/releases)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/yieldstudio/grumphp-laravel-pint/tests.yml?branch=main&style=flat-square)](https://github.com/yieldstudio/grumphp-laravel-pint/actions/workflows/tests.yml)
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
      # Auto fix Laravel Pint issues
      # Can be false, true, 'run' or 'pre_commit' (default)
      auto_fix: 'pre_commit' 
      # Auto stage files after auto fix
      # Can be false, true, 'run' or 'pre_commit' (default)
      # Works only if the task has been auto fixed (Without GrumPHP having to ask for it)
      auto_stage: 'pre_commit'
      triggered_by:
        - php
      ignore_patterns:
        - /^a-patten-to-ignore-files-or-folders\/.*/
```

**Auto fix**

When auto_fix is not enabled, GrumPHP runs Pint in dry mode and then asks if you want to apply patches automatically.

By default, `auto_fix` is enabled only in a pre_commit context.

Possible values are `true`, `false`, `pre_commit`, `run`.

**Auto stage**

Allows you to automatically stage (`git add`) files affected by Pint with GrumPHP.

By default, `auto_stage` is enabled only in a pre_commit context.

This option only works when `auto_fix` is enabled for the runtime context.


## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

### Security

If you've found a bug regarding security please mail [contact@yieldstudio.fr](mailto:contact@yieldstudio.fr) instead of using the issue tracker.

## Credits

- [James Hemery](https://github.com/jameshemery)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

