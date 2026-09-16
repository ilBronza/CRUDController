<script>
(() => {
    const cancelSelector = '.ibform-closure-buttons a[href="#crud-close-parent-overlay"]';

    window.__crudCloseParentOverlay = window.__crudCloseParentOverlay || (() => {
        const overlayWindow = window.self === window.top ? window : window.parent;

        const close = (method, selector, factory) => {
            try {
                if (typeof overlayWindow[method] === 'function')
                    return overlayWindow[method]();

                const overlay = overlayWindow.document.querySelector(selector);

                if (overlay && overlayWindow.UIkit)
                    factory(overlay).hide();
            } catch (error) {
                // The opener can be unavailable while the iframe is closing.
            }
        };

        close('closeLightbox', '.uk-lightbox.uk-open', overlay => overlayWindow.UIkit.lightbox(overlay));
        close('closeModal', '.uk-modal.uk-open', overlay => overlayWindow.UIkit.modal(overlay));

        try {
            if (typeof overlayWindow.__ibForceUnlockBodyScroll === 'function')
                overlayWindow.__ibForceUnlockBodyScroll();
        } catch (error) {
            // The parent is inaccessible only when it is from another origin.
        }
    });

    if (window.__crudIframedCancelBound)
        return;

    window.__crudIframedCancelBound = true;

    document.addEventListener('click', event => {
        if (! event.target.closest(cancelSelector))
            return;

        event.preventDefault();
        window.__crudCloseParentOverlay();
    });
})();
</script>
