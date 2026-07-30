import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';
import { useRef } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { changeTypePresentation, changeTypes } from '@/types';
import type { ChangeType } from '@/types';

export type ComposerChangeEntry = {
    id?: number;
    client_id: string;
    change_type: ChangeType;
    content: string;
};

export function ChangeEntryList({
    entries,
    errors,
    onChange,
}: {
    entries: ComposerChangeEntry[];
    errors: Record<string, string>;
    onChange: (entries: ComposerChangeEntry[]) => void;
}) {
    const addButtonRef = useRef<HTMLButtonElement>(null);

    function createEntry(): ComposerChangeEntry {
        return {
            client_id: crypto.randomUUID(),
            change_type: 'added',
            content: '',
        };
    }

    function focusEntry(clientId: string | undefined): void {
        requestAnimationFrame(() => {
            if (clientId) {
                document
                    .getElementById(`change-entry-${clientId}-content`)
                    ?.focus();

                return;
            }

            addButtonRef.current?.focus();
        });
    }

    function addEntry(): void {
        const entry = createEntry();

        onChange([...entries, entry]);
        focusEntry(entry.client_id);
    }

    function updateEntry(
        clientId: string,
        attributes: Partial<ComposerChangeEntry>,
    ): void {
        onChange(
            entries.map((entry) =>
                entry.client_id === clientId
                    ? { ...entry, ...attributes }
                    : entry,
            ),
        );
    }

    function removeEntry(index: number): void {
        const nextEntries = entries.filter(
            (_, entryIndex) => entryIndex !== index,
        );
        const nextFocus = nextEntries[index] ?? nextEntries[index - 1];

        onChange(nextEntries);
        focusEntry(nextFocus?.client_id);
    }

    function moveEntry(index: number, direction: -1 | 1): void {
        const destination = index + direction;

        if (destination < 0 || destination >= entries.length) {
            return;
        }

        const nextEntries = [...entries];
        [nextEntries[index], nextEntries[destination]] = [
            nextEntries[destination],
            nextEntries[index],
        ];

        onChange(nextEntries);
        focusEntry(nextEntries[destination].client_id);
    }

    return (
        <section className="space-y-4" aria-labelledby="change-entries-heading">
            <div className="space-y-1">
                <h2
                    id="change-entries-heading"
                    className="text-lg font-semibold"
                >
                    Change entries
                </h2>
                <p className="text-sm text-muted-foreground">
                    Every change type is valid. Use the one that best describes
                    this moment; none carries more weight than another.
                </p>
            </div>

            <div className="space-y-3">
                {entries.map((entry, index) => (
                    <fieldset
                        key={entry.client_id}
                        className="min-w-0 space-y-4 rounded-lg border border-border p-3 sm:p-4"
                    >
                        <legend className="px-1 text-sm font-medium">
                            Entry {index + 1}
                        </legend>
                        <div className="grid gap-2">
                            <Label
                                htmlFor={`change-entry-${entry.client_id}-type`}
                            >
                                Change type
                            </Label>
                            <select
                                id={`change-entry-${entry.client_id}-type`}
                                value={entry.change_type}
                                onChange={(event) =>
                                    updateEntry(entry.client_id, {
                                        change_type: event.target
                                            .value as ChangeType,
                                    })
                                }
                                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                {changeTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {changeTypePresentation[type].label}
                                    </option>
                                ))}
                            </select>
                            <p className="text-sm text-muted-foreground">
                                {
                                    changeTypePresentation[entry.change_type]
                                        .description
                                }
                            </p>
                            <InputError
                                message={
                                    errors[
                                        `change_entries.${index}.change_type`
                                    ]
                                }
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label
                                htmlFor={`change-entry-${entry.client_id}-content`}
                            >
                                What changed?
                            </Label>
                            <Input
                                id={`change-entry-${entry.client_id}-content`}
                                value={entry.content}
                                onChange={(event) =>
                                    updateEntry(entry.client_id, {
                                        content: event.target.value,
                                    })
                                }
                                maxLength={2000}
                                aria-invalid={Boolean(
                                    errors[`change_entries.${index}.content`],
                                )}
                            />
                            <InputError
                                message={
                                    errors[`change_entries.${index}.content`]
                                }
                            />
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => moveEntry(index, -1)}
                                disabled={index === 0}
                                aria-label={`Move entry ${index + 1} up`}
                            >
                                <ChevronUp /> Move up
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => moveEntry(index, 1)}
                                disabled={index === entries.length - 1}
                                aria-label={`Move entry ${index + 1} down`}
                            >
                                <ChevronDown /> Move down
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() => removeEntry(index)}
                                aria-label={`Remove entry ${index + 1}`}
                            >
                                <Trash2 /> Remove
                            </Button>
                        </div>
                    </fieldset>
                ))}
            </div>

            <Button
                ref={addButtonRef}
                type="button"
                variant="outline"
                onClick={addEntry}
            >
                <Plus /> Add change entry
            </Button>
            <InputError message={errors.change_entries} />
        </section>
    );
}
