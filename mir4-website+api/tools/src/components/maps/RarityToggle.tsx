import { type rarityVibilityObject } from '@/atoms/Maps'
import { cn } from '@/utils/classNames'
import { rarityVariantStyles } from '../crafting/ItemFrame'

export default function RarityToggle({
  isActive = {
    Legendary: true,
    Epic: true,
    Rare: true,
    Uncommon: true,
    Common: true,
  },
  action,
}: {
  isActive?: rarityVibilityObject
  action: (rarity: RarityTypes) => void
}) {
  return (
    <>
      {rarities.map((rarity) => (
        <button
          key={rarity}
          className={cn(
            'h-8 w-8 rounded border opacity-40 will-change-transform transition-[opacity,transform] hover:scale-125',
            rarityVariantStyles[rarity],
            { 'opacity-100': isActive[rarity] }
          )}
          onClick={() => action(rarity)}
        />
      ))}
    </>
  )
}

const rarities: RarityTypes[] = [
  'Legendary',
  'Epic',
  'Rare',
  'Uncommon',
  'Common',
]
