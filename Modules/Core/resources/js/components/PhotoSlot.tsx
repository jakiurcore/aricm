import { ImageIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { cn } from '@/lib/utils';

interface PhotoSlotProps {
    /** The picture already on file, shown until a new one is chosen. */
    src?: string | null;
    placeholder: string;
    onChange: (file: File | null) => void;
    /** "rounded" is the 96 x 116 student portrait, "circle" the 84px teacher avatar. */
    shape?: 'rounded' | 'circle';
    accept?: string;
    className?: string;
    id?: string;
}

/**
 * The design's image slot: a dashed frame that is the whole click target. Empty it shows the picture glyph and a
 * caption; filled it shows the picture, and a click picks another one.
 */
export function PhotoSlot({ src, placeholder, onChange, shape = 'rounded', accept = 'image/png,image/jpeg,image/webp', className, id }: PhotoSlotProps) {
    const input = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(null);
    const shown = preview ?? src ?? null;

    useEffect(() => () => {
        if (preview) {
            URL.revokeObjectURL(preview);
        }
    }, [preview]);

    return (
        <button
            type="button"
            onClick={() => input.current?.click()}
            aria-label={placeholder}
            className={cn(
                'relative grid flex-none cursor-pointer place-items-center overflow-hidden bg-surface-2 text-muted-foreground outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400',
                shape === 'circle' ? 'size-[84px] rounded-full' : 'h-[116px] w-24 rounded-2xl',
                shown ? 'border-0' : 'border-2 border-dashed border-input',
                className,
            )}
        >
            {shown ? (
                <img src={shown} alt="" className="size-full object-cover" />
            ) : (
                <span className="grid justify-items-center gap-1 px-4 text-center text-[13px] leading-tight font-medium">
                    <ImageIcon className="size-6" aria-hidden="true" />
                    {placeholder}
                </span>
            )}
            <input
                ref={input}
                id={id}
                type="file"
                accept={accept}
                hidden
                onChange={(event) => {
                    const file = event.target.files?.[0] ?? null;
                    setPreview(file ? URL.createObjectURL(file) : null);
                    onChange(file);
                }}
            />
        </button>
    );
}
