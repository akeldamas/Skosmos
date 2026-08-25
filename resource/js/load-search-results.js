/* global $t, onTranslationReady */

let searchResultOffset = window.SKOSMOS.search_results_size

// DIAGNOSTIC: record when this script first runs, so we can order events in CI logs
const loadSearchResultsStart = Date.now()
function lsrTimestamp () { return Date.now() - loadSearchResultsStart }

// DIAGNOSTIC: log to console AND persist to localStorage. Headless Cypress under
// GitHub Actions hides the browser console, but cy.task (Node main process) can
// reach localStorage and echo these lines into the CI stdout text log.
function lsrLog (msg) {
  // eslint-disable-next-line no-console
  console.log('[load-search-results] ' + msg)
  try {
    const key = '__lsrDiag'
    let arr = []
    if (window.localStorage && window.localStorage.getItem(key)) {
      arr = JSON.parse(window.localStorage.getItem(key))
    }
    arr.push(msg)
    window.localStorage.setItem(key, JSON.stringify(arr))
  } catch (e) { /* ignore storage errors */ }
}

function handleScrollEvent () {
  lsrLog('@' + lsrTimestamp() + 'ms handleScrollEvent fired! bottomVisible path. offset=' + searchResultOffset)
  const searchResultList = document.getElementById('search-results')

  // Only load new search results if the bottom of the result list is visible and no unloaded results remain
  const bottomVisible = searchResultList.getBoundingClientRect().bottom <= window.innerHeight && searchResultList.getBoundingClientRect().bottom >= 0
  if (bottomVisible && searchResultOffset < window.SKOSMOS.search_count) {
    // Disable event listener until more results are loaded
    document.removeEventListener('scroll', handleScrollEvent)

    // Add a spinner to end of result list
    const spinner = document.createElement('p')
    spinner.id = 'search-loading-spinner'
    spinner.innerHTML = `${$t('Loading more items')} <i class="fa-solid fa-spinner fa-spin-pulse"></i>`
    searchResultList.append(spinner)

    // Construct search URL depending on page type
    const params = new URLSearchParams(window.location.search)
    params.set('offset', searchResultOffset)
    const searchURL =
      window.SKOSMOS.pageType === 'vocab-search'
        ? `${window.SKOSMOS.vocab}/${window.SKOSMOS.lang}/search?${params.toString()}`
        : `${window.SKOSMOS.lang}/search?${params.toString()}`
    fetch(searchURL)
      .then(data => {
        return data.text()
      })
      .then(data => {
        const resultHTML = document.createElement('div')
        resultHTML.innerHTML = data.trim()

        // Append new search results to the list
        const searchResults = resultHTML.querySelector('#search-results').querySelectorAll('.search-result')
        for (const elem of searchResults) {
          searchResultList.append(elem)
        }

        // Remove spinner and increment offset
        searchResultList.removeChild(spinner)
        searchResultOffset += window.SKOSMOS.search_results_size

        // Re-enable event listener after results have been loaded
        document.addEventListener('scroll', handleScrollEvent)

        // If all results have been loaded, display message
        if (searchResultOffset >= window.SKOSMOS.search_count) {
          const message = document.createElement('p')
          message.id = 'search-count'
          message.textContent = $t('All %d results displayed').replace('%d', window.SKOSMOS.search_count)
          searchResultList.append(message)
        }
      })
  }
}

function registerSearchResultEventListener () {
  lsrLog('@' + lsrTimestamp() + 'ms onTranslationReady fired; attaching scroll listener. pageType=' + (window.SKOSMOS ? window.SKOSMOS.pageType : 'undefined'))
  if (window.SKOSMOS.pageType === 'vocab-search' || window.SKOSMOS.pageType === 'global-search') {
    document.addEventListener('scroll', handleScrollEvent)
    lsrLog('@' + lsrTimestamp() + 'ms scroll listener attached. scrollTop=' + document.documentElement.scrollTop + ' clientHeight=' + document.documentElement.clientHeight + ' offsetHeight=' + document.documentElement.offsetHeight)
  } else {
    lsrLog('@' + lsrTimestamp() + 'ms onTranslationReady fired but did NOT attach scroll listener (pageType=' + window.SKOSMOS.pageType + ')')
  }
}

onTranslationReady(registerSearchResultEventListener)

// DIAGNOSTIC: log the moment scrollTo would trigger a fetch so we can see if it ever runs
function diagnosticScrollProbe () {
  lsrLog('@' + lsrTimestamp() + 'ms scroll event fired at scrollTop=' + window.scrollY)
}
document.addEventListener('scroll', diagnosticScrollProbe, { passive: true })
