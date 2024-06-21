import { ItemSelectorAtom } from '@/atoms/CraftingCalc'
import { cn } from '@/utils/classNames'
import { useAtom } from 'jotai'

const rarities: Array<Exclude<RarityTypes, 'Uncommon' | 'Common'>> = [
  'Legendary',
  'Epic',
  'Rare',
]

export default function RaritySelector() {
  const [{ rarity }, setItemToCraft] = useAtom(ItemSelectorAtom)

  return (
    <div className="flex w-full gap-2.5">
      {rarities.map((currentRarity) => (
        <button
          key={currentRarity}
          onClick={() =>
            setItemToCraft((prev) => ({ ...prev, rarity: currentRarity }))
          }
          className={cn(
            'h-8 w-8 flex-grow rounded-md py-4 opacity-30 shadow-md outline-none transition-[opacity,_transform] hover:scale-105 hover:opacity-60',
            {
              'bg-legendary-frame': currentRarity === 'Legendary',
            },
            { 'bg-epic-frame': currentRarity === 'Epic' },
            { 'bg-rare-frame': currentRarity === 'Rare' },
            {
              'opacity-100 hover:scale-90 hover:opacity-100':
                rarity === currentRarity,
            }
          )}
          aria-label={currentRarity}
        ></button>
      ))}
    </div>
  )
}
