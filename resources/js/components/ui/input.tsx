import * as React from "react"
import { Input as InputPrimitive } from "@base-ui/react/input"
import { cn } from "cn"

function Input({ className, type, ...props }: React.ComponentProps<"input">) {
  return (
    <InputPrimitive
      type={type}
      data-slot="input"
      className={cn(
        "h-11 w-full min-w-0 rounded-xl border border-input bg-surface px-3 text-base text-foreground transition-colors outline-none file:mr-3 file:inline-flex file:h-8 file:rounded-lg file:border-0 file:bg-surface-2 file:px-3 file:text-sm file:font-semibold file:text-foreground placeholder:text-faint focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:pointer-events-none disabled:cursor-not-allowed disabled:bg-surface-2 disabled:text-faint aria-invalid:border-destructive",
        className
      )}
      {...props}
    />
  )
}

export { Input }
