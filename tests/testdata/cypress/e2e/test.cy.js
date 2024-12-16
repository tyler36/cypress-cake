describe('Cypress-cake', () => {
  it('manages the database', () => {
    cy.clearDatabase().then((response) => {
      expect(response.data).to.equal(true)
    })
    cy.visit('/users')
    cy.get('table').should('not.contain', 'now@example.com')

    /**
     * This test will fail if the file does not exist. Because we are running in a browser environment, we don't have
     * access to the filesystem to dynamically create the file. We create it in the BATS test.
     */
    cy.importDatabase('/tmp/test.sql')

    cy.visit('/users')
    cy.get('table').should('contain', 'now@example.com')

    cy.clearDatabase()
    cy.visit('/users')
    cy.get('table').should('not.contain', 'now@example.com')
  })

  it('creates a user', () => {
    const email = 'example@example.com'
    cy.create('User', { email }).then((response) => {
      expect(response.status).to.equal(200)
      expect(response.body.data.email).to.equal(email)
    })

    cy.visit('/users')
    cy.get('table').should('contain', email)
  })
})
