import { ref, onMounted } from 'vue';

export function useAutofill(props) {
    const autofillData = props.autofillData ?? {};
    const autofilledFields = ref(new Set());

    function build(fieldKey) {
        return autofillData[fieldKey] ?? null;
    }

    function tryFill(target, fieldKey, transform = null) {
        const value = build(fieldKey);
        if (value === null || value === undefined) return false;

        const existing = target[fieldKey];
        if (existing !== undefined && existing !== '' && !(Array.isArray(existing) && existing.length === 0)) {
            return false;
        }

        target[fieldKey] = transform ? transform(value) : value;
        autofilledFields.value.add(fieldKey);
        return true;
    }

    function autofillAnswers(answersObj, availableFieldKeys) {
        for (const fieldKey of availableFieldKeys) {
            tryFill(answersObj, fieldKey);
        }
    }

    function autofillStatic(fieldsObj, mapping) {
        for (const [fieldKey, sourceKey] of Object.entries(mapping)) {
            tryFill(fieldsObj, fieldKey, (v) => {
                if (sourceKey instanceof Function) return sourceKey(v);
                return v;
            });
        }
    }

    function isAutofilled(fieldKey) {
        return autofilledFields.value.has(fieldKey);
    }

    return { build, tryFill, autofillAnswers, autofillStatic, isAutofilled, autofilledFields, autofillData };
}
