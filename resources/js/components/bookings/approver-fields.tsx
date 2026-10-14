import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import InputError from '@/components/input-error';
import { SearchableSelect } from '@/components/searchable-select';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { SelectOption } from '@/types';

export const MIN_APPROVERS = 2;
export const MAX_APPROVERS = 5;

type Props = {
    options: SelectOption[];
    value: (number | null)[];
    onChange: (value: (number | null)[]) => void;
    errors: Partial<Record<string, string>>;
};

/**
 * Daftar approver berjenjang: urutan baris menentukan level persetujuan.
 */
export function ApproverFields({ options, value, onChange, errors }: Props) {
    const update = (index: number, approverId: number) =>
        onChange(
            value.map((current, i) => (i === index ? approverId : current)),
        );

    const move = (index: number, direction: -1 | 1) => {
        const next = [...value];
        [next[index], next[index + direction]] = [
            next[index + direction],
            next[index],
        ];
        onChange(next);
    };

    const remove = (index: number) =>
        onChange(value.filter((_, i) => i !== index));

    const canAdd =
        value.length < MAX_APPROVERS && value.length < options.length;

    return (
        <div className="grid gap-4">
            {value.map((approverId, index) => {
                const takenElsewhere = value.filter(
                    (id, i) => i !== index && id !== null,
                );
                const available = options.filter(
                    (option) => !takenElsewhere.includes(option.value),
                );
                const error = errors[`approvers.${index}`];

                return (
                    <div key={index} className="grid gap-1.5">
                        <Label htmlFor={`approver-${index}`}>
                            Level {index + 1}
                        </Label>
                        <div className="flex items-start gap-2">
                            <div className="min-w-0 flex-1">
                                <SearchableSelect
                                    id={`approver-${index}`}
                                    options={available}
                                    value={approverId}
                                    onChange={(id) => update(index, id)}
                                    placeholder="Pilih approver"
                                    searchPlaceholder="Cari nama atau jabatan…"
                                    invalid={Boolean(error)}
                                />
                            </div>
                            <div className="flex shrink-0 gap-1">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => move(index, -1)}
                                    disabled={index === 0}
                                    aria-label={`Naikkan level ${index + 1}`}
                                >
                                    <ArrowUp />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => move(index, 1)}
                                    disabled={index === value.length - 1}
                                    aria-label={`Turunkan level ${index + 1}`}
                                >
                                    <ArrowDown />
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={() => remove(index)}
                                    disabled={value.length <= MIN_APPROVERS}
                                    aria-label={`Hapus level ${index + 1}`}
                                >
                                    <Trash2 />
                                </Button>
                            </div>
                        </div>
                        <InputError message={error} />
                    </div>
                );
            })}

            <InputError message={errors.approvers} />

            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => onChange([...value, null])}
                    disabled={!canAdd}
                >
                    <Plus />
                    Tambah level
                </Button>
                <p className="text-sm text-muted-foreground">
                    Minimal {MIN_APPROVERS}, maksimal {MAX_APPROVERS} level.
                    Persetujuan berjalan berurutan dari Level 1.
                </p>
            </div>
        </div>
    );
}
