import { cn } from "cn"

function Skeleton({ className, ...props }: React.ComponentProps<"div">) {
  return (
    <div
      data-slot="skeleton"
      className={cn("adm-skeleton rounded-xl", className)}
      {...props}
    />
  )
}

export { Skeleton }
