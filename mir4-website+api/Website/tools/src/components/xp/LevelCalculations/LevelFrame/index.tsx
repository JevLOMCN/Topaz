'use client'

import { cn } from '@/utils/classNames'
import Image from 'next/image'
import React from 'react'

export default function LevelFrame({
  label,
  percentage,
  onChange,
  className,
  value,
  success,
  ...props
}: {
  label: string
  percentage?: string
  onChange: (value: string) => void
  value?: string
  success: boolean
} & Omit<React.HTMLAttributes<HTMLElement>, 'value' | 'onChange'>) {
  return (
    <label
      className={cn(
        'flex shrink-0 flex-col gap-4 text-center text-sm font-bold text-white md:text-lg',
        className
      )}
    >
      {label}

      <div
        className={
          'relative flex h-24 w-24 flex-col items-center justify-center md:h-32 md:w-32'
        }
      >
        <Image
          src={'/images/level-frame.svg'}
          alt=""
          width={136}
          height={136}
          className="pointer-events-none absolute select-none"
        />
        <input
          className={cn(
            'relative z-10 w-28 bg-transparent text-center text-3xl font-bold text-white outline-none drop-shadow-sm transition-colors selection:bg-primary-800 placeholder:text-white/70 md:text-4xl',
            { 'pt-4': !!percentage }
          )}
          {...props}
          value={value}
          onChange={(e) => {
            onChange(e.target.value)
          }}
        />
        {percentage ? (
          <p className="relative z-10 text-xs font-normal text-white md:text-sm">
            {percentage}
          </p>
        ) : (
          <></>
        )}
      </div>
    </label>
  )
}
