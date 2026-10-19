import * as React from "react"
import { cn } from "cn"

function Textarea({ className, ...props }: React.ComponentProps<"textarea">) {
  return (
    <textarea
      data-slot="textarea"
      className={cn(
        "flex field-sizing-content min-h-24 w-full rounded-xl border border-input bg-surface px-3 py-2.5 text-base text-foreground transition-colors outline-none placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint aria-invalid:border-destructive",
        className
      )}
      {...props}
    />
  )
}

export { Textarea }
