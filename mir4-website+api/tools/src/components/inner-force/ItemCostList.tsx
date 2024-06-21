'use client'
import Tooltip from '@/components/ToolTip'
import ItemFrame from '@/components/crafting/ItemFrame'
import {
  AllowedInventoryItemTypes,
  extractItemRarity,
  formatItemName,
  getReadableNumber,
} from '@/utils/index'
import millify from 'millify'

import { ItemRarities } from '../constitution/CostInformation'

function divideByRarity(list: Array<[string, number]>) {
  const resultList: Array<Array<[string, number]>> = [[], [], [], [], []]

  for (const [item, value] of Object.values(list)) {
    const itemRarity = extractItemRarity(item)
    const rarityIndex = ItemRarities.indexOf(itemRarity)

    if (
      !AllowedInventoryItemTypes.includes(formatItemName(item)) ||
      item === 'energy' ||
      item === 'copper'
    ) {
      continue
    }

    if (rarityIndex === 5) continue // 'Default' rarity

    resultList[rarityIndex].push([item, value])
  }

  return resultList.reverse().filter((subArray) => subArray.length > 0)
}

export default function ItemCostList({
  sortedResult,
}: {
  sortedResult: Array<[string, number]>
}) {
  const itemsList = divideByRarity(sortedResult)

  return (
    <div className="flex w-max flex-col justify-start gap-4">
      {itemsList.map((rarityArray, index) => (
        <ul className="flex items-center gap-4" key={index}>
          {rarityArray.map(([name, value]) => {
            const formattedName = formatItemName(name)
            const itemRarity = extractItemRarity(name)

            if (
              !AllowedInventoryItemTypes.includes(formattedName) ||
              formattedName === ('energy' as ItemWithRarity) ||
              formattedName === ('copper' as ItemWithRarity)
            ) {
              return <></>
            }

            return (
              <li key={name} className="flex flex-col items-center gap-2">
                <ItemFrame item={formattedName} rarity={itemRarity} />
                <Tooltip.Wrapper>
                  <Tooltip.Trigger
                    asChild={false}
                    aria-label="See detailed amount"
                    className="w-max rounded bg-primary-600 px-3 py-1 text-center text-sm font-medium text-white"
                  >
                    {millify(value)}
                  </Tooltip.Trigger>
                  <Tooltip.Content>{getReadableNumber(value)}</Tooltip.Content>
                </Tooltip.Wrapper>
              </li>
            )
          })}
        </ul>
      ))}
    </div>
  )
}
