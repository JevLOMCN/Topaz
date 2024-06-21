import { ShardsInventoryAtom } from '@/atoms/MagicSquareShop'
import { useAtom } from 'jotai'
import ItemFrame from '../crafting/ItemFrame'

export default function ShardItem({
  item,
  rarity,
}: {
  item: shardsType
  rarity: 'Epic' | 'Rare' | 'Uncommon'
}) {
  const [shardsInventory, setShardsInventory] = useAtom(ShardsInventoryAtom)

  return (
    <li className="flex flex-col items-center gap-4 px-2 pt-4">
      <ItemFrame item={item as ItemTypes} rarity={rarity} />

      <label
        className={
          'flex w-20 flex-col overflow-hidden rounded bg-primary-600 px-2 py-1 text-xs text-white sm:text-base'
        }
      >
        <input
          className={
            'flex w-full appearance-none items-center justify-center bg-transparent text-center text-sm font-semibold outline-none transition-colors duration-300 selection:bg-primary-800 placeholder:text-neutral-200/70 sm:text-base'
          }
          value={shardsInventory[item][rarity]}
          onClick={(e) => e.currentTarget.setSelectionRange(0, 25, 'backward')}
          onChange={(e) =>
            setShardsInventory((prev) => ({
              ...prev,
              [item]: {
                ...prev[item],
                [rarity]: Number(e.target.value.replace(/\D/g, '')),
              },
            }))
          }
        />
      </label>
    </li>
  )
}
