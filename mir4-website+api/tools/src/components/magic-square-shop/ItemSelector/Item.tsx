'use client'
import ItemFrame from '@/components/crafting/ItemFrame'
import Tooltip from '@/components/ToolTip'
import { useState } from 'react'
import { getSpecialImageByName, type RecipeType } from '.'
import ItemCost from './ItemCost'

export default function MagicShopItem({
  name,
  originalName,
  rarity,
  recipe,
  amount = 0,
  ...props
}: {
  name: string
  originalName: string
  rarity: RarityTypes | 'Default'
  recipe: RecipeType | RecipeType[]
  amount?: number
} & React.HTMLAttributes<HTMLButtonElement>) {
  return (
    <Tooltip.Wrapper disableHoverableContent>
      <Tooltip.Trigger
        {...props}
        className="flex flex-col gap-4 rounded-lg p-2"
        asChild={false}
      >
        <ItemFrame
          item={getSpecialImageByName(name, rarity).toLowerCase() as ItemTypes}
          rarity={rarity}
        />

        <span
          className={
            'flex w-20 flex-col overflow-hidden rounded bg-primary-600 px-2 py-1 text-xs font-bold text-white sm:text-base'
          }
        >
          {amount}
        </span>
      </Tooltip.Trigger>
      <Tooltip.Content
        className="pointer-events-none flex flex-col gap-4 border border-white/10 bg-primary-400/5 p-2 backdrop-blur-lg data-[state=closed]:animate-contentHide data-[state=open]:animate-contentShow"
        sideOffset={16}
        alignOffset={16}
        side="bottom"
      >
        <h3 className="px-4">{originalName}</h3>

        <SubItemRecipe recipe={recipe} />
      </Tooltip.Content>
    </Tooltip.Wrapper>
  )
}

function SubItemRecipe({ recipe }: { recipe: RecipeType | RecipeType[] }) {
  return (
    <>
      {Array.isArray(recipe) ? (
        <MultipleSubRecipe recipe={recipe} />
      ) : (
        <ul className="flex gap-4">
          {Object.entries(recipe).map(([item, amount]) => (
            <ItemCost key={item} item={item} amount={amount} />
          ))}
        </ul>
      )}
    </>
  )
}

function MultipleSubRecipe({ recipe }: { recipe: RecipeType[] }) {
  const [currentRecipeIndex, setCurrentRecipeIndex] = useState(0)

  setTimeout(
    () =>
      setCurrentRecipeIndex((prev) =>
        prev + 1 > recipe.length - 1 ? 0 : prev + 1
      ),
    1000
  )

  const currentRecipe = recipe[currentRecipeIndex]

  return (
    <ul className="flex gap-4">
      {Object.entries(currentRecipe).map(([item, amount]) => (
        <ItemCost key={item} item={item} amount={amount} />
      ))}
    </ul>
  )
}
