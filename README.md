# Cypress plugin for CakePHP <!-- omit in toc -->

- [Overview](#overview)
- [Installation](#installation)
- [Helpers](#helpers)
  - [Clear Database](#clear-database)
  - [Import Database](#import-database)
  - [Create An Entity](#create-an-entity)
  - [Run Arbitrary Cake Command](#run-arbitrary-cake-command)
- [Contributing](#contributing)

## Overview

This add-on adds some helper files for working with Cypress and CakePHP.

- This plugin injects a CSRF token into POST requests made to `cypress-cake` endpoints.
- This plugin bypasses `Authentication` on `cypress-cake` endpoints.

> [!IMPORTANT]
> This plugin exposes an API to work with the database.
> It is recommend for local development only. Do not use in production!

## Installation

1. Install plugin

    ```shell
    ddev composer require tyler36/cypress-cake
    ddev cake plugin load Tyler36/CypressCake
    ```

1. Install Cypress if not available. Eg.
   - via NPM: `npm require cypress`
   - via [tyler36/ddev-cypress](https://github.com/tyler36/ddev-cypress): `ddev addon get tyler36/ddev-cypress`

1. Update `cypress/support/commands.js`  to import the helpers

    ```js
    import '../../vendor/tyler36/cypress-cake/src/support/cypress-commands'
    ```

## Helpers

| API                        | Description                                    |
| -------------------------- | ---------------------------------------------- |
| `/cypress/clear-database`  | Clear all data from database.                  |
| `/cypress/import-database` | Import a database file.                        |
| `/cypress/csrf-token`      | Get a CSRF token. This is required POST forms. |
| `/cypress/create`          | Run a factory to generate a entity.            |
| `/cypress/cake`            | Run arbitrary cake commands.                   |

### Clear Database

Use the Cypress command `cy.clearDatabase()` to clear all data from database

```js
cy.clearDatabase()
```

### Import Database

Use `cy.importDatabase()` to import a SQL file.
By default, this it will import the value `env('SQL_TESTING_BASE_DUMP')`.
However, you can provide a path as the first parameter.

```js
// Import `env('SQL_TESTING_BASE_DUMP')` database file.
cy.importDatabase()

// Import '/tmp/test.sql'.
cy.importDatabase('/tmp/test.sql')
```

### Create An Entity

Use `cy.create('User')` to generate an entity from a configured factory.
This packages expects to find [vierge-noire/cakephp-fixture-factories](https://github.com/vierge-noire/cakephp-fixture-factories) factories setup. Please the documentation there for creating factories.

```php
cy.create('User')
```

It is possible to pass additional attributes to the factory allowing to to set default data.

For example, the following creates a User entity with the email set to `foobar@example.com`.

```php
cy.create('User', { email: 'example@example.com' })
```

### Run Arbitrary Cake Command

Post a request to `/cypress/cake` to run CakePHP commands.

```php
# To get CakePHP version
cy.cake('version')

# To clear all caches
cy.cake('cache clear_all')
```

## Contributing

PRs, especially with tests, will be considered.

**Contributed and maintained by [tyler36](https://github.com/tyler36)**
