'use client'

import { ConquestsAtom } from '@/atoms/Conquests'
import ConquestTowersData from '@/data/ConquestTowerData'
import humanizeDuration from 'humanize-duration'
import { useAtomValue } from 'jotai'
import moment from 'moment'
import { useTranslation } from '../../../public/locales/client'

export default function ConquestHeader() {
  const { t, i18n } = useTranslation()
  const { tower, stage } = useAtomValue(ConquestsAtom)

  const currentTower = ConquestTowersData[tower].Steps[stage]
  const hasPreviousStage = stage > 0

  return (
    <header className="flex w-full flex-col lg:flex-row lg:justify-between">
      <div className="flex flex-col gap-1 lg:gap-2">
        <h1 className="text-2xl font-semibold lg:text-4xl">{t(tower)}</h1>
        <p className="text-base font-normal lg:text-xl">
          {`${t('Stage')} ${stage} > `}
          <span className="text-[#62CA63]">
            {t('Stage')} {stage + 1}
          </span>
        </p>
      </div>

      <div className="flex flex-col gap-1 lg:gap-2 lg:text-end">
        <h2 className="text-base font-normal lg:text-2xl lg:font-bold">
          {humanizeDuration(
            moment
              .duration(currentTower.UpgradeTime, 'seconds')
              .asMilliseconds(),
            {
              round: true,
              language: i18n.language,
            }
          )}
        </h2>
        <p className="text-sm font-medium">
          <b className="font-bold lg:text-end">
            {t('Power Score')}
            {' - '}
          </b>
          {`${
            hasPreviousStage
              ? ConquestTowersData[tower].Steps[stage - 1].Power
              : 0
          } > `}
          <span className="text-[#62CA63]">{currentTower.Power}</span>
        </p>
      </div>
    </header>
  )
}
