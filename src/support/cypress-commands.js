// Add a custom header for CakePHP to detect Cypress requests.
Cypress.Commands.overwrite('request', (originalFn, ...args) => {
  // args[0] can be the url or an options object
  const options = typeof args[0] === 'object' ? args[0] : { url: args[0] }

  // Set or merge custom headers
  options.headers = {
    ...options.headers,
    'x-cypress-header': true,
  }

  // Call the original 'cy.request' with the modified options
  return originalFn(options)
})

/**
 * Clear the database, truncate all tables.
 *
 * @example
 * cy.clearDatabase()
 */
Cypress.Commands.add('clearDatabase', (params) => {
  return cy
    .request({
      method: 'GET',
      url: '/cypress/clear-database',
      body: params,
      log: true,
    })
    .its('body', { log: false })
})

/**
 * Restore database
 *
 * @example
 * cy.importDatabase()
 * cy.importDatabase('tests/example.sql')
 */
Cypress.Commands.add('importDatabase', (filename) => {
  return cy.getCsrfToken().then((csrfToken) => {
    return cy
      .request({
        method: 'POST',
        url: '/cypress/import-database',
        body: { filename: filename },
        log: true,
        headers: {
          'X-CSRF-Token': csrfToken,
        },
      })
      .its('body', { log: false })
  })
})

/**
 * Helper functions used by other commands to get valid CSRF token.
 */
Cypress.Commands.add('getCsrfToken', (params) => {
  return cy
    .request({
      method: 'GET',
      url: '/cypress/csrf-token', // Use any page that returns a CSRF token
      log: false,
    })
    .then((response) => response.body.csrfToken)
})

/**
 * Create a new factory
 *
 * @param {String} factory name
 * @param {attributes} attributes that are used by factory
 *
 * @example
 * cy.create('User')
 * cy.create('User', {email: 'example@example.com'})
 */
Cypress.Commands.add('create', (params, attributes) => {
  if (typeof params === 'string') {
    params = { factory: params }
    params.attributes = attributes
  }

  return cy.getCsrfToken().then((csrfToken) => {
    return cy
      .request({
        method: 'POST',
        url: '/cypress/create',
        body: params,
        log: true,
        headers: {
          'X-CSRF-Token': csrfToken,
        },
      })
      .then((response) => {
        Cypress.log({
          name: 'create',
          message: params.factory,
          consoleProps: () => response.body,
        })
      })
  })
})

/**
 * Run arbitrary CakePHP commands
 *
 * @param {String} coomand
 *
 * @example
 * cy.cake('routes')
 */
Cypress.Commands.add('cake', (params, attributes) => {
  if (typeof params === 'string') {
    params = { command: params }
  }

  return cy.getCsrfToken().then((csrfToken) => {
    return cy
      .request({
        method: 'POST',
        url: '/cypress/cake',
        body: params,
        log: true,
        headers: {
          'X-CSRF-Token': csrfToken,
        },
      })
      .then((response) => {
        Cypress.log({
          name: 'cake',
          message: response.body,
          consoleProps: () => response.body,
        })
      })
  })
})
