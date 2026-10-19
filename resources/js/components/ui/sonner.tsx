"use client"

import { Toaster as Sonner, type ToasterProps } from "sonner"
import { CircleCheckIcon, InfoIcon, TriangleAlertIcon, OctagonXIcon, Loader2Icon } from "lucide-react"

import { currentTheme } from "../../admin-shell/theme"

/* The design's toast: a round ink pill at the bottom centre, bold, with the large shadow. Errors turn danger-red. */
const Toaster = ({ ...props }: ToasterProps) => {
  return (
    <Sonner
      theme={currentTheme()}
      position="bottom-center"
      className="toaster group"
      icons={{
        success: <CircleCheckIcon className="size-4" />,
        info: <InfoIcon className="size-4" />,
        warning: <TriangleAlertIcon className="size-4" />,
        error: <OctagonXIcon className="size-4" />,
        loading: <Loader2Icon className="size-4 animate-spin" />,
      }}
      style={
        {
          "--normal-bg": "var(--ink)",
          "--normal-text": "var(--bg)",
          "--normal-border": "transparent",
          "--border-radius": "999px",
          "--success-bg": "var(--ink)",
          "--success-text": "var(--bg)",
          "--success-border": "transparent",
          "--error-bg": "var(--danger)",
          "--error-text": "#ffffff",
          "--error-border": "transparent",
          "--info-bg": "var(--ink)",
          "--info-text": "var(--bg)",
          "--info-border": "transparent",
          "--warning-bg": "var(--warn)",
          "--warning-text": "#ffffff",
          "--warning-border": "transparent",
        } as React.CSSProperties
      }
      toastOptions={{
        classNames: {
          toast: "cn-toast font-semibold! shadow-lg!",
        },
      }}
      {...props}
    />
  )
}

export { Toaster }
