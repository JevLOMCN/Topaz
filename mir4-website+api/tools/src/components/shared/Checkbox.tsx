import Tick from '@/icons/Tick'
import { cn } from '@/utils/classNames'
import * as CCheckbox from '@radix-ui/react-checkbox'
import * as Label from '@radix-ui/react-label'

export default function Checkbox({
  label,
  className,
  ...props
}: { label: string } & CCheckbox.CheckboxProps) {
  return (
    <Label.Root className="group flex items-center gap-3 text-sm font-medium text-white">
      <CCheckbox.Root
        className={cn(
          'grid h-5 w-5 place-items-center rounded border border-primary-400 bg-primary-500 transition-colors disabled:opacity-50 group-hover:enabled:border-primary-200',
          className
        )}
        {...props}
      >
        <CCheckbox.Indicator>
          <Tick className="h-4 w-4 fill-white" />
        </CCheckbox.Indicator>
      </CCheckbox.Root>
      {label}
    </Label.Root>
  )
}
