import { cn } from '@/utils/classNames'

export default function MapButton({
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
        'rounded px-4 py-3 text-xl font-bold text-white transition-colors hover:bg-white/10',
        className
      )}
      {...props}
    >
      {children}
    </button>
  )
}
