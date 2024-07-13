import { cn } from '@/utils/classNames'

export default function MenuButton({
  children,
  className,
  ...props
}: {
  className?: string
} & Exclude<React.HTMLAttributes<HTMLButtonElement>, 'className'>) {
  return (
    <button
      {...props}
      className={cn(
        'flex grow items-center justify-center gap-2 rounded-md p-2 transition-colors md:p-4',
        className
      )}
    >
      {children}
    </button>
  )
}
