import * as CPopover from '@radix-ui/react-popover'

function Wrapper({
  children,
  ...props
}: { children: React.ReactNode } & CPopover.PopoverProps) {
  return <CPopover.Root {...props}>{children}</CPopover.Root>
}

function Trigger({
  children,
  ...props
}: { children: React.ReactNode } & CPopover.PopoverTriggerProps) {
  return <CPopover.Trigger {...props}>{children}</CPopover.Trigger>
}

function Content({
  children,
  ...props
}: { children: React.ReactNode } & CPopover.PopoverContentProps) {
  return (
    <CPopover.Portal>
      <CPopover.Content {...props}>{children}</CPopover.Content>
    </CPopover.Portal>
  )
}

const Popover = {
  Wrapper,
  Content,
  Trigger,
}

export default Popover
