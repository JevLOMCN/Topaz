import { InventoryAtom } from '@/atoms/Inventory'
import Tooltip from '@/components/ToolTip'
import { formatItemName, getReadableNumber } from '@/utils/index'
import { useAtom } from 'jotai'
import millify from 'millify'
import ItemFrame from '../ItemFrame'
import InventoryInput from './InventoryInput'

export default function AdditionalCostFragment({
  name,
  cost,
  rarity,
}: {
  name: NonRarityItems
  cost: number
  rarity: RarityTypes | 'Default'
}) {
  const [inventory, setInventory] = useAtom(InventoryAtom)

  const formattedName = formatItemName(name) as NonRarityItems

  const updateInventoryValue = (e: React.ChangeEvent<HTMLInputElement>) =>
    setInventory((prev) => ({
      ...prev,
      [formattedName]: Number(e.target.value),
    }))

  if (cost <= 0) cost = 0

  return (
    <li className="flex flex-col gap-2">
      <ItemFrame item={name} rarity={rarity} size={'md'} />

      <div className="flex w-20 flex-col overflow-hidden rounded bg-primary-600 text-white transition-[width] focus-within:w-36">
        <Tooltip.Wrapper delayDuration={0}>
          <Tooltip.Trigger>
            <span
              className={
                'flex w-full items-center justify-center gap-1.5 rounded-t border-b-2 border-b-primary-400 bg-primary-600 px-3 py-1 text-xs font-medium text-white outline-none data-[hasItems=true]:bg-primary-450 sm:text-base'
              }
              data-hasItems={cost === 0}
            >
              {millify(cost)}
            </span>
          </Tooltip.Trigger>
          <Tooltip.Content sideOffset={6}>
            {getReadableNumber(cost)}
          </Tooltip.Content>
        </Tooltip.Wrapper>

        <InventoryInput
          isInventory
          onChange={updateInventoryValue}
          value={inventory[formattedName]}
        />
      </div>
    </li>
  )
}
