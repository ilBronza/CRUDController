<script>
(() => {
    const form = document.currentScript.closest('form');
    const errors = {{ Illuminate\Support\Js::from(isset($errors) ? $errors->getBag('default')->keys() : []) }};

    const initialize = () => {
        if (!form || !window.UIkit) return;

        form.querySelectorAll('[data-crud-translations]').forEach(group => {
            const navigation = group.querySelector('[uk-switcher]');
            const switcher = navigation?.nextElementSibling;
            if (!switcher) return;

            const panels = Array.from(switcher.children);
            const controls = panel => Array.from(panel.querySelectorAll('input[name], textarea[name], select[name]'));
            const errorIndex = panels.findIndex(panel => controls(panel).some(control => errors.includes(control.name)));

            panels.forEach((panel, index) => {
                if (controls(panel).some(control => errors.includes(control.name))) {
                    navigation.children[index]?.classList.add('uk-text-danger');
                }
            });

            if (errorIndex >= 0) window.UIkit.switcher(navigation).show(errorIndex);

            group.addEventListener('invalid', event => {
                const index = panels.findIndex(panel => panel.contains(event.target));
                if (index < 0 || panels[index].classList.contains('uk-active')) return;

                event.preventDefault();
                window.UIkit.switcher(navigation).show(index);
                requestAnimationFrame(() => event.target.focus());
            }, true);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize, { once: true });
    } else {
        initialize();
    }
})();
</script>
