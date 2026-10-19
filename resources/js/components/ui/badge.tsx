import { mergeProps } from "@base-ui/react/merge-props"
import { useRender } from "@base-ui/react/use-render"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "cn"

/*
 * The design's status pill: 1px 10px, fully round, 13px bold. Colours are the paired tokens (--success-bg/--success...);
 * "accent" and "highlight" are the solid red and yellow chips of the public site ("নতুন", "★ স্থাপিত").
 */
const badgeVariants = cva(
  "group/badge inline-flex h-auto w-fit shrink-0 items-center justify-center gap-1 overflow-hidden rounded-full border border-transparent px-2.5 py-px text-[13px] leading-normal font-bold whitespace-nowrap transition-colors focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 [&>svg]:pointer-events-none [&>svg]:size-3!",
  {
    variants: {
      variant: {
        default: "bg-green-50 text-green-700 [a]:hover:brightness-95",
        secondary: "bg-surface-2 text-muted-foreground [a]:hover:brightness-95",
        success: "bg-success-bg text-success [a]:hover:brightness-95",
        destructive: "bg-danger-bg text-danger [a]:hover:brightness-95",
        warning: "bg-warn-bg text-warn [a]:hover:brightness-95",
        info: "bg-info-bg text-info [a]:hover:brightness-95",
        accent: "bg-red-600 text-white",
        highlight: "bg-yellow-400 text-[#1a1a1a]",
        outline: "border-line-strong text-foreground [a]:hover:bg-surface-2",
        ghost: "text-muted-foreground hover:bg-surface-2",
        link: "text-primary underline-offset-4 hover:underline",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant = "default",
  render,
  ...props
}: useRender.ComponentProps<"span"> & VariantProps<typeof badgeVariants>) {
  return useRender({
    defaultTagName: "span",
    props: mergeProps<"span">(
      {
        className: cn(badgeVariants({ variant }), className),
      },
      props
    ),
    render,
    state: {
      slot: "badge",
      variant,
    },
  })
}

export { Badge, badgeVariants }
