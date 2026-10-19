import { Tabs as TabsPrimitive } from "@base-ui/react/tabs"
import { cn } from "cn"

/* The design's tabs: a row of 44px pills that scrolls sideways on a phone; the active one is solid green. */
function Tabs({ className, ...props }: TabsPrimitive.Root.Props) {
  return (
    <TabsPrimitive.Root
      data-slot="tabs"
      className={cn("flex flex-col gap-3.5", className)}
      {...props}
    />
  )
}

function TabsList({ className, ...props }: TabsPrimitive.List.Props) {
  return (
    <TabsPrimitive.List
      data-slot="tabs-list"
      className={cn("flex gap-1.5 overflow-x-auto", className)}
      {...props}
    />
  )
}

function TabsTrigger({ className, ...props }: TabsPrimitive.Tab.Props) {
  return (
    <TabsPrimitive.Tab
      data-slot="tabs-trigger"
      className={cn(
        "inline-flex h-11 flex-none cursor-pointer items-center justify-center gap-2 rounded-full border border-line-strong bg-surface px-4 text-[15px] font-semibold whitespace-nowrap text-foreground transition-colors outline-none hover:bg-surface-2 focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-yellow-400 disabled:cursor-not-allowed disabled:opacity-50 data-active:border-primary data-active:bg-primary data-active:text-primary-foreground data-active:hover:bg-primary",
        className
      )}
      {...props}
    />
  )
}

function TabsContent({ className, ...props }: TabsPrimitive.Panel.Props) {
  return (
    <TabsPrimitive.Panel
      data-slot="tabs-content"
      className={cn("outline-none", className)}
      {...props}
    />
  )
}

export { Tabs, TabsList, TabsTrigger, TabsContent }
