import { type HTMLAttributes } from 'react'

export default function ToggleFilter({
  children,
  value,
  disabled = false,
  ...props
}: {
  children: React.ReactNode
  value: boolean
  disabled?: boolean
} & HTMLAttributes<HTMLButtonElement>) {
  return (
    <button data-active={value} disabled={disabled} {...props}>
      {children}
    </button>
  )
}
