export const spacingClasses = {
    sm: 'py-12',
    md: 'py-16',
    lg: 'py-20',
    xl: 'py-20 lg:py-24',
};

export const backgroundClasses = {
    default: 'bg-white',
    muted: 'bg-slate-50/80',
    primary: 'bg-slate-950 text-white',
    gradient: 'bg-[radial-gradient(circle_at_top_left,_rgba(15,118,110,0.16),_transparent_34%),linear-gradient(135deg,_#ecfdf5_0%,_#eff6ff_52%,_#ffffff_100%)]',
    image: 'bg-slate-950 text-white',
};

export const containerClasses = {
    default: 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
    narrow: 'mx-auto w-full max-w-4xl px-4 sm:px-6 lg:px-8',
    wide: 'mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8',
    full: 'w-full px-4 sm:px-6 lg:px-8',
};

export function resolveSpacing(spacing = 'md') {
    return spacingClasses[spacing] ?? spacingClasses.md;
}

export function resolveBackground(background = 'default') {
    return backgroundClasses[background] ?? backgroundClasses.default;
}

export function resolveContainer(container = 'default') {
    return containerClasses[container] ?? containerClasses.default;
}
