'use client'

import { ConquestsAtom } from '@/atoms/Conquests'
import Chevron from '@/icons/Chevron'
import { useAtom } from 'jotai'
import { useTranslation } from '../../../public/locales/client'

export default function ConquestStageHandler() {
  const [{ tower, stage }, setConquests] = useAtom(ConquestsAtom)
  const { t } = useTranslation()

  const maxStage = tower === 'Sanctuary of Hydra' ? 12 : 24

  const handleStageChange = (value: number) => {
    value = value - 1

    if (value < 0) value = 0
    if (value > maxStage) value = maxStage
    if (Number.isNaN(value)) return

    setConquests((prev) => ({ ...prev, stage: value }))
  }

  return (
    <div className="mb-4 flex items-center justify-between gap-4 rounded-full bg-primary-600 px-4 py-2 lg:mb-2">
      <button
        className="rounded-full p-1 transition-[colors,opacity] hover:bg-primary-500 disabled:opacity-0"
        onClick={() =>
          setConquests((prev) => ({
            ...prev,
            stage: prev.stage === 0 ? 0 : prev.stage - 1,
          }))
        }
        disabled={stage === 0}
      >
        <Chevron />
      </button>
      <span className="flex items-center gap-2">
        {t('Stage')}{' '}
        <input
          className={
            'flex w-8 appearance-none rounded-md bg-primary-500/50 px-1 text-center text-sm font-normal outline-none selection:bg-primary-800 placeholder:text-neutral-200/70 sm:text-base'
          }
          type="number"
          value={String(stage + 1)}
          onChange={(e) => handleStageChange(e.currentTarget.valueAsNumber)}
        />
      </span>
      <button
        className="rounded-full p-1 transition-[colors,opacity] hover:bg-primary-500 disabled:opacity-0"
        onClick={() =>
          setConquests((prev) => ({
            ...prev,
            stage: stage >= maxStage ? maxStage : prev.stage + 1,
          }))
        }
        disabled={stage >= maxStage}
      >
        <Chevron className="rotate-180" />
      </button>
    </div>
  )
}
