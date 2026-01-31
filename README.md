# Pluginboilerplate

A minimal, modern WordPress plugin boilerplate using PHP 8.1, PSR-4 autoloading (Composer), a small dependency-injection container, hook contracts and a simple view renderer.

---

## Table of contents

- [Requirements](#requirements)
- [Installation](#installation)
  - [Install as a plugin (zip / copy)](#install-as-a-plugin-zip--copy)
  - [Generate autoload](#generate-autoload)
- [Quick start](#quick-start)
- [Architecture overview](#architecture-overview)
  - [Plugin entry (pluginboilerplate.php)](#plugin-entry-pluginboilerplatephp)
  - [Bootstrap Container](#bootstrap-container)
  - [Hooks (HookInterface)](#hooks-hookinterface)
  - [Services & ViewRenderer](#services--viewrenderer)
  - [Assets and templates](#assets-and-templates)
- [Examples / Recipes](#examples--recipes)
  - [Add a new Hook](#add-a-new-hook)
  - [Register a new service](#register-a-new-service)
  - [Render a template from code](#render-a-template-from-code)
  - [Enqueue additional assets](#enqueue-additional-assets)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Contributing](#contributing)
- [Credits & Notes](#credits--notes)

---

## Requirements

- PHP 8.1 or later (declared in plugin header).
- WordPress (any modern version; ensure your install meets plugin requirements).
- Composer for development tasks.

Files of interest:
- pluginboilerplate.php
- composer.json
- src/
- templates/
- assets/

---

## Installation

### Install as a plugin (zip / copy)
1. Copy or zip the repository root into a WordPress `wp-content/plugins/pluginboilerplate` folder.
2. Activate the plugin in WordPress admin: Plugins → Installed Plugins → "Pluginboilerplate" → Activate.

Activation and deactivation hooks call:
- Activation: `Activate::run()` — file: `src/Activate.php`
- Deactivation: `Deactivate::run()` — file: `src/Deactivate.php`

### Generate autoload
This project uses PSR-4 autoloading defined in `composer.json`.

To install dependencies (dev):
```bash
composer install
```

Run php-cs-fixer via composer script:
```bash
composer run fix    # runs vendor/bin/php-cs-fixer fix
composer run check  # runs vendor/bin/php-cs-fixer check
```

Note: The plugin autoloads using `vendor/autoload.php` in `pluginboilerplate.php`, so in a development environment ensure `composer install` has been executed.

---

## Quick start

1. Put the plugin folder in `wp-content/plugins/`.
2. Activate plugin in WordPress admin.
3. The plugin registers hooks (see `src/Bootstrap/Container.php`) and enqueues example assets:
   - CSS: `assets/css/style.css`
   - JS: `assets/js/script.js` (localized variable: `pluginboilerplate_vars.ajax_url`)
4. Example front-end behavior: `ExampleHook` triggers on `wp_footer` and renders `templates/example-template.php`.

Open a front-end page and check the footer output (the template prints "Hello Example title"). Also check browser console network for enqueued script handle `pluginboilerplate-script`.

---

## Architecture overview

This plugin uses a simple structured architecture to keep code modular and testable.

### Plugin entry (pluginboilerplate.php)
- Registers activation/deactivation hooks using the static classes `Activate` and `Deactivate`.
- Loads Composer autoloader:
  ```php
  require_once __DIR__ . '/vendor/autoload.php';
  ```
- Boots the plugin in `plugins_loaded`:
  ```php
  add_action('plugins_loaded', function (): void {
      Plugin::run(__FILE__);
  });
  ```

### Plugin class (src/Plugin.php)
- Main bootstrap orchestrator:
  - Holds plugin file path and version.
  - Creates the DI container: `new Container()`.
  - Iterates `getRegisteredHooks()` and calls `register()` on each hook.
- Utility helpers:
  - `Plugin::getPath($subPath)` — filesystem path to plugin files.
  - `Plugin::getUrl($subPath)` — URL to plugin files (useful for enqueuing assets).
  - `Plugin::getVersion()` — plugin version.

### Bootstrap Container (src/Bootstrap/Container.php)
- Central place for creating/retrieving services and registering hooks.
- Returns an array of Hook objects in `getRegisteredHooks()`. Example:
  ```php
  public function getRegisteredHooks(): array
  {
      return [
          new ExampleHook($this->exampleService()),
          new AssetsHook(),
      ];
  }
  ```
- Use it to add new service instances and to wire dependencies.

### Hooks (src/Hook)
- `HookInterface` defines a single method `register(): void`.
- Hooks are responsible for registering WordPress actions/filters.
- Examples:
  - `ExampleHook` - registers `wp_footer` action and calls `ExampleService`.
  - `AssetsHook` - registers `wp_enqueue_scripts` actions to enqueue plugin CSS/JS and localize script data.

### Services & ViewRenderer (src/Service)
- Services encapsulate plugin business logic. Example: `ExampleService` calls the `ViewRenderer`.
- `ViewRenderer` loads templates from the `templates/` folder using:
  ```php
  $path = Plugin::getPath("templates/{$templateName}.php");
  if (!\file_exists($path)) {
      throw new \RuntimeException("Template {$templateName} not found");
  }
  \extract($args);
  include $path;
  ```
- Use `render('example-template', ['title' => 'My title'])` to render a template.

---

## Examples / Recipes

All examples assume PSR-4 namespace root: `Pluginboilerplatevendor\Pluginboilerplate\...`.

### Add a new Hook
1. Create file: `src/Hook/MyCustomHook.php`
```php
<?php
declare(strict_types=1);

namespace Pluginboilerplatevendor\Pluginboilerplate\Hook;

final class MyCustomHook implements HookInterface
{
    public function register(): void
    {
        add_action('init', [$this, 'onInit']);
    }

    public function onInit(): void
    {
        // Your code here
    }
}
```
2. Register it in the container (edit `src/Bootstrap/Container.php`):
```php
public function getRegisteredHooks(): array
{
    return [
        new ExampleHook($this->exampleService()),
        new AssetsHook(),
        new MyCustomHook(), // <-- add here
    ];
}
```

### Register a new service
1. Add a service class under `src/Service/MyService.php`:
```php
<?php
declare(strict_types=1);

namespace Pluginboilerplatevendor\Pluginboilerplate\Service;

final class MyService
{
    public function doWork(): string
    {
        return 'done';
    }
}
```
2. Expose it via the Container:
```php
public function myService(): MyService
{
    return $this->instances[MyService::class] ??= new MyService();
}
```
3. Inject into a Hook by adding the constructor parameter and updating how the Hook is created in `getRegisteredHooks()`.

### Render a template from code
From a service or hook:
```php
// inside ExampleService or any service with access to ViewRenderer
$this->viewRenderer->render('example-template', ['title' => 'Custom Title']);
```
Template file: `templates/example-template.php`
```php
<?php
/** @var string $title */
?>
<h1>Hello <?php echo $title; ?></h1>
```

### Enqueue additional assets
You may add more scripts/styles in `AssetsHook`:
```php
public function enqueueFrontScripts(): void
{
    $handle = 'pluginboilerplate-script';
    wp_enqueue_script($handle, Plugin::getUrl('assets/js/script.js'), ['jquery'], Plugin::getVersion(), true);

    // additional script
    wp_enqueue_script(
        'pluginboilerplate-extra',
        Plugin::getUrl('assets/js/extra.js'),
        [],
        Plugin::getVersion(),
        true
    );
}
```

---

## Development

- Composer is required to install dev tooling:
  ```bash
  composer install
  ```
- Code style:
  - The project includes `friendsofphp/php-cs-fixer` as a dev dependency.
  - Composer scripts in `composer.json`:
    - `composer run fix` — auto-fix coding style.
    - `composer run check` — check coding style.
- Autoloading:
  - PSR-4 autoloading is defined in `composer.json`:
    ```json
    "autoload": {
      "psr-4": {
        "Pluginboilerplatevendor\\\\Pluginboilerplate\\\\": "src/"
      }
    }
    ```
  - After adding new classes, run:
    ```bash
    composer dump-autoload
    ```

Testing:
- This boilerplate does not include automated tests by default. Consider adding PHPUnit or WP-specific testing frameworks (WP_Mock, WP-CLI, WP PHPUnit) based on your needs.

---

## Troubleshooting

- Missing template exception:
  - If you see `RuntimeException: Template X not found` — ensure `templates/{name}.php` exists and `Plugin::getPath()` resolves correctly.
- Autoload errors:
  - Run `composer dump-autoload` and verify `vendor/autoload.php` is loaded by `pluginboilerplate.php`.
- Assets not loading:
  - Confirm `AssetsHook` is being registered by inspecting `getRegisteredHooks()` — and that the plugin is active.
  - Clear caches and check browser console for 404s.

---

## Contributing

- Feel free to open issues or PRs.
- Follow PSR-12 / php-cs-fixer rules as configured by the maintainers; run `composer run fix` before commit.


