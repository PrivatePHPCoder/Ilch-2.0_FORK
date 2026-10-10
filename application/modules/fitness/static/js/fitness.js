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

    // PayPal Checkout. PayPal's script is only loaded after the buyer clicks, so no data goes to
    // PayPal before. The server creates and books the payment; the page only passes the ids on.
    document.querySelectorAll('[data-fx-paypal]').forEach(function (box) {
        const start = box.querySelector('[data-fx-paypal-start]');
        const container = box.querySelector('.fx-paypal__buttons');
        const message = box.querySelector('.fx-paypal__message');

        const showMessage = function (text) {
            message.textContent = text || box.dataset.error;
            message.hidden = false;
        };

        const post = function (url, fields) {
            const body = new FormData();
            body.append('ilch_token', box.dataset.token);
            Object.keys(fields).forEach(function (name) {
                body.append(name, fields[name]);
            });

            return fetch(url, {
                method: 'POST',
                body: body,
                credentials: 'same-origin',
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            }).then(function (response) {
                return response.json();
            });
        };

        start.addEventListener('click', function () {
            start.disabled = true;
            message.hidden = true;

            const script = document.createElement('script');
            script.src = box.dataset.sdk;
            script.onerror = function () {
                start.disabled = false;
                showMessage();
            };
            script.onload = function () {
                start.hidden = true;
                container.hidden = false;

                window.paypal.Buttons({
                    createOrder: function () {
                        return post(box.dataset.create, {}).then(function (answer) {
                            if (!answer.id) {
                                showMessage(answer.message);
                                throw new Error(answer.message || 'PayPal');
                            }
                            return answer.id;
                        });
                    },
                    onApprove: function (data, actions) {
                        return post(box.dataset.capture, {paypalOrderId: data.orderID}).then(function (answer) {
                            if (answer.state === 'paid') {
                                window.location.href = answer.redirect;
                            } else if (answer.state === 'declined') {
                                return actions.restart();
                            } else {
                                showMessage(answer.message);
                            }
                        });
                    },
                    onError: function () {
                        showMessage();
                    }
                }).render(container);
            };
            document.head.appendChild(script);
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
