(function () {
  'use strict';

  // Get DOM elements
  const searchInput = document.getElementById('ebsco-search-box-text');
  const searchButton = document.getElementById('ebsco-search-box-submit');

  // Check if elements exist
  if (!searchInput || !searchButton) {
    return;
  }

  // Handle Enter key press
  searchInput.addEventListener('keypress', function (event) {
    if (event.key === 'Enter' || event.keyCode === 13) {
      goNewSearch(event);
    }
  });

  // Handle button click
  searchButton.addEventListener('click', function (event) {
    goNewSearch(event);
  });

  function goNewSearch(event) {
    const queryString = searchInput.value.trim();

    if (queryString.length > 0) {
      event.preventDefault();
      const url = new URL('https://research.ebsco.com/c/ryvb5t/results');
      url.searchParams.set('autocorrect', 'y');
      url.searchParams.set('q', queryString);
      window.location.href = url.toString(); // phpcs:ignore WordPressVIPMinimum.JS.Window.location
    }
  }
})();
