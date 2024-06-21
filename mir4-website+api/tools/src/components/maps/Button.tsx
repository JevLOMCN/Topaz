import { cn } from '@/utils/classNames'

export default function Button({
  children,
  className,
  ...props
}: {
  children: React.ReactNode
  className?: string
} & React.HTMLAttributes<HTMLButtonElement>) {
  return (
    <button
      className={cn(
        'flex h-12 w-12 items-center justify-center rounded bg-white/10 p-2 transition-colors hover:bg-white/20',
        className
      )}
      {...props}
    >
      {children}
    </button>
  )
}
