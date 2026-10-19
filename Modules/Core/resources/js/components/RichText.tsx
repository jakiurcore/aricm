import { useEffect, useRef } from 'react';

import { cn } from '@/lib/utils';

interface RichTextProps {
    value: string;
    onChange: (html: string) => void;
    minHeight?: number;
    label?: string;
    labels: { bold: string; italic: string; list: string; link: string; linkPrompt: string };
}

/**
 * The design's body editor: a 36px toolbar (B, I, bullet list, link) over a contenteditable area. The server keeps only
 * the tags these four buttons make (see Modules/Notices/Support/SafeHtml.php).
 */
export function RichText({ value, onChange, minHeight = 110, label, labels }: RichTextProps) {
    const area = useRef<HTMLDivElement>(null);

    // Only push a value in from outside (loading a notice to edit, clearing after a save); typing keeps its own caret.
    useEffect(() => {
        if (area.current && area.current.innerHTML !== value) {
            area.current.innerHTML = value;
        }
    }, [value]);

    const run = (command: string, argument?: string) => {
        area.current?.focus();
        document.execCommand(command, false, argument);
        onChange(area.current?.innerHTML ?? '');
    };

    const button = 'size-9 cursor-pointer rounded-lg border-0 bg-transparent hover:bg-line';

    return (
        <div className="overflow-hidden rounded-xl border border-input focus-within:outline-3 focus-within:outline-offset-2 focus-within:outline-yellow-400">
            <div className="flex gap-0.5 border-b border-line bg-surface-2 p-1">
                <button type="button" aria-label={labels.bold} title={labels.bold} onMouseDown={(event) => event.preventDefault()} onClick={() => run('bold')} className={cn(button, 'font-extrabold')}>
                    B
                </button>
                <button type="button" aria-label={labels.italic} title={labels.italic} onMouseDown={(event) => event.preventDefault()} onClick={() => run('italic')} className={cn(button, 'italic')}>
                    I
                </button>
                <button type="button" aria-label={labels.list} title={labels.list} onMouseDown={(event) => event.preventDefault()} onClick={() => run('insertUnorderedList')} className={button}>
                    •≡
                </button>
                <button
                    type="button"
                    aria-label={labels.link}
                    title={labels.link}
                    onMouseDown={(event) => event.preventDefault()}
                    onClick={() => {
                        const url = window.prompt(labels.linkPrompt, 'https://');

                        if (url) {
                            run('createLink', url);
                        }
                    }}
                    className={button}
                >
                    🔗
                </button>
            </div>
            <div
                ref={area}
                contentEditable
                suppressContentEditableWarning
                role="textbox"
                aria-multiline="true"
                aria-label={label}
                onInput={() => onChange(area.current?.innerHTML ?? '')}
                className="bg-surface px-3 py-2.5 font-normal outline-none [&_a]:text-green-700 [&_a]:underline [&_ul]:list-disc [&_ul]:pl-5"
                style={{ minHeight }}
            />
        </div>
    );
}
