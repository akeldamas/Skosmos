const { defineConfig } = require("cypress")

module.exports = defineConfig({
  projectRoot: "tests",
  e2e: {
    // You also can run like this: npx cypress run --config "baseUrl=http://localhost/Skosmos/"
    // !! Tailing slash in baseUrl is important
    baseUrl: 'http://localhost:9090/',
    setupNodeEvents(on, config) {
      on('task', {
        log(message) {
          console.log(message)
          return null
        },
        table(message) {
          console.table(message)
          return null
        }
      })
    },
    // Custom support file surfaces browser-side console.log from
    // load-search-results.js into the Cypress report (visible in headless CI).
    supportFile: 'tests/cypress/support/load-search-results-diag.js',
    specPattern: [
      'tests/cypress/accessibility/**/*.cy.js',
      'tests/cypress/template/**/*.cy.js',
      'tests/cypress/e2e/**/*.cy.js'
    ],
    screenshotsFolder: 'tests/cypress/screenshots',
    videosFolder: 'tests/cypress/videos'
  }
})
