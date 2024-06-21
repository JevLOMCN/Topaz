import { cn } from '@/utils/classNames'
import * as CTooltip from '@radix-ui/react-tooltip'
import React from 'react'

function Wrapper({
  children,
  ...props
}: { children: React.ReactNode } & CTooltip.TooltipProps) {
  return (
    <CTooltip.Provider delayDuration={0}>
      <CTooltip.Root {...props}>{children}</CTooltip.Root>
    </CTooltip.Provider>
  )
}

function Trigger({
  children,
  ...props
}: { children: React.ReactNode } & CTooltip.TooltipTriggerProps) {
  return <CTooltip.Trigger asChild {...props}>{children}</CTooltip.Trigger>
}

function Content({
  children,
  ...props
}: { children: React.ReactNode } & CTooltip.TooltipContentProps) {
  return (
    <CTooltip.Portal>
      <CTooltip.Content
        {...props}
        className={cn(
          'flex items-center shadow rounded font-main bg-primary-600 text-xs px-2 py-1 font-medium text-white',
          props.className
        )}
      >
        {children}
      </CTooltip.Content>
    </CTooltip.Portal>
  )
}

const Tooltip = {
  Wrapper,
  Content,
  Trigger,
}

export default Tooltip
