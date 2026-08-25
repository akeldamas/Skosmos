/* global Cypress */
// Support file: surface browser-side diagnostics from load-search-results.js into
// the CI stdout text log under headless GitHub Actions runs.
//
// How it works: the app JS (resource/js/load-search-results.js) writes every
// diagnostic line to localStorage. Here we read that buffer per test and echo it
// through cy.task('log'), which runs in Cypress's Node main process and is the
// only mechanism whose output reaches the GitHub Actions text log in headless CI.
// (Browser console.log is hidden; cy.log() does not reach stdout.)
//
// beforeEach  -> start each test with an empty buffer, preserving per-test order
// afterEach   -> echo every line accumulated during that test, then clear

const DIAG_KEY = '__lsrDiag'

beforeEach(() => {
  // Clear the buffer so lines only appear under the test they belong to.
  cy.window().then(win => {
    win.localStorage.removeItem(DIAG_KEY)
  })
})

afterEach(() => {
  cy.window().then(win => {
    let lines = []
    try {
      const raw = win.localStorage.getItem(DIAG_KEY)
      if (raw) {
        lines = JSON.parse(raw)
      }
    } catch (e) { /* ignore malformed buffer */ }
    // Clear the buffer now so a later test does not re-echo these lines.
    win.localStorage.removeItem(DIAG_KEY)
    return lines
  }).then(lines => {
    for (const line of lines) {
      cy.task('log', '[load-search-results] ' + line)
    }
  })
})
