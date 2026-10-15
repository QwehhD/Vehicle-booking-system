export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header
            className={variant === 'small' ? '' : 'reveal mb-8 space-y-1.5'}
        >
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium'
                        : 'font-display text-2xl font-normal tracking-tight sm:text-[2rem]'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-sm text-muted-foreground">{description}</p>
            )}
        </header>
    );
}
