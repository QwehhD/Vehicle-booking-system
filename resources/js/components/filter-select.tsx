import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { Option } from '@/types';

const ALL = 'all';

/**
 * Select filter berlabel dengan pilihan "Semua" untuk mengosongkan filter.
 */
export function FilterSelect({
    id,
    label,
    value,
    options,
    onChange,
    allLabel = 'Semua',
    className,
}: {
    id: string;
    label: string;
    value: string | null;
    options: Option[];
    onChange: (value: string | null) => void;
    allLabel?: string;
    className?: string;
}) {
    return (
        <div className={cn('grid gap-1.5', className)}>
            <Label htmlFor={id}>{label}</Label>
            <Select
                value={value ?? ALL}
                onValueChange={(selected) =>
                    onChange(selected === ALL ? null : selected)
                }
            >
                <SelectTrigger id={id} className="w-full">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={ALL}>{allLabel}</SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}
