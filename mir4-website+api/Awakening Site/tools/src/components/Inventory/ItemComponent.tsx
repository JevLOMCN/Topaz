import { InventoryAtom } from '@/atoms/Inventory'
import Balance from '@/icons/Balance'
import { cn } from '@/utils/classNames'
import { ItemRarities } from '@/utils/index'
import { useAtom } from 'jotai'
import Image from 'next/image'
import { useState, type HTMLAttributes } from 'react'

export default function ItemComponent({
  item,
  children,
  ...props
}: ItemComponentProps) {
  const [inventory, setInventory] = useAtom(InventoryAtom)
  const [selectedRarity, setRarity] = useState<RarityTypes>('Epic')

  const modifyInventory = ({
    item,
    value,
    type,
  }: {
    item: ItemWithRarity
    value: number
    type: 'traddable' | 'nonTraddable'
  }) => {
    setInventory((prev) => ({
      ...prev,
      [item]: {
        ...prev[item],
        [selectedRarity]: {
          ...prev[item][selectedRarity],
          [type]: value,
        },
      },
    }))
  }

  return (
    <ul className="flex justify-center gap-2 md:justify-start">
      <label
        className="flex w-[8.5rem] cursor-pointer flex-col items-center gap-3"
        {...props}
      >
        <div
          className={cn(
            'relative flex h-[8.5rem] w-[8.5rem] items-center justify-center rounded-lg border-2',
            rarityVariantStyles[selectedRarity]
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
          <label className="flex w-full cursor-text items-center justify-center gap-1.5 rounded-t border-b-2 border-b-primary-400 bg-primary-600 px-2 py-1">
            <Balance className="inline-block h-5 w-5 shrink-0 fill-white" />
            <input
              className="w-full bg-transparent font-medium text-white outline-none"
              type="number"
              value={inventory[item][selectedRarity].traddable}
              onChange={(e) => {
                modifyInventory({
                  item,
                  value: e.currentTarget.valueAsNumber,
                  type: 'traddable',
                })
              }}
            />
          </label>
          <input
            className="flex w-full appearance-none items-center justify-center gap-1.5 rounded-b bg-primary-600 px-3 py-1 font-medium text-white outline-none"
            type="number"
            value={inventory[item][selectedRarity].nonTraddable}
            onChange={(e) => {
              modifyInventory({
                item,
                value: e.currentTarget.valueAsNumber,
                type: 'nonTraddable',
              })
            }}
          />
        </div>
      </label>

      <div className="flex flex-col gap-1">
        {ItemRarities.map((rarity) => (
          <button
            key={rarity}
            data-active={selectedRarity === rarity}
            className="flex w-full items-center justify-start gap-1.5 rounded px-2 py-1 text-xs font-semibold text-white transition-colors hover:bg-primary-400/20 data-[active=true]:bg-primary-400/40"
            onClick={() => setRarity(rarity)}
          >
            <div
              className={cn(
                'relative flex h-4 w-4 shrink-0 items-center justify-center rounded-sm border-[1.5px]',
                rarityVariantStyles[rarity]
              )}
            ></div>
            {inventory[item][rarity].traddable +
              inventory[item][rarity].nonTraddable}
          </button>
        ))}
      </div>
    </ul>
  )
}

type ItemComponentProps = {
  item: ItemWithRarity
} & HTMLAttributes<HTMLLabelElement>

const rarityVariantStyles: { [key in RarityTypes | 'Default']: string } = {
  Default: 'border-[#272043] bg-default-frame',
  Legendary: 'border-[#DCC529] bg-legendary-frame',
  Epic: 'border-[#761B29] bg-epic-frame',
  Rare: 'border-[#2F60A8] bg-rare-frame',
  Uncommon: 'border-[#38896B] bg-uncommon-frame',
  Common: 'border-[#6D737A] bg-common-frame',
}
