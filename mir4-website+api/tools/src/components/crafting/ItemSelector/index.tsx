import { ItemSelectorAtom } from '@/atoms/CraftingCalc'
import ItemFrame from '@/components/crafting/ItemFrame'
import CategorySelector from '@/components/crafting/ItemSelector/CategorySelector'
import RaritySelector from '@/components/crafting/ItemSelector/RaritySelector'
import TierSelector from '@/components/crafting/ItemSelector/TierSelector'
import WeaponTypeSelector from '@/components/crafting/ItemSelector/WeaponTypeSelector'
import { cn } from '@/utils/classNames'
import { getItemImagePath } from '@/utils/index'
import * as Popover from '@radix-ui/react-popover'
import { useAtomValue } from 'jotai'

export default function ItemSelector() {
  const { category, rarity, tier, weaponType } = useAtomValue(ItemSelectorAtom)

  return (
    <Popover.Root>
      <Popover.Trigger
        id="itemSelectorFrame"
        className="group relative mx-auto h-max w-max transition-transform will-change-transform hover:scale-110 md:mx-0 md:w-auto"
      >
        <ItemFrame
          item={category}
          rarity={rarity}
          tier={tier}
          customPath={getItemImagePath({
            category,
            rarity: rarity.toLowerCase() as RarityTypes,
            weaponType,
          })}
          size="lg"
          className="shrink-0"
        />
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          side="bottom"
          align="center"
          sideOffset={16}
          className={cn(
            'flex flex-col gap-4 rounded-lg border border-white/10 bg-primary-600/60 p-2 backdrop-blur-lg md:p-4',
            'data-[state=closed]:animate-hidePopover data-[state=open]:animate-showPopover'
          )}
        >
          <RaritySelector />

          <CategorySelector />

          <TierSelector />

          <WeaponTypeSelector />
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  )
}
