'use client'

import { InnerForceBloodsAtom, InnerForceTabAtom } from '@/atoms/InnerForce'
import { cn } from '@/utils/classNames'
import {
  getBloodSetObject,
  getMaxIFTier,
  getValidBloodValue,
  toCamelCase,
} from '@/utils/index'
import { useAtom, useAtomValue } from 'jotai'
import Image from 'next/image'
import { useMemo } from 'react'
import { useTranslation } from '../../../public/locales/client'

export default function TabButton({
  tabName,
  className,
  ...props
}: {
  tabName: BloodSets
} & Exclude<React.HTMLAttributes<HTMLButtonElement>, 'onClick'>) {
  const [tab, setTab] = useAtom(InnerForceTabAtom)
  const bloodObject = useAtomValue(InnerForceBloodsAtom)
  const { t } = useTranslation()

  const targetedObject = useMemo(
    () => getBloodSetObject(tabName, bloodObject),
    [tabName, bloodObject]
  )

  const minLevel = Math.min(
    ...Object.values(targetedObject).map((values) => values.initial)
  )
  const maxLevel = Math.max(
    ...Object.values(targetedObject).map((values) => values.final)
  )
  const hasErrors = Object.values(targetedObject).some(
    (values) =>
      values.final < values.initial ||
      values.initial !== getValidBloodValue(tabName, values.initial) ||
      values.final !== getValidBloodValue(tabName, values.final)
  )
  const currentTier = Math.min(
    Math.floor(minLevel / 5) + 1,
    getMaxIFTier[tabName]
  )
  const nextTier = Math.min(
    Math.floor((maxLevel - 1) / 5) + 1,
    getMaxIFTier[tabName]
  )

  return (
    <button
      className={cn(
        'relative flex h-20 w-20 shrink-0 flex-col justify-center rounded border-2 border-transparent bg-primary-600/50 px-3 py-2 text-white transition-colors hover:border-primary-400 hover:bg-primary-600/80 sm:h-28 sm:w-28 xl:h-20 xl:w-52 xl:flex-row xl:items-center xl:justify-between',
        { 'border-primary-400 bg-primary-600/80': tab === tabName },
        { 'border-csred-400 bg-csred-400/20': hasErrors },
        className
      )}
      onClick={() => setTab(tabName)}
      {...props}
    >
      <Image
        src={`/items/${toCamelCase(tabName)}.webp`}
        alt={tabName}
        width={70}
        height={58}
        className="w-12 object-contain sm:w-[4.5rem]"
      />
      <div className="hidden flex-col justify-between text-center text-xs font-medium xl:flex xl:h-full xl:text-end">
        <p>
          Tier {currentTier}{' '}
          {nextTier > currentTier && (
            <span className="text-success-400">
              {'>'} Tier {nextTier}
            </span>
          )}
        </p>
        <p className="max-w-[6rem]">{t(tabName)}</p>
      </div>
    </button>
  )
}
