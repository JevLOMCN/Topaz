import ItemFrame from '@/components/crafting/ItemFrame'
import { extractItemRarity, formatItemName } from '@/utils/index'

export default function ItemCost({
  item,
  amount,
}: {
  item: string
  amount: number
}) {
  return (
    <li key={item} className="flex flex-col items-center gap-2">
      <ItemFrame
        item={formatItemName(item)}
        rarity={extractItemRarity(item)}
        size="sm"
      />
      <span className="w-max rounded bg-primary-600 px-3 py-1 text-center text-sm font-medium text-white">
        {amount}
      </span>
    </li>
  )
}
