import { cn } from '@/utils/classNames'
import React from 'react'

export default function StatusSelectorSkeleton() {
  return (
    <React.Fragment>
      {buttons.map(({ label, styling, tagStyling }) => (
        <label
          key={label}
          className={cn(
            'absolute flex flex-col items-center justify-center',
            styling
          )}
        >
          <span
            className={cn(
              'absolute flex animate-pulse flex-col whitespace-nowrap rounded-full bg-primary-450 px-2 py-1 text-xs font-medium text-transparent shadow-md lg:px-4 lg:py-2 lg:text-base',
              'translate-y-[3.25rem] lg:translate-y-0',
              tagStyling
            )}
          >
            {'Lv. 40 > Lv. 45'}
          </span>

          <div
            className={
              'group absolute z-[11] h-[4.2rem] w-[4.2rem] animate-pulse rounded-full bg-primary-450/40 duration-300 lg:h-[7.4rem] lg:w-[7.4rem]'
            }
          />
        </label>
      ))}
    </React.Fragment>
  )
}

const buttons: Array<{
  label: statusEffects
  styling: string
  tagStyling: string
}> = [
  {
    label: 'PHYS DEF',
    styling:
      'translate-x-[-6.2rem] translate-y-[-6.2rem] lg:translate-x-[-10.8rem] lg:translate-y-[-10.8rem]',
    tagStyling: 'lg:-translate-x-40',
  },
  {
    label: 'HP',
    styling: 'translate-x-[-8.8rem] lg:translate-x-[-15.25rem]',
    tagStyling: 'lg:-translate-x-40',
  },
  {
    label: 'EVA',
    styling:
      'translate-x-[-6.2rem] translate-y-[6.2rem] lg:translate-x-[-10.8rem] lg:translate-y-[10.8rem]',
    tagStyling: 'lg:-translate-x-40',
  },
  {
    label: 'PHYS ATK',
    styling: 'translate-y-[8.8rem] lg:translate-y-[15.2rem]',
    tagStyling: 'lg:translate-y-28',
  },
  {
    label: 'Accuracy',
    styling:
      'translate-x-[6.2rem] translate-y-[6.2rem] lg:translate-x-[10.8rem] lg:translate-y-[10.8rem]',
    tagStyling: 'lg:translate-x-40',
  },
  {
    label: 'MP',
    styling: 'translate-x-[8.8rem] lg:translate-x-[15.25rem]',
    tagStyling: 'lg:translate-x-40',
  },
  {
    label: 'Spell DEF',
    styling:
      'translate-x-[6.2rem] translate-y-[-6.2rem] lg:translate-x-[10.8rem] lg:translate-y-[-10.8rem]',
    tagStyling: 'lg:translate-x-40',
  },
]
