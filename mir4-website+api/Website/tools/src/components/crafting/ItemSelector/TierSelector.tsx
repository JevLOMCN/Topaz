import { ItemSelectorAtom } from '@/atoms/CraftingCalc'
import MenuButton from '@/components/crafting/ItemSelector/MenuButton'
import { useAtom } from 'jotai'

export default function TierSelector() {
  const [{ tier }, setItemToCraft] = useAtom(ItemSelectorAtom)

  return (
    <div className="flex gap-2.5">
      {ItemTiers.map((currentTier) => (
        <MenuButton
          key={currentTier}
          className={
            'font-ptSerif text-3xl font-bold text-white data-[active=true]:bg-primary-100/10'
          }
          onClick={() =>
            setItemToCraft((prev) => ({
              ...prev,
              tier: currentTier,
            }))
          }
          data-active={currentTier === tier}
        >
          {currentTier === 4 ? 'IV' : 'I'.repeat(currentTier)}
        </MenuButton>
      ))}
    </div>
  )
}

const ItemTiers: ItemTier[] = [1, 2, 3, 4]
