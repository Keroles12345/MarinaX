<script>
    window.dashboardTranslations = @json(\App\Support\DashboardTemplateTranslator::clientTranslations());
    window.dashboardTranslate = (value) => {
        if (document.documentElement.lang !== 'en' || typeof value !== 'string') return value;

        const translations = window.dashboardTranslations;
        const escapeRegExp = (text) => text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        let translated = value;

        Object.entries(translations)
            .sort(([left], [right]) => right.length - left.length)
            .forEach(([source, target]) => {
                const pattern = new RegExp(`(^|[^\\u0600-\\u06FF])${escapeRegExp(source)}(?=$|[^\\u0600-\\u06FF])`, 'gu');
                translated = translated.replace(pattern, (_, prefix) => `${prefix}${target}`);
            });

        return translated.replace(/[\u0600-\u06FF]+/gu, (word) => translations[word] || word);
    };
</script>
