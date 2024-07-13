'use client'

import { type Level } from '@/app/xp/page'
import { XPCalculatorAtom, XPInvalidInput } from '@/atoms/XPCalculator'
import XPPerLevel from '@/data/XPPerLevel'
import { cn } from '@/utils/classNames'
import { getPercentage, getReadableNumber } from '@/utils/index'
import humanizeDuration from 'humanize-duration'
import { useAtomValue } from 'jotai'
import moment from 'moment'
import { useTranslation } from '../../../../../public/locales/client'

export default function MobileLevelCalculations() {
  const {
    levels,
    percentages,
    xpPerMinute = 0,
    manualCalculation,
  } = useAtomValue(XPCalculatorAtom)
  const invalidInput = useAtomValue(XPInvalidInput)
  const { t, i18n } = useTranslation()

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

  const successfulInput = !!levels.initial && !!levels.final

  const XPPerMinute = xpPerMinute || (manualCalculation.xpPerMinute ?? 0)

  return (
    <div
      className={cn(
        'relative mb-16 flex h-6 w-full flex-col items-center md:hidden'
      )}
    >
      <p className="min-h-[1.5rem] -translate-y-4 px-2 text-center text-base font-medium text-white">
        {XPToTargetLevel && !invalidInput
          ? getReadableNumber(XPToTargetLevel)
          : ''}
      </p>

      <div
        className={cn(
          'absolute flex h-6 w-[calc(100%-6rem)] flex-col rounded-b-lg border-4 border-t-0 border-primary-500 transition-colors',
          { 'border-white': successfulInput }
        )}
      ></div>

      <div className="flex translate-y-4 flex-col items-center gap-2 text-sm">
        <p className="text-center font-light text-white">
          <b className="font-bold">
            {XPToTargetLevel && !invalidInput
              ? humanizeDuration(
                  moment
                    .duration(XPToTargetLevel / XPPerMinute, 'minutes')
                    .asMilliseconds(),
                  { round: true, language: i18n.language }
                )
              : 0}
          </b>{' '}
          {t('to level up')}
        </p>

        <p className="text-center font-light text-white">
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
