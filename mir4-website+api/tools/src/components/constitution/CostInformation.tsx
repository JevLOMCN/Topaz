'use client'

import { constitutionUpgradeAtom, statusLevelsAtom } from '@/atoms/Constitution'
import { SettingsAtom } from '@/atoms/Settings'
import ConstitutionData from '@/data/ConstituionData'
import ConstitutionMasteryData from '@/data/ConstitutionMasteryData'
import {
  getReadableNumber,
  prepareItemForDisplay,
  sumObjects,
  toCamelCase,
} from '@/utils/index'
import { useAtom, useAtomValue, useSetAtom } from 'jotai'
import millify from 'millify'
import { useEffect, useState } from 'react'
import { useTranslation } from '../../../public/locales/client'
import Tooltip from '../ToolTip'
import ItemFrame from '../crafting/ItemFrame'
import Checkbox from '../shared/Checkbox'

export const ItemRarities = [
  'Legendary',
  'Epic',
  'Rare',
  'Uncommon',
  'Common',
  'Default',
] as const

export default function ConstitutionCostInformation() {
  const levels = useAtomValue(statusLevelsAtom)
  const setConstUpgrade = useSetAtom(constitutionUpgradeAtom)
  const [{ showConstitutionPromotion }, setSettings] = useAtom(SettingsAtom)
  const [cost, setCost] = useState<ItemForDisplay[]>([])
  const { t } = useTranslation()

  useEffect(() => {
    const result = statusKeys.map((key) => {
      const iteration = Array(levels[key].to - levels[key].from)
        .fill(0)
        .map((_, i) => i + levels[key].from)

      return iteration.map((i) => ConstitutionData[key][i])
    })

    const results = sumObjects(result.flat())

    if ('Level' in results) delete results.Level
    // eslint-disable-next-line @typescript-eslint/no-dynamic-delete
    statusKeys.forEach((key) => delete results[key])

    const mergedResults = prepareItemForDisplay(results)
    let masteryCost: ItemForDisplay[] = []

    const fromLevels = Object.values(levels).map((values) => values.from)
    const toLevels = Object.values(levels).map((values) => values.to)

    const minLevel = Math.min(...fromLevels)
    const maxLevel = Math.max(...toLevels)

    const masteryIteration = Array(
      Math.round(maxLevel / 5) - Math.round(minLevel / 5)
    )
      .fill(0)
      .map((_, i) => i + Math.round(minLevel / 5))

    setConstUpgrade({ masteryIteration })

    if (showConstitutionPromotion) {
      const masteryData = sumObjects(
        masteryIteration.map((i) =>
          i > 19 ? {} : ConstitutionMasteryData[i + 1]?.Cost
        )
      )
      const CopperCost = masteryIteration
        .map((i) => ConstitutionMasteryData[i].Copper as number)
        .reduce((acc, cur) => acc + cur, 0)
      masteryCost = prepareItemForDisplay(masteryData)
      const copperIndex = mergedResults.findIndex(
        (item) => item.name === 'Copper'
      )
      if (copperIndex && mergedResults?.[copperIndex]?.amount) {
        mergedResults[copperIndex].amount += CopperCost
      }
    }

    setCost([...mergedResults, ...masteryCost])
  }, [JSON.stringify(levels), showConstitutionPromotion])

  return (
    <div className="flex w-full flex-col gap-4">
      <header className="flex w-full items-center justify-between">
        <h1 className="text-3xl font-bold text-white">{t('Cost')}</h1>
        <Checkbox
          label={t('Show promotion cost')}
          onCheckedChange={(checked) =>
            setSettings((prev) => ({
              ...prev,
              showConstitutionPromotion: checked as boolean,
            }))
          }
          checked={showConstitutionPromotion}
        />
      </header>
      <ul className="flex w-full flex-wrap gap-4">
        {orderByRarity(cost).map(({ name, rarity, amount }, index) => (
          <li key={index} className="flex flex-col items-center gap-2">
            <ItemFrame item={toCamelCase(name) as ItemTypes} rarity={rarity} />
            <Tooltip.Wrapper>
              <Tooltip.Trigger
                asChild={false}
                aria-label="See detailed amount"
                className="w-max rounded bg-primary-600 px-3 py-1 text-center text-sm font-medium text-white"
              >
                {millify(amount)}
              </Tooltip.Trigger>
              <Tooltip.Content>{getReadableNumber(amount)}</Tooltip.Content>
            </Tooltip.Wrapper>
          </li>
        ))}
      </ul>
    </div>
  )
}

const statusKeys = [
  'PHYS DEF',
  'Spell DEF',
  'HP',
  'MP',
  'EVA',
  'Accuracy',
  'PHYS ATK',
] as const

const orderByRarity = <T extends { rarity: RarityTypes | 'Default' }>(
  data: T[]
): T[] => {
  return data.sort(
    (obj1, obj2) =>
      ItemRarities.indexOf(obj1.rarity) - ItemRarities.indexOf(obj2.rarity)
  )
}
