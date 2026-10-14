import { Check, ChevronsUpDown } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from '@/components/ui/command';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import type { SelectOption } from '@/types';

type Props = {
    id?: string;
    options: SelectOption[];
    value: number | null;
    onChange: (value: number) => void;
    placeholder: string;
    searchPlaceholder?: string;
    emptyText?: string;
    invalid?: boolean;
    disabled?: boolean;
};

/**
 * Select dengan pencarian (combobox) untuk daftar pilihan yang panjang.
 */
export function SearchableSelect({
    id,
    options,
    value,
    onChange,
    placeholder,
    searchPlaceholder = 'Cari…',
    emptyText = 'Tidak ada hasil.',
    invalid = false,
    disabled = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const selected = options.find((option) => option.value === value);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    aria-invalid={invalid}
                    disabled={disabled}
                    className="h-auto min-h-9 w-full justify-between px-3 py-2 text-left font-normal whitespace-normal"
                >
                    {selected ? (
                        <span className="flex min-w-0 flex-col">
                            <span className="truncate">{selected.label}</span>
                            {selected.description && (
                                <span className="truncate text-xs text-muted-foreground">
                                    {selected.description}
                                </span>
                            )}
                        </span>
                    ) : (
                        <span className="text-muted-foreground">
                            {placeholder}
                        </span>
                    )}
                    <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-(--radix-popover-trigger-width) min-w-72 p-0"
                align="start"
            >
                <Command>
                    <CommandInput placeholder={searchPlaceholder} />
                    <CommandList>
                        <CommandEmpty>{emptyText}</CommandEmpty>
                        <CommandGroup>
                            {options.map((option) => (
                                <CommandItem
                                    key={option.value}
                                    value={`${option.label} ${option.description ?? ''}`}
                                    onSelect={() => {
                                        onChange(option.value);
                                        setOpen(false);
                                    }}
                                >
                                    <Check
                                        className={cn(
                                            'size-4',
                                            option.value === value
                                                ? 'opacity-100'
                                                : 'opacity-0',
                                        )}
                                    />
                                    <span className="flex min-w-0 flex-col">
                                        <span>{option.label}</span>
                                        {option.description && (
                                            <span className="text-xs text-muted-foreground">
                                                {option.description}
                                            </span>
                                        )}
                                    </span>
                                </CommandItem>
                            ))}
                        </CommandGroup>
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
