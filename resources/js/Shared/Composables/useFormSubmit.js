import { useForm } from '@inertiajs/vue3';

export function useFormSubmit(data) {
    const form = useForm(data);

    function submit(method, url, options = {}) {
        const { onSuccess, onError, ...rest } = options;

        form[method](url, {
            ...rest,
            onSuccess: () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                onSuccess?.();
            },
            onError: (errors) => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                onError?.(errors);
            },
        });
    }

    return {
        ...form,
        submit,
    };
}