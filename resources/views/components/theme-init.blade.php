<script>
    (() => {
        const storageKey = 'shopkey-theme';
        let theme = 'dark';

        try {
            theme = window.localStorage.getItem(storageKey) === 'light' ? 'light' : 'dark';
        } catch (_) {
            theme = 'dark';
        }

        document.documentElement.dataset.theme = theme;
        document.documentElement.style.colorScheme = theme;
    })();
</script>
