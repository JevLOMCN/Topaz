'use client'

import { type Level } from '@/app/xp/page'
import { SettingsAtom } from '@/atoms/Settings'
import { XPCalculatorAtom, XPExtension } from '@/atoms/XPCalculator'
import XPPerLevel from '@/data/XPPerLevel'
import { getPercentage } from '@/utils/index'
import humanizeDuration from 'humanize-duration'
import { useAtomValue } from 'jotai'
import moment from 'moment'
import { useEffect, useState } from 'react'

export default function SquareAndPeakResult() {
  const [isLoading, setIsLoading] = useState(true)
  const { xpPerMinute, manualCalculation, levels, percentages } =
    useAtomValue(XPCalculatorAtom)
  const { magicSquare, secretPeak } = useAtomValue(XPExtension)
  const settings = useAtomValue(SettingsAtom)

  const language = settings?.language ?? 'en'

  const LevelGap =
    levels.initial && levels.final
      ? XPPerLevel[(Number(levels.final) - 1) as Level]
      : ''

  const XPToTargetLevel = LevelGap
    ? getPercentage(
        LevelGap,
        100 - Number(levels.initialPercentage ?? percentages.final)
      )
    : undefined

  const XPPerMinute = xpPerMinute ?? manualCalculation.xpPerMinute

  const XPPerReset =
    magicSquare.xpPerRun * magicSquare.tickets +
    secretPeak.xpPerRun * secretPeak.tickets

  const ticketsXPPerMinute = XPPerReset / 1440 // XP per day divided by minutes per day

  const resultInMinutes =
    (XPToTargetLevel ?? 0) / (ticketsXPPerMinute + (XPPerMinute ?? 0))

  useEffect(() => {
    setIsLoading(false)
  }, [])

  return (
    <p className={'rounded-md bg-primary-600 px-4 py-2 text-sm text-white'}>
      {isLoading ? (
        <span className="my-1 flex h-4 w-full max-w-[6rem] animate-pulse rounded-full bg-primary-500 text-transparent">
          Loading...
        </span>
      ) : (
        humanizeDuration(
          moment.duration(resultInMinutes, 'minutes').asMilliseconds(),
          { round: true, language }
        )
      )}
    </p>
  )
}
