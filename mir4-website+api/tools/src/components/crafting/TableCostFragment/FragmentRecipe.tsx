import Tooltip from '@/components/ToolTip'
import {
  extractItemRarity,
  formatItemName,
  getReadableNumber,
} from '@/utils/index'
import ItemFrame from '../ItemFrame'

export default function FragmentRecipe({
  itemRecipe,
  cost,
}: {
  itemRecipe: Record<string, number>
  cost: number
}) {
  return (
    <Tooltip.Content
      className="flex gap-4 pointer-events-none border border-white/10 bg-primary-400/5 p-2 backdrop-blur-lg data-[state=closed]:animate-contentHide data-[state=open]:animate-contentShow"
      sideOffset={16}
      alignOffset={16}
      side="bottom"
    >
      {Object.entries(itemRecipe ?? {}).map(([subRecipe, amount]) => {
        const rarity = extractItemRarity(subRecipe)
        const formattedSubRecipeName = formatItemName(subRecipe)

        const totalCost = amount * cost

        return (
          <ul key={subRecipe} className="flex flex-col gap-4">
            <li className="flex flex-col items-center gap-2">
              <ItemFrame
                item={formattedSubRecipeName}
                rarity={rarity}
                size={'md'}
              />

              <span
                className={
                  'flex w-full items-center justify-center gap-1.5 rounded bg-primary-600 px-3 py-1 text-xs font-medium text-white outline-none data-[hasItems=true]:bg-primary-450'
                }
                data-hasItems={cost <= 0}
              >
                {totalCost > 0 ? getReadableNumber(totalCost) : 0}
              </span>
            </li>
          </ul>
        )
      })}
    </Tooltip.Content>
  )
}
