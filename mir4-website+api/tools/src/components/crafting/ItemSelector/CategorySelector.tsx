import { ItemSelectorAtom } from '@/atoms/CraftingCalc'
import MenuButton from '@/components/crafting/ItemSelector/MenuButton'
import { cn } from '@/utils/classNames'
import { getItemImagePath } from '@/utils/index'
import { useAtom } from 'jotai'
import Image from 'next/image'

const Categories: ItemCategory[] = ['weapon', 'armor', 'jewelry', 'earrings']

export default function CategorySelector() {
  const [{ category, rarity, weaponType }, setItemToCraft] =
    useAtom(ItemSelectorAtom)

  return (
    <div className="flex gap-2.5">
      {Categories.map((currentCategory) => {
        const path = getItemImagePath({
          category: currentCategory,
          rarity,
          weaponType,
        })

        return (
          <MenuButton
            key={currentCategory}
            className={cn('data-[active=true]:bg-primary-100/10', {
              'w-50 md:w-56': currentCategory === 'jewelry',
            })}
            onClick={() =>
              setItemToCraft((prev) => ({
                ...prev,
                category: currentCategory,
              }))
            }
            aria-label={currentCategory}
            data-active={currentCategory === category}
          >
            <Image
              src={path.toLowerCase()}
              alt={''}
              width={190}
              height={80}
              className={cn('h-12 w-12 object-contain md:h-20 md:w-20', {
                'w-40 md:w-64': currentCategory === 'jewelry',
              })}
            />
          </MenuButton>
        )
      })}
    </div>
  )
}
