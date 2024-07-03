import { InventoryAtom } from '@/atoms/Inventory'
import { cn } from '@/utils/classNames'
import { useAtom } from 'jotai'
import Image from 'next/image'
import { type HTMLAttributes } from 'react'

export default function BasicItemComponent({
  item,
  rarity,
  children,
  ...props
}: ItemComponentProps) {
  const [inventory, setInventory] = useAtom(InventoryAtom)

  const modifyInventory = ({
    item,
    value,
  }: {
    item: NonRarityItems
    value: number
  }) => {
    setInventory((prev) => ({
      ...prev,
      [item]: value,
    }))
  }

  return (
    <ul>
      <label
        className="flex w-[8.5rem] cursor-pointer flex-col items-center gap-3"
        {...props}
      >
        <div
          className={cn(
            'relative flex h-[8.5rem] w-[8.5rem] items-center justify-center rounded-lg border-2 border-[#272043] bg-default-frame'
          )}
        >
          <Image
            src={`/items/${item}.webp`}
            alt=""
            width={102}
            height={102}
            className={'h-[6.375rem] w-[6.375rem] object-contain'}
          />
        </div>
        <div className={'flex w-full flex-col'}>
          <input
            type="number"
            value={inventory[item]}
            onChange={(e) => {
              modifyInventory({ item, value: e.currentTarget.valueAsNumber })
            }}
            className={
              'flex w-full appearance-none items-center justify-center gap-1.5 rounded bg-primary-600 px-3 py-1 font-medium text-white outline-none'
            }
          />
        </div>
      </label>
    </ul>
  )
}

type ItemComponentProps = {
  item: NonRarityItems
  rarity: RarityTypes | 'Default'
} & HTMLAttributes<HTMLLabelElement>
