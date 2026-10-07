/*
 * Fitness module: frontend helpers.
 *
 * Videos are only loaded after a click, so no data is sent to YouTube or Vimeo before
 * the visitor agrees.
 */
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.fx-video[data-src]').forEach(function (box) {
        const button = box.querySelector('.fx-video-load');
        if (!button) {
            return;
        }

        button.addEventListener('click', function () {
            const iframe = document.createElement('iframe');
            iframe.src = box.dataset.src;
            iframe.title = box.dataset.title || '';
            iframe.allow = 'accelerometer; encrypted-media; gyroscope; picture-in-picture; fullscreen';
            iframe.allowFullscreen = true;
            iframe.loading = 'lazy';
            box.innerHTML = '';
            box.appendChild(iframe);
            box.classList.add('fx-video--loaded');
        });
    });
});
