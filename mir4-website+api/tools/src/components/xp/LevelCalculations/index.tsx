'use client'

import { type Level } from '@/app/xp/page'
import { XPCalculatorAtom } from '@/atoms/XPCalculator'
import { formatLevel } from '@/utils/index'
import { useAtom } from 'jotai'
import { useTranslation } from '../../../../public/locales/client'
import LevelFrame from './LevelFrame'
import DesktopLevelCalculations from './desktop'
import MobileLevelCalculations from './mobile'

export default function LevelCalculations() {
  const [{ levels, percentages }, setXPCalc] = useAtom(XPCalculatorAtom)
  const { t } = useTranslation()

  const successfulInput = !!levels.initial && !!levels.final

  return (
    <section
      id="experienceLevels"
      className="relative flex w-full max-w-3xl flex-col items-center justify-between gap-4"
    >
      <div className="flex w-full items-end justify-between gap-4 md:items-center">
        <LevelFrame
          label={t('Current Level')}
          placeholder="100"
          percentage={`${levels.initialPercentage ?? percentages.final ?? 0}%`}
          value={String(levels.initial ?? '')}
          onChange={(value) => {
            setXPCalc((prev) => ({
              ...prev,
              levels: {
                ...prev.levels,
                initial: formatLevel(value),
              },
            }))
          }}
          onBlur={() => {
            !levels.final &&
              setXPCalc((prev) => ({
                ...prev,
                levels: {
                  ...prev.levels,
                  final: prev.levels.initial
                    ? ((Number(prev.levels.initial) + 1) as Level)
                    : prev.levels.final,
                },
              }))
          }}
          success={successfulInput}
        />

        <DesktopLevelCalculations />

        <LevelFrame
          label={t('Desired Level')}
          placeholder="100"
          value={String(levels.final ?? '')}
          onChange={(value) => {
            setXPCalc((prev) => ({
              ...prev,
              levels: {
                ...prev.levels,
                final: formatLevel(value),
              },
            }))
          }}
          success={successfulInput}
        />
      </div>

      <MobileLevelCalculations />
    </section>
  )
}
