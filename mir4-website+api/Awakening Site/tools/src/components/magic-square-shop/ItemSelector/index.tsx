'use client'
import {
  ShardsInventoryAtom,
  type ShardsInventoryType,
} from '@/atoms/MagicSquareShop'
import MagicSquareShopItems from '@/data/MagicSquareShopItems'
import {
  ComplementaryItems,
  extractItemRarity,
  formatItemName,
} from '@/utils/index'
import * as Tabs from '@radix-ui/react-tabs'
import { useAtomValue } from 'jotai'
import { useMemo } from 'react'
import MagicShopItem from './Item'

export type RecipeType = Record<string, number>

const AllRecipes = {}
Object.values(MagicSquareShopItems).forEach((obj) =>
  Object.assign(AllRecipes, obj)
)

export default function MagicSquareItemSelector() {
  const shardsInventory = useAtomValue(ShardsInventoryAtom)

  const totalRecipe = useMemo(() => {
    const totalRecipe = {}

    for (const desiredItem of Object.keys(AllRecipes)) {
      const currentRecipe = AllRecipes[desiredItem as keyof typeof AllRecipes]
      const hasVariants = Array.isArray(currentRecipe)

      if (hasVariants) {
        ;(currentRecipe as any[]).map((recipe) =>
          checkRecipe(recipe, totalRecipe, desiredItem, shardsInventory)
        )
      } else {
        checkRecipe(currentRecipe, totalRecipe, desiredItem, shardsInventory)
      }
    }

    return totalRecipe
  }, [JSON.stringify(shardsInventory)])

  return (
    <div className="relative flex flex-col w-full gap-8">
      <h2 className="text-3xl font-bold">What you want</h2>

      <Tabs.Root defaultValue="special" className='flex flex-col gap-8'>
        <Tabs.List
          className="custom-scroll flex gap-3 font-bold overflow-auto pb-2 [&>button]:flex [&>button]:cursor-pointer [&>button]:rounded [&>button]:px-4 [&>button]:py-2"
          aria-label="Manage your settings"
        >
          {content.map((content) => (
            <Tabs.Trigger
              key={content}
              value={content.toLowerCase()}
              className="data-[state=active]:bg-white/10"
            >
              {content}
            </Tabs.Trigger>
          ))}
        </Tabs.List>

        {content.map((content) => (
          <Tabs.Content
            key={content}
            className="custom-scroll"
            value={content.toLocaleLowerCase()}
          >
            <ul className="flex flex-row justify-center flex-wrap xl:grid xl:grid-cols-4 gap-4">
              {Object.entries(MagicSquareShopItems[content]).map(
                ([item, recipe], index) => {
                  const name = formatItemName(item)
                  const rarity = extractItemRarity(item)

                  const itemName = getSpecialImageByName(
                    name,
                    rarity
                  ).toLowerCase()

                  return (
                    <MagicShopItem
                      key={index}
                      name={itemName}
                      originalName={item}
                      rarity={rarity}
                      recipe={recipe as RecipeType}
                      amount={totalRecipe?.[item as keyof typeof totalRecipe]}
                    />
                  )
                }
              )}
            </ul>
          </Tabs.Content>
        ))}
      </Tabs.Root>
    </div>
  )
}

const content = [
  'Special',
  'Equipment',
  'Promotion',
  'Training',
  'Material',
] as const

function checkRecipe(
  recipe: object,
  totalRecipe: RecipeType,
  desiredItem: string,
  shardsInventory: ShardsInventoryType
) {
  for (const [curItem, curAmount] of Object.entries(recipe as RecipeType)) {
    if (ComplementaryItems.includes(curItem)) continue

    const formattedName = formatItemName(curItem)
    const rarity = extractItemRarity(curItem) as Exclude<
      RarityTypes,
      'Legendary' | 'Common'
    >

    const maxCraftPossible = Math.floor(
      shardsInventory[formattedName as unknown as shardsType][rarity] /
        curAmount
    )

    totalRecipe[desiredItem] =
      (totalRecipe[desiredItem] ?? 0) + maxCraftPossible
  }
}

export function getSpecialImageByName(
  name: string,
  rarity: RarityTypes | 'Default'
) {
  switch (name) {
    case 'darksteel_box':
    case 'copper_box':
    case 'energy_box':
      return `${rarity}_box`
    default:
      return name
  }
}
