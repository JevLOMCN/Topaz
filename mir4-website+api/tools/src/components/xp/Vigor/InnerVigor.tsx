'use client'

import { XPCalculatorAtom, XPExtension } from '@/atoms/XPCalculator'
import Input from '@/components/Input'
import XPPerLevel from '@/data/XPPerLevel'
import { useAtom } from 'jotai'
import millify from 'millify'
import { useEffect, useState } from 'react'

export default function InnerVigor() {
  const [isLoading, setIsLoading] = useState(true)
  const [{ levels, xpPerMinute = 0, manualCalculation }] =
    useAtom(XPCalculatorAtom)
  const [{ vigor }, setExtension] = useAtom(XPExtension)

  const XPPerMinute = xpPerMinute || (manualCalculation.xpPerMinute ?? 0)

  const acquiredXPWithVigor = XPPerMinute * 60 * vigor

  const acquiredPercentage = levels.initial
    ? ((acquiredXPWithVigor / XPPerLevel[`${levels.initial}`]) * 100).toFixed(4)
    : '0.0000'

  useEffect(() => {
    setIsLoading(false)
  }, [])

  return (
    <div className="flex flex-col sm:flex-row items-center gap-2">
      <Input
        className={'sm:max-w-[5rem] shrink-0 [&>div]:py-1'}
        suffix="h"
        placeholder="duration"
        type='number'
        onChange={(e) => {
          setExtension((prev) => ({
            ...prev,
            vigor:
              Number.isInteger(Number(e.currentTarget.valueAsNumber)) && Number(e.currentTarget.valueAsNumber) < 1000
                ? Number(e.currentTarget.valueAsNumber)
                : prev.vigor,
          }))
        }}
        value={vigor}
      />

      <div className="flex w-full items-baseline justify-end gap-3 truncate rounded-md bg-primary-600 px-3 py-1 text-sm md:text-base text-white">
        {isLoading ? (
          <>
            <span className="my-1 flex h-4 w-full max-w-[3rem] animate-pulse rounded-full bg-primary-500 text-transparent">
              Loading...
            </span>
            <span className="my-1 flex h-4 w-full max-w-[5rem] animate-pulse rounded-full bg-primary-500 text-transparent">
              Loading...
            </span>
          </>
        ) : (
          <>
            <span className="inline-flex gap-2">
              <p className="truncate">{millify(acquiredXPWithVigor)} </p>
              <b className="shrink-0 font-bold">XP</b>
            </span>
            <span className="inline-flex gap-2">
              <p className="truncate">{acquiredPercentage}</p>
              <b className="shrink-0 font-bold">%</b>
            </span>
          </>
        )}
      </div>
    </div>
  )
}
