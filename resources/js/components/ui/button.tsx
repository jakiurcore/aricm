import { Button as ButtonPrimitive } from "@base-ui/react/button"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "cn"

/*
 * The design's buttons: pills, 44px tall, bold. "outline" is the white pill with the strong border, "secondary" the navy
 * tint of the table's Edit button, "destructive" the solid danger pill. sm/xs/icon are the 36px, 32px rounded-10 buttons
 * of table rows.
 */
const buttonVariants = cva(
  "group/button inline-flex shrink-0 cursor-pointer items-center justify-center gap-2 rounded-full border border-transparent text-base font-bold whitespace-nowrap transition-colors select-none outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 active:not-aria-[haspopup]:translate-y-px disabled:pointer-events-none disabled:cursor-not-allowed disabled:border-transparent disabled:bg-surface-2 disabled:text-faint [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground hover:brightness-110 aria-expanded:brightness-110",
        outline:
          "border-line-strong bg-surface font-semibold text-foreground hover:bg-surface-2 aria-expanded:bg-surface-2",
        secondary:
          "bg-secondary font-semibold text-secondary-foreground hover:brightness-95 aria-expanded:brightness-95",
        ghost:
          "font-semibold text-foreground hover:bg-surface-2 aria-expanded:bg-surface-2",
        destructive:
          "bg-danger text-white hover:brightness-110 dark:text-[#1a1a1a]",
        link: "rounded-none font-bold text-primary underline-offset-4 hover:underline",
      },
      size: {
        default:
          "h-11 px-[18px] has-data-[icon=inline-end]:pr-3.5 has-data-[icon=inline-start]:pl-3.5",
        xs: "h-8 rounded-[10px] px-2.5 text-xs [&_svg:not([class*='size-'])]:size-3.5",
        sm: "h-9 rounded-[10px] px-3 text-base [&_svg:not([class*='size-'])]:size-3.5",
        lg: "h-12 px-6",
        icon: "size-9 rounded-[10px]",
        "icon-xs": "size-7 rounded-lg [&_svg:not([class*='size-'])]:size-3.5",
        "icon-sm": "size-8 rounded-[10px]",
        "icon-lg": "size-11",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

function Button({
  className,
  variant = "default",
  size = "default",
  ...props
}: ButtonPrimitive.Props & VariantProps<typeof buttonVariants>) {
  return (
    <ButtonPrimitive
      data-slot="button"
      className={cn(buttonVariants({ variant, size, className }))}
      {...props}
    />
  )
}

export { Button, buttonVariants }
