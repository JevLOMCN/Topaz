import Backpack from '@/icons/Backpack'
import Balance from '@/icons/Balance'
import { cn } from '@/utils/classNames'
import millify from 'millify'
import { useState } from 'react'

export default function InventoryInput({
  isTraddable,
  isInventory,
  className,
  ...props
}: {
  isTraddable?: boolean
  isInventory?: boolean
} & React.InputHTMLAttributes<HTMLInputElement>) {
  const [focused, setFocused] = useState(false)

  return (
    <label
      className={cn(
        'relative flex w-full items-center gap-1.5 px-2 py-1 text-xs sm:text-base',
        className
      )}
    >
      {isTraddable ? (
        <Balance className="h-6 w-6 shrink-0 fill-white" />
      ) : (
        <></>
      )}
      {isInventory ? (
        <Backpack className="h-4 w-4 shrink-0 fill-white" />
      ) : (
        <></>
      )}

      <input
        className={
          'flex w-full appearance-none items-center justify-center bg-transparent text-center text-sm font-semibold outline-none transition-colors duration-300 selection:bg-primary-800 placeholder:text-neutral-200/70 sm:text-base'
        }
        {...props}
        value={
          focused
            ? props.value
            : typeof props.value === 'number' && Number(props.value)
            ? millify(props.value)
            : props.value
        }
        onBlur={() => setFocused(false)}
        onFocus={() => setFocused(true)}
        onClick={(e) => e.currentTarget.setSelectionRange(0, 25, 'backward')}
      />
    </label>
  )
}
