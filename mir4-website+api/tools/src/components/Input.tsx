import { cn } from '@/utils/classNames'
import { type DetailedHTMLProps, type InputHTMLAttributes } from 'react'

export default function Input({
  label,
  className,
  suffix,
  error,
  isLoading = false,
  ...props
}: {
  label?: string
  suffix?: React.ReactNode
  error?: React.ReactNode
  isLoading?: boolean
} & DetailedHTMLProps<
  InputHTMLAttributes<HTMLInputElement>,
  HTMLInputElement
>) {
  return (
    <label
      className={cn(
        'flex w-full flex-col items-center gap-1 text-center text-sm font-medium text-primary-100',
        className
      )}
    >
      {label}
      <div
        className={cn(
          'flex w-full items-center justify-center gap-1.5 rounded-md bg-primary-600 px-1.5 py-1 font-bold text-white transition-colors duration-300 sm:px-3 sm:py-2',
          { '!border-red-400 bg-red-400/30': error }
        )}
      >
        <input
          className={cn(
            'flex w-full appearance-none items-center justify-center bg-transparent text-center text-sm font-normal outline-none transition-colors duration-300 selection:bg-primary-800 placeholder:text-neutral-200/70 sm:text-base',
            {
              'text-red-200': error,
            },
            {
              'mx-auto w-1/2 animate-pulse rounded-full bg-primary-500/50 text-transparent placeholder:text-transparent':
                isLoading,
            }
          )}
          {...props}
        />
        {suffix && (
          <div className="shrink-0 text-sm font-bold text-white sm:text-base">
            {suffix}
          </div>
        )}
      </div>
    </label>
  )
}
