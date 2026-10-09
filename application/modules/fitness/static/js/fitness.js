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

    // Copies a text, for example the payment reference.
    document.querySelectorAll('[data-fx-copy]').forEach(function (button) {
        button.addEventListener('click', function () {
            if (!navigator.clipboard) {
                return;
            }

            navigator.clipboard.writeText(button.dataset.fxCopy).then(function () {
                const label = button.querySelector('span');
                if (label && button.dataset.fxCopied) {
                    label.textContent = button.dataset.fxCopied;
                }
            });
        });
    });

    // Asks before buttons with a lasting effect, for example cancelling an order.
    document.querySelectorAll('[data-fx-confirm]').forEach(function (button) {
        button.addEventListener('click', function (event) {
            if (!window.confirm(button.dataset.fxConfirm)) {
                event.preventDefault();
            }
        });
    });
});
