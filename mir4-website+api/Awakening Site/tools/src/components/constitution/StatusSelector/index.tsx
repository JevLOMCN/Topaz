'use client'

import { statusAtom, statusLevelsAtom } from '@/atoms/Constitution'
import Accuracy from '@/icons/Accuracy'
import Eva from '@/icons/Eva'
import HP from '@/icons/HP'
import MP from '@/icons/MP'
import PhysAtk from '@/icons/PhysAtk'
import PhysDef from '@/icons/PhysDef'
import SpellDef from '@/icons/SpellDef'
import { cn } from '@/utils/classNames'
import { Transition } from '@headlessui/react'
import { useAtom } from 'jotai'
import React from 'react'
import StatusCostTooltip from './StatusCostTooltip'
import StatusInput from './StatusInput'

export default function ConstitutionStatusSelector() {
  const [status, setStatus] = useAtom(statusAtom)
  const [levels, setLevels] = useAtom(statusLevelsAtom)

  return (
    <>
      {buttons.map(({ label, styling, inputStyling, tagStyling, Icon }) => {
        const isActive = label === status
        const hasLevelDifference = levels[label].to > levels[label].from

        return (
          <label
            key={label}
            htmlFor={`from${label}`}
            className={cn(
              'absolute flex flex-col items-center justify-center',
              styling,
              { 'z-40': isActive }
            )}
          >
            <Transition
              show={isActive}
              enter="transition-transform duration-200"
              enterFrom="translate-x-0 opacity-0"
              enterTo={cn('opacity-100', inputStyling)}
              leave="transition-[transform,opacity] duration-300"
              leaveFrom={cn('opacity-100', inputStyling)}
              leaveTo="translate-x-0 opacity-0"
              as="div"
              className={cn(
                'absolute z-10 flex items-center gap-2',
                'translate-y-24 lg:translate-y-0',
                inputStyling
              )}
            >
              <StatusInput
                label={label}
                levels={levels}
                setLevels={setLevels}
              />
            </Transition>

            <Transition
              show={!isActive}
              enter="transition-transform duration-300"
              enterFrom="opacity-0"
              enterTo={'opacity-100'}
              leave="transition-[transform,opacity] duration-200"
              leaveFrom={'opacity-100'}
              leaveTo="opacity-0"
              as="span"
              className={cn(
                'absolute flex flex-col whitespace-nowrap rounded-full bg-primary-450 px-2 py-1 text-xs font-medium text-white shadow-md lg:px-4 lg:py-2 lg:text-base',
                'translate-y-[3.25rem] lg:translate-y-0',
                tagStyling
              )}
            >
              {`Lv. ${levels[label].from} ${
                levels[label].to > levels[label].from
                  ? `> Lv. ${levels[label].to}`
                  : ''
              }`}
            </Transition>

            <StatusCostTooltip
              selectStatus={() =>
                setStatus((prev) => (prev === label ? null : label))
              }
              Icon={Icon}
              hasLevelDifference={hasLevelDifference}
              isActive={isActive}
              label={label}
              levels={levels}
            />
          </label>
        )
      })}
    </>
  )
}

const buttons: Array<{
  label: statusEffects
  styling: string
  inputStyling: string
  tagStyling: string
  Icon: (props: React.SVGProps<SVGSVGElement>) => JSX.Element
}> = [
  {
    label: 'PHYS DEF',
    styling:
      'translate-x-[-6.2rem] translate-y-[-6.2rem] lg:translate-x-[-10.8rem] lg:translate-y-[-10.8rem]',
    inputStyling: 'lg:-translate-x-44',
    tagStyling: 'lg:-translate-x-40',
    Icon: PhysDef,
  },
  {
    label: 'HP',
    styling: 'translate-x-[-8.8rem] lg:translate-x-[-15.25rem]',
    inputStyling: 'lg:-translate-x-44',
    tagStyling: 'lg:-translate-x-40',
    Icon: HP,
  },
  {
    label: 'EVA',
    styling:
      'translate-x-[-6.2rem] translate-y-[6.2rem] lg:translate-x-[-10.8rem] lg:translate-y-[10.8rem]',
    inputStyling: 'lg:-translate-x-44',
    tagStyling: 'lg:-translate-x-40',
    Icon: Eva,
  },
  {
    label: 'PHYS ATK',
    styling: 'translate-y-[8.8rem] lg:translate-y-[15.2rem]',
    inputStyling: 'lg:translate-y-36',
    tagStyling: 'lg:translate-y-28',
    Icon: PhysAtk,
  },
  {
    label: 'Accuracy',
    styling:
      'translate-x-[6.2rem] translate-y-[6.2rem] lg:translate-x-[10.8rem] lg:translate-y-[10.8rem]',
    inputStyling: 'lg:translate-x-44',
    tagStyling: 'lg:translate-x-40',
    Icon: Accuracy,
  },
  {
    label: 'MP',
    styling: 'translate-x-[8.8rem] lg:translate-x-[15.25rem]',
    inputStyling: 'lg:translate-x-44',
    tagStyling: 'lg:translate-x-40',
    Icon: MP,
  },
  {
    label: 'Spell DEF',
    styling:
      'translate-x-[6.2rem] translate-y-[-6.2rem] lg:translate-x-[10.8rem] lg:translate-y-[-10.8rem]',
    inputStyling: 'lg:translate-x-44',
    tagStyling: 'lg:translate-x-40',
    Icon: SpellDef,
  },
]
