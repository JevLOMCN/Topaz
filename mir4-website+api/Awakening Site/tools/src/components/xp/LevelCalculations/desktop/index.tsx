'use client'

import { type Level } from '@/app/xp/page'
import { XPCalculatorAtom, XPInvalidInput } from '@/atoms/XPCalculator'
import XPPerLevel from '@/data/XPPerLevel'
import { cn } from '@/utils/classNames'
import { getPercentage, getReadableNumber } from '@/utils/index'
import humanizeDuration from 'humanize-duration'
import { useAtomValue } from 'jotai'
import millify from 'millify'
import moment from 'moment'
import { useTranslation } from '../../../../../public/locales/client'

export default function DesktopLevelCalculations() {
  const {
    levels,
    percentages,
    xpPerMinute = 0,
    manualCalculation,
  } = useAtomValue(XPCalculatorAtom)
  const invalidInput = useAtomValue(XPInvalidInput)
  const { t, i18n } = useTranslation()

  let XPToDesiredLevel = 0

  if (!!levels.initial && !!levels.final) {
    const levelDistance =
      !!levels.initial && !!levels.final ? levels.final - levels.initial : 0

    for (let i = 0; i < levelDistance; i++) {
      XPToDesiredLevel += XPPerLevel[(Number(levels.final) - i - 1) as Level]
    }
  }

  const XPToNextLevel = XPToDesiredLevel
    ? getPercentage(
        XPPerLevel[Number(levels.initial) as Level],
        percentages.final ?? levels.initialPercentage
      )
    : undefined

  const TotalXPNeeded =
    XPToDesiredLevel && XPToNextLevel
      ? XPToDesiredLevel - XPToNextLevel
      : undefined

  const successfulInput = !!levels.initial && !!levels.final

  const XPPerMinute = xpPerMinute || (manualCalculation.xpPerMinute ?? 0)

  return (
    <div className="hidden h-full w-full grid-rows-[1fr_4px_1fr] flex-col items-center gap-4 pt-11 md:grid">
      <p className="mt-auto px-4 text-center text-xl font-medium text-white">
        {TotalXPNeeded && !invalidInput
          ? `${getReadableNumber(TotalXPNeeded)} (${millify(TotalXPNeeded)})`
          : ''}
      </p>

      <span
        className={cn(
          'flex h-1 w-full rounded-full bg-primary-500 transition-colors',
          { 'bg-white': successfulInput }
        )}
      />

      <div className="flex flex-col items-center gap-2 px-4">
        <p className="text-center text-base font-light text-white">
          <b className="font-bold">
            {TotalXPNeeded && !invalidInput
              ? humanizeDuration(
                  moment
                    .duration(TotalXPNeeded / XPPerMinute, 'minutes')
                    .asMilliseconds(),
                  { round: true, language: i18n.language }
                )
              : 0}
          </b>{' '}
          {t('to level up')}
        </p>

        <p className="text-center text-base font-light text-white">
          {t('You are earning')}{' '}
          <b className="font-bold">
            {invalidInput ? 0 : getReadableNumber(XPPerMinute * 5)}
          </b>{' '}
          {t('XP every')} <b className="font-bold">5 {t('minutes')}</b>
        </p>
      </div>
    </div>
  )
}
