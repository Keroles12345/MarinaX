(() => {
  document.querySelectorAll('[data-infinite-scroll]').forEach(container => {
    const rows = document.querySelector(container.dataset.rows);
    const summary = container.dataset.summary
      ? document.querySelector(container.dataset.summary)
      : null;
    const button = container.querySelector('[data-load-more-button]');
    const message = container.querySelector('[data-load-message]');
    const messageText = container.querySelector('[data-load-message-text]');
    const spinner = container.querySelector('[data-load-spinner]');
    const sentinel = container.querySelector('[data-load-sentinel]');

    if (!rows || !button || !message || !messageText || !spinner || !sentinel) return;

    const minimumRequestInterval = 2000;
    const scrollDelay = 1000;
    let nextPageUrl = container.dataset.nextPage;
    let hasMore = container.dataset.hasMore === 'true';
    let loading = false;
    let failed = false;
    let sentinelVisible = false;
    let lastRequestFinished = 0;
    let scheduledLoad = null;

    const setLoadingIndicator = isLoading => {
      container.classList.toggle('is-loading', isLoading);
      if (isLoading) {
        container.setAttribute('aria-busy', 'true');
        spinner.hidden = false;
        spinner.style.display = '';
        button.disabled = true;
        button.textContent = container.dataset.loadingText;
        messageText.textContent = container.dataset.loadingText;
        return;
      }

      container.removeAttribute('aria-busy');
      spinner.hidden = true;
      spinner.style.display = 'none';
      button.disabled = false;
      if (failed) {
        button.textContent = container.dataset.retryText;
      } else if (hasMore) {
        button.textContent = container.dataset.moreText;
        messageText.textContent = '';
      }
    };

    const updateAvailability = () => {
      const isAvailable = hasMore || failed;
      button.hidden = !isAvailable;
      button.style.display = isAvailable ? '' : 'none';
      if (!hasMore) messageText.textContent = container.dataset.endText;
    };

    const loadNextPage = async () => {
      if (loading || failed || !hasMore || !nextPageUrl) return;

      loading = true;
      setLoadingIndicator(true);

      try {
        const delay = Math.max(0, minimumRequestInterval - (Date.now() - lastRequestFinished));
        if (delay) await new Promise(resolve => setTimeout(resolve, delay));

        const response = await fetch(nextPageUrl, {
          headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!response.ok) throw new Error(`Infinite scroll request failed: ${response.status}`);

        const result = await response.json();
        if (
          typeof result.html !== 'string' ||
          typeof result.hasMore !== 'boolean' ||
          !(result.nextPageUrl === null || typeof result.nextPageUrl === 'string')
        ) {
          throw new Error('Invalid infinite scroll response');
        }

        rows.insertAdjacentHTML('beforeend', result.html);
        const selectAll = document.getElementById('check-all-operations');
        if (selectAll?.checked) {
          rows.querySelectorAll('.operation-checkbox').forEach(checkbox => {
            checkbox.checked = true;
          });
        }
        nextPageUrl = result.nextPageUrl;
        hasMore = result.hasMore;
        if (summary && typeof result.summary === 'string') summary.textContent = result.summary;
        failed = false;
        messageText.textContent = '';
        updateAvailability();
      } catch (error) {
        console.error(error);
        failed = true;
        messageText.textContent = container.dataset.retryText;
        button.textContent = container.dataset.retryText;
      } finally {
        lastRequestFinished = Date.now();
        loading = false;
        setLoadingIndicator(false);
        if (sentinelVisible && hasMore && !failed) {
          const remainingInterval = Math.max(0, minimumRequestInterval - (Date.now() - lastRequestFinished));
          scheduleLoad(Math.max(scrollDelay, remainingInterval));
        }
      }
    };

    const scheduleLoad = delay => {
      if (scheduledLoad || loading || failed || !hasMore) return;
      setLoadingIndicator(true);
      scheduledLoad = setTimeout(() => {
        scheduledLoad = null;
        if (sentinelVisible) {
          loadNextPage();
        } else {
          setLoadingIndicator(false);
        }
      }, delay);
    };

    button.addEventListener('click', () => {
      failed = false;
      loadNextPage();
    });

    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver(entries => {
        sentinelVisible = entries[0].isIntersecting;
        if (sentinelVisible && !failed) {
          const remainingInterval = Math.max(0, minimumRequestInterval - (Date.now() - lastRequestFinished));
          scheduleLoad(Math.max(scrollDelay, remainingInterval));
        } else if (scheduledLoad) {
          clearTimeout(scheduledLoad);
          scheduledLoad = null;
          if (!loading) setLoadingIndicator(false);
        }
      }, { rootMargin: '120px' });
      observer.observe(sentinel);
    }

    updateAvailability();
  });
})();
