// Minimal YouTube lazy loader for .c-video patterns and is-style-overlay embeds
import { onReady } from './utils/dom.js';

function enhanceVideoOverlay(root = document) {
  const containers = root.querySelectorAll('.c-video');
  containers.forEach((wrap) => {
    const btn = wrap.querySelector('.c-video__btn');
    if (!btn) {
      return;
    }
    const embedWrapper = wrap.querySelector('.wp-block-embed__wrapper');
    const urlAnchor = wrap.querySelector('.c-video__url');

    const dataUrl = wrap.getAttribute('data-yt-url') || '';
    const wrapperText = embedWrapper
      ? (embedWrapper.textContent || '').trim()
      : '';
    const anchorHref = urlAnchor ? urlAnchor.getAttribute('href') || '' : '';
    const iframeEl = wrap.querySelector('iframe');
    const urlText = (
      dataUrl ||
      anchorHref ||
      wrapperText ||
      (iframeEl ? iframeEl.getAttribute('src') || '' : '')
    ).trim();
    if (!/youtube\.com|youtu\.be/.test(urlText)) {
      return;
    }

    // Derive embed URL with autoplay and modest params
    let videoId = '';
    try {
      const url = new URL(urlText);
      if (url.hostname.includes('youtu.be')) {
        videoId = url.pathname.replace('/', '').split('?')[0];
      } else if (url.pathname.includes('/embed/')) {
        videoId = url.pathname.split('/embed/')[1].split('/')[0];
      } else {
        videoId = url.searchParams.get('v') || '';
      }
    } catch (e) {
      // Fallback regex
      const m = urlText.match(/(?:youtu\.be\/|v=|embed\/)([A-Za-z0-9_-]{6,})/);
      if (m) {
        videoId = m[1];
      }
    }
    if (!videoId) {
      return;
    }

    const src = videoId
      ? `https://www.youtube.com/embed/${videoId}?autoplay=1&controls=1&rel=0&modestbranding=1&playsinline=1&enablejsapi=1&origin=${encodeURIComponent(
          location.origin
        )}`
      : '';

    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const poster = wrap.querySelector('.c-video__poster');
      // Prefer native embed replacement: set src on the existing wrapper if possible
      if (embedWrapper) {
        embedWrapper.innerHTML = '';
        const iframe = document.createElement('iframe');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute(
          'allow',
          'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture'
        );
        iframe.src = src;
        embedWrapper.appendChild(iframe);

        // Restore overlay if video is paused for a while (YouTube sends blur/focus messages, not true pause via postMessage without API)
        let overlayTimer;
        const restoreOverlay = () => {
          wrap.classList.remove('is-playing');
          // Re-show controls
          if (poster) {
            poster.style.display = '';
          }
          btn.style.display = '';
          // Also pause the YouTube player via postMessage API
          try {
            if (iframe && iframe.contentWindow) {
              iframe.contentWindow.postMessage(
                JSON.stringify({
                  event: 'command',
                  func: 'pauseVideo',
                  args: [],
                }),
                '*'
              );
            }
          } catch (_) {}
        };
        const startTimer = () => {
          clearTimeout(overlayTimer);
          overlayTimer = setTimeout(restoreOverlay, 5000); // 5s idle → show overlay
        };
        const cancelTimer = () => {
          clearTimeout(overlayTimer);
        };
        // Heuristic: if iframe loses focus and mouse/touch is idle, start timer; cancel on interaction
        iframe.addEventListener('blur', startTimer);
        iframe.addEventListener('load', cancelTimer);
        iframe.addEventListener('mouseenter', cancelTimer);
        iframe.addEventListener('mousemove', cancelTimer);
        iframe.addEventListener('mouseleave', startTimer);
        iframe.addEventListener('touchstart', cancelTimer, { passive: true });
        iframe.addEventListener('touchend', startTimer, { passive: true });
      }
      wrap.classList.add('is-playing');
    });
  });
}

onReady(enhanceVideoOverlay);

export { enhanceVideoOverlay };
