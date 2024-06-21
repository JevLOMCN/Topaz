import { InventoryAtom } from '@/atoms/Inventory'
import Tooltip from '@/components/ToolTip'
import ItemFrame from '@/components/crafting/ItemFrame'
import BaseResourceCost from '@/data/BaseResouceCost'
import { formatItemName, rarityRegex } from '@/utils/index'
import { useAtom } from 'jotai'
import FragmentRecipe from './FragmentRecipe'
import InventoryInput from './InventoryInput'

export default function TableCostFragment({
  name,
  rarity,
  cost,
}: {
  name: string
  rarity: RarityTypes
  cost: number
}) {
  const [inventory, setInventory] = useAtom(InventoryAtom)

  const formattedName = formatItemName(name)

  const updateInventoryValue = (
    e: React.ChangeEvent<HTMLInputElement>,
    isTraddable: boolean
  ) =>
    setInventory((prev) => ({
      ...prev,
      [formattedName]: {
        ...prev[formattedName],
        [rarity]: {
          ...prev[formattedName][rarity],
          [isTraddable ? 'traddable' : 'nonTraddable']: Number(e.target.value),
        },
      },
    }))

  let itemRecipe: Record<string, number> = {}
  if (['Legendary', 'Epic'].includes(rarity)) {
    itemRecipe =
      BaseResourceCost[
        name.replace(rarityRegex, '') as keyof typeof BaseResourceCost
      ]?.[rarity as Exclude<RarityTypes, 'Rare' | 'Uncommon' | 'Common'>]
  }

  const itemHasRecipe = Object.keys(itemRecipe ?? {}).length > 0

  return (
    <Tooltip.Wrapper disableHoverableContent>
      <Tooltip.Trigger disabled={Object.keys(itemRecipe ?? {}).length < 1}>
        <td className="w-40 gap-4 px-2 pt-4" align="center">
          <ItemFrame
            className="mb-4"
            item={formattedName}
            rarity={rarity}
            size={'md'}
          />

          <div className="flex w-20 flex-col overflow-hidden rounded bg-primary-600 text-white transition-[width] focus-within:w-36">
            <InventoryInput
              isTraddable
              onChange={(e) => updateInventoryValue(e, true)}
              value={inventory[formattedName][rarity].traddable}
              className="border-b-2 border-b-primary-400"
            />
            <InventoryInput
              onChange={(e) => updateInventoryValue(e, false)}
              value={inventory[formattedName][rarity].nonTraddable}
            />
          </div>
        </td>
      </Tooltip.Trigger>
      {itemHasRecipe ? (
        <FragmentRecipe itemRecipe={itemRecipe} cost={cost} />
      ) : (
        <></>
      )}
    </Tooltip.Wrapper>
  )
}
