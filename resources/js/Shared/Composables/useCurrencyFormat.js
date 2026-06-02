import { ref, computed } from 'vue';

export function useCurrencyFormat(initialValue = null) {
    const rawValue = ref(initialValue);

    const setRaw = (val) => {
        rawValue.value = val;
    };

    const displayValue = computed(() => {
        const num = parseFloat(rawValue.value);
        if (isNaN(num) || num === null || num === '') return '';
        return num.toLocaleString('en-MY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    });

    const numericValue = computed(() => {
        return parseFloat(rawValue.value) || 0;
    });

    const format = (val) => {
        const num = parseFloat(val);
        if (isNaN(num)) return '-';
        return 'RM ' + num.toLocaleString('en-MY', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    };

    const formatShort = (val) => {
        const num = parseFloat(val);
        if (isNaN(num)) return '-';
        return 'RM ' + num.toLocaleString('en-MY', {
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        });
    };

    const handleInput = (e) => {
        const cleaned = e.target.value.replace(/[^0-9.]/g, '');
        const parts = cleaned.split('.');
        if (parts.length > 2) return;
        if (parts[0] && parts[0].length > 15) return;
        rawValue.value = cleaned;
    };

    const toApiValue = () => numericValue.value;

    return {
        rawValue,
        setRaw,
        displayValue,
        numericValue,
        format,
        formatShort,
        handleInput,
        toApiValue,
    };
}